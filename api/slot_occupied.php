<?php
require_once __DIR__ . '/../includes/db.php';
// Dòng này QUAN TRỌNG cho việc lấy thông tin SMTP và gọi hàm
require_once __DIR__ . '/../config.php';
// Dòng này QUAN TRỌNG để gọi hàm gửi mail
require_once __DIR__ . '/../includes/functions.php';
// Dòng này QUAN TRỌNG nếu bạn cài PHPMailer qua Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Use statements không thực sự cần nếu đã có trong functions.php, nhưng để đây cũng không sao
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Giả định ESP32 gửi 'sensor_id' hoặc 'slot_name'
$sensor_id = $_POST['sensor_id'] ?? null;
$slot_name_from_esp = $_POST['slot_name'] ?? null;

// --- Validation Input ---
if (!$sensor_id && !$slot_name_from_esp) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu thông tin Sensor ID hoặc Slot Name']);
    exit;
}

try {
    // --- Xác định slot_id và slot_name ---
    $slot = null;
    // Sử dụng COALESCE để ưu tiên slot_name_from_esp nếu có
    $target_slot_name = $slot_name_from_esp ?? ("Slot " . $sensor_id); // Logic map sensor -> name đơn giản

    $stmtSlot = $pdo->prepare("SELECT * FROM slots WHERE slot_name = ?");
    $stmtSlot->execute([$target_slot_name]);
    $slot = $stmtSlot->fetch();

    if (!$slot) {
        error_log("Slot Occupied: Slot not found for target name: $target_slot_name (Sensor: $sensor_id, Name: $slot_name_from_esp)");
        http_response_code(404); // Not Found rõ ràng hơn
        echo json_encode(['status' => 'error', 'message' => "Slot '{$target_slot_name}' không tồn tại."]);
        exit;
    }

    $slot_id = $slot['slot_id'];
    $slot_name = $slot['slot_name']; // Dùng tên slot chính xác từ DB

    // --- Kiểm tra trạng thái hiện tại của slot ---
    if ($slot['status'] !== 'available') {
         error_log("Slot Occupied Triggered but slot $slot_name (ID: $slot_id) is not available. Status: {$slot['status']}");
         http_response_code(409); // Conflict - Trạng thái không phù hợp
         echo json_encode(['status' => 'warning', 'message' => "Slot {$slot_name} hiện không ở trạng thái 'available' ({$slot['status']})."]);
         exit;
    }

    // --- Tìm UID đang chờ gần nhất chưa được gán ---
    // Thêm kiểm tra thời gian của pending entry nếu cần (vd: chỉ lấy entry trong vòng 5 phút gần nhất)
    // $fiveMinutesAgo = date('Y-m-d H:i:s', strtotime('-5 minutes'));
    // $stmtPending = $pdo->prepare("SELECT id, uid FROM pending_entries WHERE assigned = FALSE AND scan_time >= ? ORDER BY scan_time DESC LIMIT 1");
    // $stmtPending->execute([$fiveMinutesAgo]);
    $stmtPending = $pdo->prepare("SELECT id, uid FROM pending_entries WHERE assigned = FALSE ORDER BY scan_time DESC LIMIT 1");
    $stmtPending->execute();
    $pending_entry = $stmtPending->fetch();

    if (!$pending_entry) {
        error_log("Slot Occupied Triggered for $slot_name (ID: $slot_id) but NO RECENT PENDING ENTRY found.");
        http_response_code(404); // Không tìm thấy thông tin cần thiết
        echo json_encode(['status' => 'error', 'message' => "Không tìm thấy thông tin người dùng vừa quét thẻ ở cổng vào."]);
        exit;
    }

    $pending_id = $pending_entry['id'];
    $user_uid = $pending_entry['uid'];

    // --- Lấy thông tin user ---
    $stmtUser = $pdo->prepare("SELECT name, email FROM users WHERE uid = ?");
    $stmtUser->execute([$user_uid]);
    $user = $stmtUser->fetch();
    // Đặt giá trị mặc định nếu user không tìm thấy (dù không nên xảy ra nếu pending entry hợp lệ)
    $user_name = $user['name'] ?? 'Người dùng không xác định';
    $user_email = $user['email'] ?? null;

    if(!$user) {
         error_log("Warning: User with UID {$user_uid} from pending entry ID {$pending_id} not found in users table.");
    }


    // === Thực hiện cập nhật CSDL trong Transaction ===
    $pdo->beginTransaction();

    try {
        // 1. Cập nhật Slot
        $stmtUpdateSlot = $pdo->prepare("UPDATE slots SET status = 'occupied', current_user_uid = ?, occupied_since = NOW() WHERE slot_id = ? AND status = 'available'"); // Thêm AND status='available' để double check
        $stmtUpdateSlot->execute([$user_uid, $slot_id]);
        // Kiểm tra xem có đúng 1 dòng được cập nhật không, đề phòng race condition
        if ($stmtUpdateSlot->rowCount() !== 1) {
             throw new Exception("Failed to update slot or slot status changed unexpectedly.");
        }


        // 2. Ghi Log Parking
        $stmtLog = $pdo->prepare("INSERT INTO parking_log (uid, slot_id, action, user_name_snapshot) VALUES (?, ?, 'entry', ?)");
        $stmtLog->execute([$user_uid, $slot_id, $user_name]); // Lưu tên user vào log

        // 3. Đánh dấu Pending Entry đã được sử dụng
        $stmtUpdatePending = $pdo->prepare("UPDATE pending_entries SET assigned = TRUE, assigned_slot_id = ? WHERE id = ?");
        $stmtUpdatePending->execute([$slot_id, $pending_id]);

        // Nếu tất cả thành công, commit transaction
        $pdo->commit();

    } catch (Exception $e) {
        // Nếu có lỗi trong transaction, hủy bỏ thay đổi
        $pdo->rollBack();
        // Ném lại lỗi để khối catch bên ngoài xử lý
        throw $e; // Quan trọng: Ném lại để ghi log và trả lỗi đúng
    }

    // === Gửi Email Thông Báo (Sau khi commit thành công) ===
    if (!empty($user_email)) {
        try {
            // Gọi hàm sendParkingNotification từ includes/functions.php
            sendParkingNotification($user_email, $user_name, $slot_name, 'entry');
            error_log("Notification email sent successfully to {$user_email} for slot {$slot_name}."); // Ghi log thành công

        } catch (Exception $mailException) {
            // Ghi log lỗi gửi mail nhưng KHÔNG dừng script hay báo lỗi cho ESP
            error_log("!!! EMAIL SENDING FAILED for user {$user_name} (Email: {$user_email}, Slot: {$slot_name}): " . $mailException->getMessage());
            // Không thay đổi response status
        }
    } else {
        // Ghi log nếu không gửi được do thiếu email
        error_log("Skipping notification email for user {$user_name} (UID: {$user_uid}) in slot {$slot_name} - No email address provided.");
    }

    // === Phản hồi thành công cho ESP32 ===
    http_response_code(200); // Đảm bảo trả về 200 OK
    echo json_encode([
        'status' => 'success',
        'message' => "Đã ghi nhận {$user_name} vào vị trí {$slot_name}.",
        'slot_name' => $slot_name,
        'user_name' => $user_name
    ]);
    exit; // Kết thúc script

} catch (PDOException $e) {
    // Đã rollback ở trên nếu lỗi xảy ra trong transaction
    error_log("Database Error in slot_occupied.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi cơ sở dữ liệu khi cập nhật trạng thái slot.']);
    exit;
} catch (Exception $e) { // Bắt các lỗi khác (ví dụ lỗi từ throw trong transaction)
    // Đã rollback ở trên nếu lỗi xảy ra trong transaction
    error_log("General Error in slot_occupied.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống khi xử lý: ' . $e->getMessage()]);
    exit;
}
?>