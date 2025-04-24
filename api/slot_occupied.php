<?php
// api/slot_occupied.php (Đã bỏ kiểm tra setting email)

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$sensor_id = $_POST['sensor_id'] ?? null;
$slot_name_from_esp = $_POST['slot_name'] ?? null;
error_log("--- Slot Occupied API Start ---");
error_log("Received sensor_id: " . print_r($sensor_id, true) . ", slot_name: " . print_r($slot_name_from_esp, true));

if (!$sensor_id && !$slot_name_from_esp) { /* ... xử lý lỗi thiếu input ... */ exit; }

try {
    // --- Xác định slot ---
    $slot = null;
    $target_slot_name = $slot_name_from_esp ?? ("Slot " . $sensor_id);
    error_log("Target Slot Name: " . $target_slot_name);
    $stmtSlot = $pdo->prepare("SELECT slot_id, slot_name, status FROM slots WHERE slot_name = ?");
    $stmtSlot->execute([$target_slot_name]);
    $slot = $stmtSlot->fetch();
    if (!$slot) { /* ... xử lý lỗi không tìm thấy slot ... */ exit; }
    error_log("Slot Found: ID=" . $slot['slot_id'] . ", Name=" . $slot['slot_name'] . ", Status=" . $slot['status']);
    $slot_id = $slot['slot_id']; $slot_name = $slot['slot_name'];

    // --- Kiểm tra trạng thái slot ---
    if ($slot['status'] !== 'available') { /* ... xử lý lỗi slot không available ... */ exit; }

    // --- Tìm pending entry ---
    error_log("Searching for pending entry...");
    $stmtPending = $pdo->prepare("SELECT id, uid, scan_time FROM pending_entries WHERE assigned = FALSE ORDER BY scan_time DESC LIMIT 1");
    $stmtPending->execute();
    $pending_entry = $stmtPending->fetch();
    if (!$pending_entry) { /* ... xử lý lỗi không tìm thấy pending entry ... */ exit; }
    error_log("Pending Entry Found: ID=" . $pending_entry['id'] . ", UID=" . $pending_entry['uid'] . ", ScanTime=" . $pending_entry['scan_time']);
    $pending_id = $pending_entry['id']; $user_uid = $pending_entry['uid'];

    // --- Lấy thông tin user ---
    error_log("Fetching user details for UID: " . $user_uid);
    $stmtUser = $pdo->prepare("SELECT name, email FROM users WHERE uid = ?");
    $stmtUser->execute([$user_uid]);
    $user = $stmtUser->fetch();
    if(!$user) { /* ... xử lý lỗi không tìm thấy user ... */ exit; }
    error_log("User Found: Name=" . ($user['name'] ?? 'N/A') . ", Email=" . ($user['email'] ?? 'N/A'));
    $user_name = $user['name'] ?? 'Người dùng không xác định'; $user_email = $user['email'] ?? null;

    // === Thực hiện cập nhật CSDL ===
    error_log("Starting database transaction for SlotID: {$slot_id}, UserUID: {$user_uid}, PendingID: {$pending_id}");
    $pdo->beginTransaction();
    try {
        // 1. Update slots
        error_log("Updating slots table...");
        $stmtUpdateSlot = $pdo->prepare("UPDATE slots SET status = 'occupied', current_user_uid = ?, occupied_since = NOW() WHERE slot_id = ? AND status = 'available'");
        $stmtUpdateSlot->execute([$user_uid, $slot_id]);
        $rowCountSlot = $stmtUpdateSlot->rowCount();
        error_log("Slots table update rowCount: " . $rowCountSlot);
        if ($rowCountSlot !== 1) throw new Exception("Failed to update slot $slot_name (rowCount={$rowCountSlot}) or status changed.");

        // 2. Insert parking_log
        error_log("Inserting into parking_log...");
        $stmtLog = $pdo->prepare("INSERT INTO parking_log (uid, slot_id, action, user_name_snapshot) VALUES (?, ?, 'entry', ?)");
        $stmtLog->execute([$user_uid, $slot_id, $user_name]);
        error_log("parking_log inserted.");

        // 3. Update pending_entries
        error_log("Updating pending_entries...");
        $stmtUpdatePending = $pdo->prepare("UPDATE pending_entries SET assigned = TRUE, assigned_slot_id = ? WHERE id = ?"); // Giả sử đã thêm cột assigned_slot_id
        $stmtUpdatePending->execute([$slot_id, $pending_id]);
        error_log("pending_entries updated.");

        error_log("Committing transaction...");
        $pdo->commit();
        error_log("Transaction committed successfully.");

    } catch (Exception $e) {
        error_log("!!! Transaction ERROR: " . $e->getMessage() . " - Rolling back...");
        $pdo->rollBack(); throw $e;
    }

    // === Gửi Email (Sau khi commit thành công) ===
    // !!! BỎ KIỂM TRA CÀI ĐẶT ENABLE EMAIL !!!
    error_log("Email sending check: user_email=" . ($user_email ?? 'NULL'));
    if (!empty($user_email)) { // <<< Chỉ còn kiểm tra user có email không
        error_log("Attempting to send email to " . $user_email);
        try {
            sendParkingNotification($user_email, $user_name, $slot_name, 'entry');
            error_log("Notification email presumably sent successfully to {$user_email} for slot {$slot_name}.");
        } catch (Exception $mailException) {
            error_log("!!! EMAIL SENDING FAILED for user {$user_name} (Email: {$user_email}, Slot: {$slot_name}): " . $mailException->getMessage());
        }
    } else {
        error_log("Skipping email sending for user {$user_name} - No email address provided.");
    }

    // === Phản hồi thành công cho ESP32 ===
    http_response_code(200);
    $finalResponse = ['status' => 'success', /*...*/];
    error_log("Sending final JSON response: " . json_encode($finalResponse));
    echo json_encode($finalResponse);
    exit;

} catch (PDOException $e) { /* ... */ exit; }
catch (Exception $e) { /* ... */ exit; }
?>