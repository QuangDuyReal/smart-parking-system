<?php
// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Thay bằng user của bạn
define('DB_PASS', '');     // Thay bằng password của bạn
define('DB_NAME', 'smart_parking_system'); // Thay bằng tên DB của bạn

// SMTP Settings (for PHPMailer)
define('SMTP_HOST', 'smtp.gmail.com'); // Hoặc SMTP server khác
define('SMTP_PORT', 587); // TLS Port (hoặc 465 for SSL)
define('SMTP_USER', 'iot.parkingmanager@gmail.com'); // Email gửi đi (dùng để xác thực)
define('SMTP_PASS', 'wguthfdnxwkozium'); // Mật khẩu ứng dụng cho email trên
define('SMTP_FROM_EMAIL', 'iot.parkingmanager@gmail.com'); // *** SỬA THÀNH EMAIL XÁC THỰC ***
define('SMTP_FROM_NAME', 'Smart Parking System'); // Tên hiển thị người gửi

// Other settings
define('BASE_URL', 'http://localhost/parking-manager/'); // URL gốc của web
?>