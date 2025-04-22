<?php
// (Bao gồm kiểm tra đăng nhập/quyền của người dùng web nếu cần)
session_start(); // Giả sử cần kiểm tra session đăng nhập
// if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_is_admin']) {
//     http_response_code(403); // Forbidden
//     echo json_encode(['status' => 'error', 'message' => 'Không có quyền truy cập']);
//     exit;
// }

require_once __DIR__ . '/../config.php'; // Để lấy IP ESP32 nếu cần

header('Content-Type: application/json');

// --- Lấy IP của ESP32 cổng ---
// Cách 1: Hardcode trực tiếp
// $esp32_gate_ip = '192.168.1.100'; // **THAY BẰNG IP THỰC TẾ CỦA ESP32 CỔNG**

// Cách 2: Lấy từ file config.php
// Thêm vào config.php: define('ESP32_GATE_IP', '192.168.1.100');
// require_once __DIR__ . '/../config.php';
// $esp32_gate_ip = defined('ESP32_GATE_IP') ? ESP32_GATE_IP : null;

// Cách 3: Lấy từ cài đặt trong database (linh hoạt nhất)
require_once __DIR__ . '/../includes/db.php';
try {
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'esp32_gate_ip'");
    $esp32_gate_ip = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Lỗi lấy IP ESP32 từ DB: " . $e->getMessage());
    $esp32_gate_ip = null; // Hoặc fallback về giá trị mặc định
}


if (empty($esp32_gate_ip)) {
    echo json_encode(['status' => 'error', 'message' => 'Chưa cấu hình địa chỉ IP của ESP32 cổng.']);
    exit;
}

// --- Địa chỉ endpoint trên ESP32 để mở barrier ---
$open_command_url = "http://" . $esp32_gate_ip . "/open"; // Giả sử ESP32 lắng nghe trên path /open

// --- Sử dụng cURL để gửi request GET ---
$ch = curl_init();

// Thiết lập các tùy chọn cho cURL
curl_setopt($ch, CURLOPT_URL, $open_command_url); // URL cần gọi
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Trả về kết quả dưới dạng chuỗi thay vì in ra
curl_setopt($ch, CURLOPT_HEADER, false);         // Không bao gồm header trong kết quả trả về
curl_setopt($ch, CURLOPT_TIMEOUT, 5);            // Thời gian tối đa chờ kết nối (giây)
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);     // Thời gian tối đa chờ thiết lập kết nối (giây)
// Có thể thêm các tùy chọn khác nếu ESP32 yêu cầu (vd: Authentication)

// Thực thi cURL request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Lấy HTTP status code
$curl_error = curl_error($ch);                    // Lấy lỗi nếu có

// Đóng cURL session
curl_close($ch);

// --- Xử lý kết quả ---
if ($curl_error) {
    // Có lỗi trong quá trình thực thi cURL (vd: không kết nối được)
    error_log("Lỗi cURL khi gọi ESP32: " . $curl_error); // Ghi log lỗi chi tiết
    echo json_encode([
        'status' => 'error',
        'message' => 'Không thể kết nối đến thiết bị Barrier. Vui lòng kiểm tra lại.'
        // 'details' => $curl_error // Có thể thêm chi tiết lỗi nếu cần debug phía client
    ]);
} elseif ($http_code == 200) {
    // ESP32 trả về mã 200 OK (thường là thành công)
    echo json_encode([
        'status' => 'success',
        'message' => 'Đã gửi lệnh mở Barrier thành công.',
        // 'esp_response' => $response // Bao gồm cả response từ ESP32 nếu nó có trả về gì đó
    ]);
} else {
    // ESP32 trả về mã lỗi khác (vd: 404 Not Found, 500 Internal Server Error...)
    error_log("Lỗi từ ESP32 khi mở barrier: HTTP Code " . $http_code . ", Response: " . $response);
    echo json_encode([
        'status' => 'error',
        'message' => 'Thiết bị Barrier báo lỗi (Code: ' . $http_code . ').',
        // 'esp_response' => $response
    ]);
}

?>