<?php
require_once __DIR__ . '/../includes/db.php';
// **QUAN TRỌNG: Thêm kiểm tra đăng nhập và quyền Admin ở đây!**
// session_start(); ... (kiểm tra quyền tương tự get_users.php)

header('Content-Type: application/json');

// Lấy UID từ body của request POST (gửi dưới dạng JSON từ JS)
$input = json_decode(file_get_contents('php://input'), true);
$uid = $input['uid'] ?? null;

if (empty($uid)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu UID của người dùng cần xóa.']);
    exit;
}

try {
    // **Cân nhắc:** Kiểm tra xem user có đang đỗ xe không trước khi xóa?
    // $stmtCheckSlot = $pdo->prepare("SELECT COUNT(*) FROM slots WHERE current_user_uid = ?");
    // $stmtCheckSlot->execute([$uid]);
    // if ($stmtCheckSlot->fetchColumn() > 0) {
    //     http_response_code(409); // Conflict
    //     echo json_encode(['status' => 'error', 'message' => 'Người dùng này đang đỗ xe, không thể xóa.']);
    //     exit;
    // }

    // Thực hiện xóa
    $stmtDelete = $pdo->prepare("DELETE FROM users WHERE uid = ?");
    $rowCount = $stmtDelete->execute([$uid]); // execute trả về true/false, không phải số dòng trên mọi driver

    // Kiểm tra xem có dòng nào bị ảnh hưởng không (cách này chuẩn hơn)
    if ($stmtDelete->rowCount() > 0) {
         echo json_encode(['status' => 'success', 'message' => 'Xóa người dùng thành công.']);
    } else {
         http_response_code(404); // Not Found
         echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy người dùng với UID này để xóa.']);
    }

} catch (PDOException $e) {
    error_log("Database Error in delete_user.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi cơ sở dữ liệu khi xóa người dùng.']);
}
?>