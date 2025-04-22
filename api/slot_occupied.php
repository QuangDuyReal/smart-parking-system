<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Cho PHPMailer
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php'; // Giả sử có file chứa hàm sendParkingNotification

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Giả định ESP32 gửi 'sensor_id' hoặc 'slot_name'
$sensor_id = $_POST['sensor_id'] ?? null;
$slot_name_from_esp = $_POST['slot_name'] ?? null;

if (!$sensor_id && !$slot_name_from_esp) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu thông tin Sensor ID hoặc Slot Name']);
    exit;
}

try {
    // Xác định slot_id và slot_name
    $slot = null;
    if ($slot_name_from_esp) {
        $stmtSlot = $pdo->prepare("SELECT * FROM slots WHERE slot_name = ?");
        $stmtSlot->execute([$slot_name_from_esp]);
        $slot = $stmtSlot->fetch();
    } elseif ($sensor_id) {
        // *** Cần logic mapping Sensor ID -> Slot Name/ID nếu chúng khác nhau ***
        // Ví dụ đơn giản: Slot Name là "Slot X" với X là sensor_id
        $assumed_slot_name = "Slot " . $sensor_id;
        $stmtSlot = $pdo->prepare("SELECT * FROM slots WHERE slot_name = ?");
        $stmtSlot->execute([$assumed_slot_name]);
        $slot = $stmtSlot->fetch();
    }

    if (!$slot) {
        error_log("Slot not found for sensor/name: sensor=$sensor_id, name=$slot_name_from_esp");
        echo json_encode(['status' => 'error', 'message' => 'Slot không tồn tại']);
        exit;
    }

    $slot_id = $slot['slot_id'];
    $slot_name = $slot['slot_name'];

    // Kiểm tra trạng thái hiện tại của slot
    if ($slot['status'] !== 'available') {
         // Slot này không trống? Lỗi logic hoặc sensor báo sai?
         error_log("Slot Occupied Triggered but slot $slot_name (ID: $slot_id) is not available. Status: {$slot['status']}");
         echo json_encode(['status' => 'warning', 'message' => "Slot {$slot_name} hiện không trống."]);
         exit; // Hoặc xử lý khác tùy logic
    }


    // Tìm UID đang chờ gần nhất chưa được gán
    $stmtPending = $pdo->prepare("SELECT id, uid FROM pending_entries WHERE assigned = FALSE ORDER BY scan_time DESC LIMIT 1");
    $stmtPending->execute();
    $pending_entry = $stmtPending->fetch();

    if (!$pending_entry) {
        // Không có ai quét thẻ đang chờ? Xe vào lậu?
        error_log("Slot Occupied Triggered for $slot_name (ID: $slot_id) but NO PENDING ENTRY found.");
        // Tùy chọn: Có thể vẫn cập nhật slot là occupied nhưng không có user_uid
        // $stmtUpdateUnknown = $pdo->prepare("UPDATE slots SET status = 'occupied', occupied_since = NOW(), current_user_uid = NULL WHERE slot_id = ?");
        // $stmtUpdateUnknown->execute([$slot_id]);
        // echo json_encode(['status' => 'warning', 'message' => "Slot {$slot_name} đã có xe nhưng không rõ người dùng."]);
        echo json_encode(['status' => 'error', 'message' => "Không tìm thấy thông tin người dùng vừa quét thẻ."]);
        exit;
    }

    $pending_id = $pending_entry['id'];
    $user_uid = $pending_entry['uid'];

    // Lấy thông tin user
    $stmtUser = $pdo->prepare("SELECT name, email FROM users WHERE uid = ?");
    $stmtUser->execute([$user_uid]);
    $user = $stmtUser->fetch();
    $user_name = $user ? $user['name'] : 'Người dùng không xác định';
    $user_email = $user ? $user['email'] : null;


    // === Thực hiện cập nhật ===
    $pdo->beginTransaction(); // Bắt đầu transaction để đảm bảo tính toàn vẹn

    // 1. Cập nhật Slot
    $stmtUpdateSlot = $pdo->prepare("UPDATE slots SET status = 'occupied', current_user_uid = ?, occupied_since = NOW() WHERE slot_id = ?");
    $stmtUpdateSlot->execute([$user_uid, $slot_id]);

    // 2. Ghi Log
    $stmtLog = $pdo->prepare("INSERT INTO parking_log (uid, slot_id, action) VALUES (?, ?, 'entry')");
    $stmtLog->execute([$user_uid, $slot_id]);

    // 3. Đánh dấu Pending Entry đã được sử dụng
    $stmtUpdatePending = $pdo->prepare("UPDATE pending_entries SET assigned = TRUE WHERE id = ?");
    $stmtUpdatePending->execute([$pending_id]);

    $pdo->commit(); // Hoàn tất transaction

    // === Gửi Email (Nếu có email và cấu hình) ===
    if ($user_email) {
        // Gọi hàm gửi mail (định nghĩa trong functions.php hoặc ngay đây)
        // Cần có hàm sendParkingNotification($toEmail, $name, $slotName, $action = 'entry')
        // sendParkingNotification($user_email, $user_name, $slot_name, 'entry');
        // Ví dụ inline (cần có hàm này hoặc dùng trực tiếp PHPMailer):
         try {
             sendParkingNotification($user_email, $user_name, $slot_name, 'entry');
         } catch (Exception $mailException) {
             error_log("Failed to send entry email to $user_email for slot $slot_name: " . $mailException->getMessage());
             // Không làm dừng chương trình chính, chỉ log lỗi mail
         }
    }

    // Phản hồi thành công
    echo json_encode([
        'status' => 'success',
        'message' => "Đã ghi nhận {$user_name} vào vị trí {$slot_name}.",
        'slot_name' => $slot_name,
        'user_name' => $user_name
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Hủy transaction nếu có lỗi DB
    }
    error_log("Database Error in slot_occupied.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Lỗi cơ sở dữ liệu khi cập nhật trạng thái slot.']);
} catch (Exception $e) { // Bắt các lỗi chung khác
     if ($pdo->inTransaction()) {
         $pdo->rollBack();
     }
     error_log("General Error in slot_occupied.php: " . $e->getMessage());
     echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống khi xử lý.']);
}

?>