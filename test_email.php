<?php
// test_email.php

echo "<h1>Kiểm tra gửi Email</h1>";

// Nạp các file cần thiết
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php'; // Chứa hàm sendParkingNotification
require_once __DIR__ . '/vendor/autoload.php';   // Cho PHPMailer

// --- Thông tin giả lập cho việc test ---
$test_email_recipient = 'fansjaki@gmail.com'; // *** THAY BẰNG EMAIL THỰC CỦA BẠN ĐỂ NHẬN MAIL TEST ***
$test_user_name = 'QuangDuyReal';
$test_slot_name = 'Slot Test A99';
// --- ---

echo "<p>Đang cố gắng gửi email đến: <strong>" . htmlspecialchars($test_email_recipient) . "</strong></p>";
echo "<p>Tên người dùng: " . htmlspecialchars($test_user_name) . "</p>";
echo "<p>Vị trí đỗ: " . htmlspecialchars($test_slot_name) . "</p>";
echo "<hr>";

try {
    // Gọi trực tiếp hàm gửi mail
    sendParkingNotification($test_email_recipient, $test_user_name, $test_slot_name, 'entry');

    // Nếu không có lỗi ném ra, tức là hàm đã chạy xong (chưa chắc mail đã đến nơi)
    echo "<p style='color: green; font-weight: bold;'>Đã gọi hàm sendParkingNotification thành công!</p>";
    echo "<p><strong>Vui lòng kiểm tra hộp thư đến (và cả thư mục SPAM) của địa chỉ:</strong> " . htmlspecialchars($test_email_recipient) . "</p>";
    echo "<p><i>Lưu ý: Mail có thể mất vài phút để đến nơi.</i></p>";

} catch (Exception $e) {
    // Nếu có lỗi từ PHPMailer (sai pass, sai host, kết nối...), nó sẽ được bắt ở đây
    echo "<h3 style='color: red;'>Gửi Email Thất Bại!</h3>";
    echo "<p><strong>Lỗi chi tiết:</strong></p>";
    echo "<pre style='background-color: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";
    echo "<p><strong>Kiểm tra lại các cài đặt SMTP trong `config.php` (đặc biệt là SMTP_PASS - mật khẩu ứng dụng nếu dùng Gmail) và kết nối mạng của server.</strong></p>";
    echo "<p>Kiểm tra thêm log lỗi PHP tại: <code>C:/xampp/php/logs/php_error_log</code></p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Quay lại trang chính</a></p>";

?>