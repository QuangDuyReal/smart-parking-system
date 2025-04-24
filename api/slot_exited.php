<?php
require_once __DIR__ . '/../includes/db.php';
// require_once __DIR__ . '/../vendor/autoload.php'; // Bỏ qua nếu không gửi mail khi ra
require_once __DIR__ . '/../config.php';
// require_once __DIR__ . '/../includes/functions.php'; // Bỏ qua nếu không gửi mail khi ra

header('Content-Type: application/json');

// --- Tham số cấu hình (Có thể đưa vào config.php) ---
define('MIN_OCCUPATION_TIME_SECONDS', 0); // Luôn xử lý tín hiệu exit

$sensor_id = $_POST['sensor_id'] ?? null;
$slot_name_from_esp = $_POST['slot_name'] ?? null;

// --- Validation Input ---
if (!$sensor_id && !$slot_name_from_esp) {
    error_log("Exit Triggered: Missing sensor_id or slot_name.");
    echo json_encode(['status' => 'error', 'message' => 'Thiếu thông tin Sensor ID hoặc Slot Name']);
    exit;
}

try {
    // --- Xác định slot_id và slot_name ---
    $slot = null;
    $pdo->beginTransaction(); // Bắt đầu transaction sớm hơn để khóa slot nếu cần

    // Thêm FOR UPDATE để khóa dòng dữ liệu, tránh race condition nếu có nhiều request exit cùng lúc cho 1 slot
    // Lưu ý: FOR UPDATE chỉ hoạt động với InnoDB và trong transaction
    if ($slot_name_from_esp) {
        $stmtSlot = $pdo->prepare("SELECT * FROM slots WHERE slot_name = ? FOR UPDATE");
        $stmtSlot->execute([$slot_name_from_esp]);
        $slot = $stmtSlot->fetch();
    } elseif ($sensor_id) {
        // Logic mapping Sensor ID -> Slot Name (cần đảm bảo chính xác)
        $assumed_slot_name = "Slot " . $sensor_id;
        $stmtSlot = $pdo->prepare("SELECT * FROM slots WHERE slot_name = ? FOR UPDATE");
        $stmtSlot->execute([$assumed_slot_name]);
        $slot = $stmtSlot->fetch();
    }

    if (!$slot) {
        $pdo->rollBack(); // Nhả khóa
        error_log("Exit Triggered: Slot not found for sensor/name: sensor=$sensor_id, name=$slot_name_from_esp");
        echo json_encode(['status' => 'error', 'message' => 'Slot không tồn tại']);
        exit;
    }

    $slot_id = $slot['slot_id'];
    $slot_name = $slot['slot_name'];

    // --- Kiểm tra trạng thái hiện tại của Slot ---
    if ($slot['status'] !== 'occupied') {
        $pdo->rollBack(); // Nhả khóa
        // Slot không ở trạng thái 'occupied', có thể đã xử lý rồi hoặc lỗi cảm biến
        error_log("Exit Triggered: Slot $slot_name (ID: $slot_id) is not occupied. Current status: {$slot['status']}. Ignoring duplicate signal.");
        echo json_encode(['status' => 'warning', 'message' => "Slot {$slot_name} vốn đang trống hoặc không ở trạng thái chiếm dụng."]);
        exit;
    }

    // --- Kiểm tra thời gian đỗ tối thiểu ---
    if (!empty($slot['occupied_since'])) {
        $occupiedTimestamp = strtotime($slot['occupied_since']);
        $currentTimestamp = time();
        $duration = $currentTimestamp - $occupiedTimestamp;

        if ($duration < MIN_OCCUPATION_TIME_SECONDS) {
            $pdo->rollBack(); // Nhả khóa
            // Xe vừa vào chưa được bao lâu đã báo ra -> Coi như tín hiệu nhiễu
            error_log("Exit Triggered: Slot $slot_name (ID: $slot_id) exit signal received too soon ({$duration}s < " . MIN_OCCUPATION_TIME_SECONDS . "s). Assuming noise/brief movement. Ignoring.");
            echo json_encode([
                'status' => 'warning',
                'message' => "Tín hiệu rời slot {$slot_name} quá nhanh, có thể là nhiễu."
            ]);
            exit;
        }
    } else {
        // Nếu không có thời gian occupied_since -> dữ liệu không nhất quán?
         error_log("Warning: Slot $slot_name (ID: $slot_id) is 'occupied' but 'occupied_since' is NULL or empty.");
         // Vẫn tiếp tục xử lý việc exit nhưng ghi lại cảnh báo
    }


    // --- Lấy thông tin User (nếu có) để ghi log ---
    $user_uid = $slot['current_user_uid'];
    $user_name = 'Người dùng không xác định'; // Mặc định
    if($user_uid) {
        $stmtUser = $pdo->prepare("SELECT name FROM users WHERE uid = ?");
        $stmtUser->execute([$user_uid]);
        $user = $stmtUser->fetch();
        if ($user) {
             $user_name = $user['name'];
        } else {
            error_log("Warning: UID $user_uid was in slot $slot_id but not found in users table during exit.");
        }
    } else {
         error_log("Warning: Slot $slot_id was occupied but had no current_user_uid during exit processing.");
         $user_uid = 'UNKNOWN_ON_EXIT'; // Đánh dấu đặc biệt trong log nếu cần
    }


    // === Thực hiện cập nhật (Sau khi đã qua hết các kiểm tra) ===

    // 1. Cập nhật Slot -> thành 'available', xóa user, xóa thời gian
    $stmtUpdateSlot = $pdo->prepare("UPDATE slots SET status = 'available', current_user_uid = NULL, occupied_since = NULL WHERE slot_id = ?");
    $successUpdate = $stmtUpdateSlot->execute([$slot_id]);

    if(!$successUpdate) {
        $pdo->rollBack();
        error_log("Exit Failed: Could not update slot status for $slot_name (ID: $slot_id).");
        echo json_encode(['status' => 'error', 'message' => 'Lỗi cơ sở dữ liệu khi cập nhật trạng thái slot.']);
        exit;
    }


    // 2. Ghi Log Exit (Sử dụng UID đã lấy được hoặc giá trị đặc biệt)
    $stmtLog = $pdo->prepare("INSERT INTO parking_log (uid, slot_id, action, user_name_snapshot) VALUES (?, ?, 'exit', ?)");
    // Lưu thêm user_name snapshot vào log phòng khi user bị xoá sau này
    $stmtLog->execute([$user_uid, $slot_id, $user_name]);


    // 3. Commit Transaction nếu mọi thứ thành công
    $pdo->commit();

    // === Xử lý sau khi cập nhật thành công ===
    // (Ví dụ: gửi thông báo admin nếu cần)

    // --- Phản hồi thành công cho ESP32 ---
    error_log("Exit Success: Slot $slot_name (ID: $slot_id) updated to available. User: $user_uid ($user_name)."); // Ghi log thành công
    echo json_encode([
        'status' => 'success',
        'message' => "Đã ghi nhận xe rời khỏi vị trí {$slot_name}.",
        'slot_name' => $slot_name
    ]);


} catch (PDOException $e) {
    // Hủy transaction nếu có lỗi DB xảy ra giữa chừng
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database Error in slot_exited.php: " . $e->getMessage() . " | Input: sensor=$sensor_id, name=$slot_name_from_esp");
    echo json_encode(['status' => 'error', 'message' => 'Lỗi cơ sở dữ liệu khi xử lý xe ra.']);
} catch (Exception $e) {
     // Hủy transaction nếu có lỗi khác
     if ($pdo->inTransaction()) {
         $pdo->rollBack();
     }
    error_log("General Error in slot_exited.php: " . $e->getMessage() . " | Input: sensor=$sensor_id, name=$slot_name_from_esp");
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống khi xử lý xe ra.']);
}

?>