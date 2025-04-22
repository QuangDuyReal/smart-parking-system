<?php
require_once __DIR__ . '/../includes/db.php';
// **THÊM KIỂM TRA ĐĂNG NHẬP VÀ QUYỀN ADMIN Ở ĐÂY!**
// Ví dụ:
// session_start();
// if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_is_admin']) {
//     http_response_code(403);
//     echo json_encode(['status' => 'error', 'message' => 'Truy cập bị từ chối']);
//     exit;
// }


header('Content-Type: application/json');

// Giả sử frontend gửi các setting qua POST
$settings_to_update = [];
if (isset($_POST['esp32_gate_ip'])) {
    // Validate IP address format if needed
    $settings_to_update['esp32_gate_ip'] = filter_var($_POST['esp32_gate_ip'], FILTER_VALIDATE_IP) ? $_POST['esp32_gate_ip'] : null;
}
if (isset($_POST['notification_email'])) {
     // Validate email format
     $settings_to_update['notification_email'] = filter_var($_POST['notification_email'], FILTER_VALIDATE_EMAIL) ? $_POST['notification_email'] : null;
}
// Thêm các setting khác nếu cần (ví dụ: cấu hình wifi để hiển thị, nhưng không nên lưu password plain text)


if (empty($settings_to_update)) {
    echo json_encode(['status' => 'warning', 'message' => 'Không có cài đặt nào được gửi để cập nhật.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO settings (setting_key, setting_value)
        VALUES (:key, :value)
        ON DUPLICATE KEY UPDATE setting_value = :value
    ");

    foreach ($settings_to_update as $key => $value) {
        // Bỏ qua nếu validation thất bại (giá trị là null)
        if ($value !== null) {
            $stmt->execute(['key' => $key, 'value' => $value]);
        } else {
             // Có thể báo lỗi về client nếu giá trị không hợp lệ
             error_log("Invalid value provided for setting key: $key");
        }
    }

    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Cài đặt đã được cập nhật thành công.']);

} catch (PDOException $e) {
     if ($pdo->inTransaction()) {
         $pdo->rollBack();
     }
    error_log("Database Error in update_settings.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Lỗi cơ sở dữ liệu khi cập nhật cài đặt.']);
} catch (Exception $e) {
     if ($pdo->inTransaction()) {
         $pdo->rollBack();
     }
    error_log("General Error in update_settings.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống khi cập nhật cài đặt.']);
}
?>