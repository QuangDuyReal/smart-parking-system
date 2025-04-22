<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Đảm bảo autoload và config đã được require ở file gọi hàm này
// require_once __DIR__ . '/../vendor/autoload.php';
// require_once __DIR__ . '/../config.php';

/**
 * Gửi email thông báo đỗ xe.
 *
 * @param string $toEmail Địa chỉ email người nhận.
 * @param string $name Tên người nhận.
 * @param string $slotName Tên vị trí đỗ xe.
 * @param string $action Hành động ('entry' hoặc 'exit').
 * @throws Exception Nếu gửi mail thất bại.
 */
function sendParkingNotification($toEmail, $name, $slotName, $action = 'entry') {
    // Kiểm tra xem email có hợp lệ không trước khi gửi
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        error_log("Attempted to send email to invalid address: $toEmail");
        return; // Không gửi nếu email không hợp lệ
    }
     // Kiểm tra xem có đủ thông tin SMTP không
    if (!defined('SMTP_HOST') || !defined('SMTP_USER') || !defined('SMTP_PASS')) {
         error_log("SMTP settings are not fully configured. Cannot send email.");
         return; // Không thể gửi nếu thiếu cấu hình
    }


    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->SMTPDebug = 0;                      // Enable verbose debug output (0 for production, 2 for detailed debugging)
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = SMTP_HOST;                   // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = SMTP_USER;                // SMTP username
        $mail->Password   = SMTP_PASS;                           // SMTP password (App Password for Gmail)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
        $mail->Port       = SMTP_PORT;                                   // TCP port to connect to

        //Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $name);     // Add a recipient

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8'; // Đảm bảo hỗ trợ tiếng Việt

        if ($action == 'entry') {
            $mail->Subject = 'Thông báo vào bãi đỗ xe';
            $mail->Body    = "Xin chào <b>{$name}</b>,<br><br>Bạn vừa đỗ xe vào vị trí: <b>{$slotName}</b>.<br>Thời gian: " . date('Y-m-d H:i:s') . "<br><br>Cảm ơn bạn đã sử dụng dịch vụ!";
            $mail->AltBody = "Xin chào {$name},\n\nBạn vừa đỗ xe vào vị trí: {$slotName}.\nThời gian: " . date('Y-m-d H:i:s') . "\n\nCảm ơn bạn đã sử dụng dịch vụ!";
        } else { // action == 'exit'
            $mail->Subject = 'Thông báo rời bãi đỗ xe';
            $mail->Body    = "Xin chào <b>{$name}</b>,<br><br>Bạn vừa lấy xe khỏi vị trí: <b>{$slotName}</b>.<br>Thời gian: " . date('Y-m-d H:i:s') . "<br><br>Hẹn gặp lại!";
            $mail->AltBody = "Xin chào {$name},\n\nBạn vừa lấy xe khỏi vị trí: {$slotName}.\nThời gian: " . date('Y-m-d H:i:s') . "\n\nHẹn gặp lại!";
        }


        $mail->send();
        // echo 'Message has been sent'; // Ghi log thành công nếu cần
    } catch (Exception $e) {
        // Ném lại exception để file gọi nó có thể bắt và ghi log
        throw new Exception("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}

?>