<?php
// api/clear_history.php
require_once __DIR__ . '/../includes/db.php';

// **PHẦN KIỂM TRA ĐĂNG NHẬP & QUYỀN ĐÃ BỊ XÓA/VÔ HIỆU HÓA**
// session_start(); // Giữ lại nếu bạn có dùng session cho việc khác (ví dụ ghi log admin)
/*
if (!isset($_SESSION['user_logged_in']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin' ) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Truy cập bị từ chối. Yêu cầu quyền quản trị viên.']);
    exit;
}
*/

// Chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không được phép.']);
    exit;
}

header('Content-Type: application/json');

try {
    // === Thực hiện xóa toàn bộ lịch sử ===
    // Chọn 1 trong 2 cách: TRUNCATE (nhanh, reset ID) hoặc DELETE (chậm hơn)
    $stmt = $pdo->exec("TRUNCATE TABLE parking_log");
    // $stmt = $pdo->exec("DELETE FROM parking_log");

    if ($stmt !== false) {
        // Ghi log hành động (vẫn có thể ghi nếu session tồn tại)
        $adminIdentifier = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : (isset($_SESSION['user_id']) ? 'UserID:'.$_SESSION['user_id'] : 'Unknown User');
        error_log("Parking log cleared by: " . $adminIdentifier);

        echo json_encode(['status' => 'success', 'message' => 'Đã xóa toàn bộ lịch sử ra vào.']);
    } else {
         // Lỗi này hiếm khi xảy ra với TRUNCATE/DELETE nếu quyền DB đúng
         throw new Exception("Lệnh xóa lịch sử không thành công.");
    }

} catch (PDOException $e) {
    error_log("Database Error in clear_history.php: " . $e.getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi cơ sở dữ liệu khi xóa lịch sử.']);
} catch (Exception $e) {
    error_log("General Error in clear_history.php: " . $e.getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống khi xóa lịch sử.']);
}
?>