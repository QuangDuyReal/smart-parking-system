<?php
// api/get_settings.php
require_once __DIR__ . '/../includes/db.php';

// **QUAN TRỌNG: Thêm kiểm tra đăng nhập ở đây nếu cần**
/*
session_start();
if (!isset($_SESSION['user_logged_in'])) { // ... kiểm tra quyền ...
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Truy cập bị từ chối.']);
    exit;
}
*/

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Xử lý giá trị
    $settings_processed = [
        'system_name' => $settings_raw['system_name'] ?? 'Bãi đỗ xe Thông Minh',
        'esp32_gate_ip' => $settings_raw['esp32_gate_ip'] ?? '',
        'notification_email' => $settings_raw['notification_email'] ?? '',
        // --- DÒNG XỬ LÝ CHECKBOX ĐÃ BỊ XÓA ---
        // Thêm các settings khác nếu có
    ];

    echo json_encode(['status' => 'success', 'data' => $settings_processed]);

} catch (PDOException $e) {
    error_log("Database Error in get_settings.php: " . $e->getMessage());
    http_response_code(500); echo json_encode(['status' => 'error', 'message' => 'Lỗi truy vấn cài đặt hệ thống từ CSDL.']);
} catch (Exception $e) {
    error_log("General Error in get_settings.php: " . $e->getMessage());
    http_response_code(500); echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống khi lấy cài đặt.']);
}
?>