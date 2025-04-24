<?php
// api/update_settings.php
require_once __DIR__ . '/../includes/db.php';

// **QUAN TRỌNG: Thêm kiểm tra đăng nhập và quyền Admin ở đây!**
/*
session_start();
if (!isset($_SESSION['user_logged_in'])) { // ... kiểm tra quyền ...
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Truy cập bị từ chối.']);
    exit;
}
*/

header('Content-Type: application/json');

$settings_to_update = [];

// --- Lấy và Validate các giá trị ---
// IP ESP32
if (isset($_POST['esp32_gate_ip'])) {
    $ip = trim($_POST['esp32_gate_ip']);
    $settings_to_update['esp32_gate_ip'] = ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP)) ? $ip : false;
     if ($settings_to_update['esp32_gate_ip'] === false) {
         http_response_code(400); echo json_encode(['status' => 'error', 'message' => 'Địa chỉ IP ESP32 không hợp lệ.']); exit;
     }
}
// Email thông báo hệ thống
if (isset($_POST['notification_email'])) {
     $email = trim($_POST['notification_email']);
     $settings_to_update['notification_email'] = ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL)) ? $email : false;
      if ($settings_to_update['notification_email'] === false) {
         http_response_code(400); echo json_encode(['status' => 'error', 'message' => 'Địa chỉ Email nhận thông báo không hợp lệ.']); exit;
     }
}
// Tên hệ thống
if (isset($_POST['system_name'])) {
     $system_name = trim(strip_tags($_POST['system_name']));
     if (empty($system_name)) {
         http_response_code(400); echo json_encode(['status' => 'error', 'message' => 'Tên hệ thống không được để trống.']); exit;
     }
      $settings_to_update['system_name'] = $system_name;
}

// --- DÒNG XỬ LÝ CHECKBOX ĐÃ BỊ XÓA ---

// --- Lưu vào Database ---
if (empty($settings_to_update)) {
    echo json_encode(['status' => 'info', 'message' => 'Không có cài đặt nào được gửi để cập nhật.']);
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = :value");
    foreach ($settings_to_update as $key => $value) {
         $stmt->execute(['key' => $key, 'value' => $value]);
    }
    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Cài đặt đã được cập nhật thành công.']);
} catch (PDOException $e) {
     if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log("Database Error in update_settings.php: " . $e->getMessage());
    http_response_code(500); echo json_encode(['status' => 'error', 'message' => 'Lỗi cơ sở dữ liệu khi cập nhật cài đặt.']);
} catch (Exception $e) {
     if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log("General Error in update_settings.php: " . $e->getMessage());
    http_response_code(500); echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống khi cập nhật cài đặt.']);
}
?>