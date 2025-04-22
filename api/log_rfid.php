<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Nếu dùng Composer cho PHPMailer
require_once __DIR__ . '/../config.php';
// Hoặc require 'path/to/PHPMailer/src/Exception.php', etc. nếu tải thủ công

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json'); // Trả về JSON

// --- Giả định ESP32 gửi dữ liệu qua POST ---
$uid = $_POST['uid'] ?? null;
$sensor_id = $_POST['sensor_id'] ?? null; // ID của cảm biến/slot mà ESP32 phát hiện

if (!$uid || !$sensor_id) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu UID hoặc Sensor ID']);
    exit;
}

try {
    // --- Logic xử lý ---
    // 1. Tìm user dựa vào UID
    $stmtUser = $pdo->prepare("SELECT * FROM users WHERE uid = ?");
    $stmtUser->execute([$uid]);
    $user = $stmtUser->fetch();

    if (!$user) {
         // (Tùy chọn) Ghi log người lạ hoặc chỉ báo lỗi
         echo json_encode(['status' => 'error', 'message' => 'UID không hợp lệ']);
         // Hoặc ghi log: file_put_contents('unknown_log.txt', date('Y-m-d H:i:s') . " - Unknown UID: $uid at Sensor: $sensor_id\n", FILE_APPEND);
         exit;
    }

    // 2. Tìm slot dựa vào sensor_id (Giả định sensor_id ánh xạ trực tiếp đến slot_name hoặc slot_id)
    // Cần có cơ chế mapping Sensor ID -> Slot ID/Name nếu chúng không giống nhau
    $slot_name = "Slot " . $sensor_id; // Ví dụ đơn giản, cần điều chỉnh theo thực tế
    $stmtSlot = $pdo->prepare("SELECT * FROM slots WHERE slot_name = ?");
    $stmtSlot->execute([$slot_name]);
    $slot = $stmtSlot->fetch();

    if (!$slot) {
         echo json_encode(['status' => 'error', 'message' => 'Slot không tồn tại cho sensor này']);
         exit;
    }

    $slot_id = $slot['slot_id'];
    $current_status = $slot['status'];
    $response_message = '';

    // --- Logic xử lý vào/ra ---
    // Giả định: Nếu slot 'available', đây là lượt VÀO. Nếu 'occupied' và UID khớp, đây là lượt RA.
    if ($current_status == 'available') {
        // === Hành động: VÀO ===
        // Kiểm tra xem còn chỗ không (tổng thể) - Tùy chọn
        $stmtCount = $pdo->query("SELECT COUNT(*) as occupied_count FROM slots WHERE status = 'occupied'");
        $occupied_count = $stmtCount->fetchColumn();
        $stmtTotal = $pdo->query("SELECT COUNT(*) as total_count FROM slots");
        $total_count = $stmtTotal->fetchColumn();

        if ($occupied_count >= $total_count) {
            echo json_encode(['status' => 'warning', 'message' => 'Bãi đỗ đã đầy!']);
            // Gửi tín hiệu từ chối cho ESP32 nếu có (ví dụ: đóng barrier lại)
            // Gửi cảnh báo cho admin qua email
            exit;
        }

        // Cập nhật trạng thái slot
        $stmtUpdateSlot = $pdo->prepare("UPDATE slots SET status = 'occupied', current_user_uid = ?, occupied_since = NOW() WHERE slot_id = ?");
        $stmtUpdateSlot->execute([$uid, $slot_id]);

        // Ghi log
        $stmtLog = $pdo->prepare("INSERT INTO parking_log (uid, slot_id, action) VALUES (?, ?, 'entry')");
        $stmtLog->execute([$uid, $slot_id]);

        $response_message = "Chào mừng {$user['name']} vào vị trí {$slot['slot_name']}";

        // Gửi email thông báo (nếu user có email và đã cài đặt)
        if (!empty($user['email'])) {
            // (Code gửi mail bằng PHPMailer đặt ở đây hoặc gọi hàm riêng)
            // sendParkingNotification($user['email'], $user['name'], $slot['slot_name'], 'entry');
        }

    } elseif ($current_status == 'occupied' && $slot['current_user_uid'] == $uid) {
        // === Hành động: RA ===
         // Cập nhật trạng thái slot
        $stmtUpdateSlot = $pdo->prepare("UPDATE slots SET status = 'available', current_user_uid = NULL, occupied_since = NULL WHERE slot_id = ?");
        $stmtUpdateSlot->execute([$slot_id]);

         // Ghi log
        $stmtLog = $pdo->prepare("INSERT INTO parking_log (uid, slot_id, action) VALUES (?, ?, 'exit')");
        $stmtLog->execute([$uid, $slot_id]);

         $response_message = "Tạm biệt {$user['name']} từ vị trí {$slot['slot_name']}";

         // Gửi email thông báo RA (nếu cần)
         // if (!empty($user['email'])) {
         //    sendParkingNotification($user['email'], $user['name'], $slot['slot_name'], 'exit');
         // }
    } elseif ($current_status == 'occupied' && $slot['current_user_uid'] != $uid) {
        // Trường hợp: Slot đang có người khác, nhưng lại có người quẹt thẻ vào? -> Lỗi logic hoặc cố tình?
        echo json_encode(['status' => 'error', 'message' => "Slot {$slot['slot_name']} đang có người khác sử dụng!"]);
        // Ghi log bất thường, gửi cảnh báo admin
        exit;
    } else {
         // Các trạng thái khác ('reserved', 'maintenance')
         echo json_encode(['status' => 'warning', 'message' => "Slot {$slot['slot_name']} không khả dụng ({$current_status})"]);
         exit;
    }


    // --- Phản hồi cho ESP32 ---
    echo json_encode([
        'status' => 'success',
        'message' => $response_message,
        'slot_name' => $slot['slot_name'],
        'user_name' => $user['name']
    ]);

} catch (PDOException $e) {
    // Log lỗi thực tế vào file thay vì echo ra cho ESP32
    error_log("Database Error in log_rfid.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Lỗi máy chủ khi xử lý.']);
} catch (Exception $e) { // Bắt lỗi từ PHPMailer
     error_log("Email Error in log_rfid.php: " . $e->getMessage());
     // Vẫn trả về success cho ESP nếu việc đỗ xe thành công, chỉ log lỗi email
     echo json_encode([
         'status' => 'success_email_failed',
         'message' => $response_message . " (Không gửi được email)",
         'slot_name' => $slot['slot_name'],
         'user_name' => $user['name']
     ]);
}

?>