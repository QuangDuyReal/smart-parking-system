<?php
require_once __DIR__ . '/../includes/db.php';
// **QUAN TRỌNG: Thêm kiểm tra đăng nhập và quyền Admin ở đây!**
// Ví dụ:
// session_start();
// if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_is_admin']) {
//     http_response_code(403);
//     echo json_encode(['status' => 'error', 'message' => 'Truy cập bị từ chối']);
//     exit;
// }

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT uid, name, email FROM users ORDER BY name ASC");
    $users = $stmt->fetchAll();

    echo json_encode(['status' => 'success', 'data' => $users]);

} catch (PDOException $e) {
    error_log("Database Error in get_users.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Lỗi truy vấn danh sách người dùng.']);
}
?>