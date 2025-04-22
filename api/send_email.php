<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader (nếu dùng composer)
require 'vendor/autoload.php'; // Đường dẫn có thể thay đổi

function sendParkingNotification($toEmail, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Cấu hình Server Settings (Ví dụ dùng Gmail)
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Bật debug chi tiết
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'YOUR_GMAIL_ADDRESS@gmail.com'; // Email của bạn
        $mail->Password   = 'YOUR_GMAIL_APP_PASSWORD';    // Mật khẩu ứng dụng Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8'; // Hỗ trợ tiếng Việt

        // Người gửi
        $mail->setFrom('YOUR_GMAIL_ADDRESS@gmail.com', 'Hệ thống đỗ xe ABC');

        // Người nhận
        $mail->addAddress($toEmail);

        // Nội dung Email
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body); // Nội dung dạng text thuần

        $mail->send();
        error_log('Email sent successfully to ' . $toEmail); // Ghi log thành công
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}"); // Ghi log lỗi
        return false;
    }
}
?>