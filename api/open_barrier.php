<?php
// api/open_barrier.php

// **PHẦN KIỂM TRA ĐĂNG NHẬP & QUYỀN ĐÃ BỊ XÓA/VÔ HIỆU HÓA**
// session_start(); // Giữ lại nếu bạn có dùng session cho việc khác, bỏ đi nếu không
/*
if (!isset($_SESSION['user_logged_in']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Truy cập bị từ chối. Yêu cầu quyền quản trị viên.']);
    exit;
}
*/

// Chỉ chấp nhận phương thức POST từ web interface
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không được phép.']);
    exit;
}


require_once __DIR__ . '/../includes/db.php'; // Để lấy IP từ CSDL

header('Content-Type: application/json'); // Luôn trả về JSON

$esp32_gate_ip = null;

// --- Lấy IP của ESP32 cổng từ CSDL (Bảng settings) ---
try {
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'esp32_gate_ip'");
    $esp32_gate_ip = $stmt->fetchColumn();

    error_log("[Open Barrier API] Retrieved ESP32 Gate IP from DB: " . ($esp32_gate_ip ?: 'Not Set'));

} catch (PDOException $e) {
    error_log("Database Error getting ESP32 IP in open_barrier.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi cơ sở dữ liệu khi lấy cấu hình IP thiết bị.']);
    exit;
}


// --- Kiểm tra xem IP có được cấu hình không ---
if (empty($esp32_gate_ip) || !filter_var($esp32_gate_ip, FILTER_VALIDATE_IP)) {
     error_log("[Open Barrier API] Invalid or missing ESP32 Gate IP: " . ($esp32_gate_ip ?: 'Empty'));
    http_response_code(400); // Bad Request - Thiếu cấu hình
    echo json_encode(['status' => 'error', 'message' => 'Chưa cấu hình hoặc địa chỉ IP của ESP32 cổng không hợp lệ trong phần Cài đặt.']);
    exit;
}

// --- Địa chỉ endpoint trên ESP32 để mở barrier ---
$open_command_url = "http://" . $esp32_gate_ip . "/open";
error_log("[Open Barrier API] Attempting to call URL: " . $open_command_url);


// --- Sử dụng cURL để gửi request POST ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $open_command_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, '');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

$response_body = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_errno = curl_errno($ch);
$curl_error = curl_error($ch);
curl_close($ch);

error_log("[Open Barrier API] cURL Result: Errno=" . $curl_errno . ", HTTP Code=" . $http_code . ", Response: " . $response_body);


// --- Xử lý kết quả ---
if ($curl_errno !== 0) {
    error_log("[Open Barrier API] cURL Execution Error: (" . $curl_errno . ") " . $curl_error);
    http_response_code(503);
    echo json_encode([
        'status' => 'error',
        'message' => 'Không thể kết nối đến thiết bị Barrier. Vui lòng kiểm tra lại kết nối mạng và trạng thái thiết bị.',
        'details' => $curl_error
    ]);
} elseif ($http_code == 200) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Đã gửi lệnh mở Barrier thành công đến thiết bị.',
        'esp_response' => $response_body
    ]);
} else {
    error_log("[Open Barrier API] Error Response from ESP32: HTTP Code " . $http_code);
    http_response_code(502);
    echo json_encode([
        'status' => 'error',
        'message' => 'Thiết bị Barrier báo lỗi hoặc không phản hồi đúng cách (Code: ' . $http_code . ').',
        'esp_response' => $response_body
    ]);
}

?>