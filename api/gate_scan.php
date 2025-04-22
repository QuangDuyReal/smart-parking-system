<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../config.php'; // Có thể cần nếu dùng hằng số nào đó

// API này chỉ trả về text đơn giản cho ESP32
// header('Content-Type: application/json'); // Không cần JSON cho ESP đơn giản

$uid = $_POST['uid'] ?? null;

if (!$uid) {
    http_response_code(400); // Bad Request
    echo "ERROR_MISSING_UID";
    exit;
}

try {
    // 1. Kiểm tra UID hợp lệ
    $stmtUser = $pdo->prepare("SELECT uid FROM users WHERE uid = ?");
    $stmtUser->execute([$uid]);
    $user = $stmtUser->fetch();

    if (!$user) {
        http_response_code(403); // Forbidden (or 404 Not Found)
        echo "ERROR_INVALID_UID";
        exit;
    }

    // 2. Kiểm tra chỗ trống
    $stmtCount = $pdo->query("SELECT COUNT(*) as available_count FROM slots WHERE status = 'available'");
    $available_count = $stmtCount->fetchColumn();

    if ($available_count <= 0) {
        http_response_code(429); // Too Many Requests (or custom code)
        echo "ERROR_PARKING_FULL";
        exit;
    }

    // 3. Lưu vào bảng chờ
    // Trước tiên, xóa các entry quá cũ (ví dụ: cũ hơn 15 phút) để dọn dẹp
    $cleanupTime = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $stmtCleanup = $pdo->prepare("DELETE FROM pending_entries WHERE assigned = FALSE AND scan_time < ?");
    $stmtCleanup->execute([$cleanupTime]);

    // Thêm entry mới
    $stmtPending = $pdo->prepare("INSERT INTO pending_entries (uid) VALUES (?)");
    $stmtPending->execute([$uid]);

    // 4. Phản hồi thành công cho ESP32
    http_response_code(200);
    echo "OK_OPEN_BARRIER"; // Hoặc chỉ "OK"

} catch (PDOException $e) {
    error_log("Database Error in gate_scan.php: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo "ERROR_SERVER_DB";
} catch (Exception $e) {
    error_log("General Error in gate_scan.php: " . $e->getMessage());
    http_response_code(500);
    echo "ERROR_SERVER_GENERAL";
}
?>