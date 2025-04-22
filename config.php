<?php
// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Thay bằng user của bạn
define('DB_PASS', '');     // Thay bằng password của bạn
define('DB_NAME', 'smart_parking_system'); // Thay bằng tên DB của bạn

// SMTP Settings (for PHPMailer)
define('SMTP_HOST', 'smtp.gmail.com'); // Hoặc SMTP server khác
define('SMTP_PORT', 587); // TLS Port (hoặc 465 for SSL)
define('SMTP_USER', 'fansjaki@gmail.com'); // Email gửi đi
define('SMTP_PASS', 'your_app_password'); // Mật khẩu ứng dụng Gmail hoặc mật khẩu SMTP
define('SMTP_FROM_EMAIL', 'your_email@gmail.com');
define('SMTP_FROM_NAME', 'Hệ thống đỗ xe thông minh');

// Other settings
define('BASE_URL', 'http://localhost/parking-manager/'); // URL gốc của web
?>