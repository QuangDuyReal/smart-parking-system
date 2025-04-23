<?php
require_once __DIR__ . '/../includes/db.php';
// **QUAN TRỌNG: Thêm kiểm tra đăng nhập và quyền Admin ở đây!**
// session_start(); ... (kiểm tra quyền tương tự get_users.php)

header('Content-Type: application/json');

// Lấy dữ liệu từ POST (thường là FormData từ JS)
$uid = $_POST['uid'] ?? null;
$name = $_POST['name'] ?? null;
$email = $_POST['email'] ?? null; // Email có thể trống

// --- Validation cơ bản ---
if (empty($uid) || empty($name)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'UID và Tên người dùng không được để trống.']);
    exit;
}

// Làm sạch email, nếu không hợp lệ thì đặt là NULL
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
     $email = null; // Hoặc báo lỗi nếu email bắt buộc phải đúng định dạng
     // echo json_encode(['status' => 'error', 'message' => 'Địa chỉ email không hợp lệ.']);
     // exit;
} elseif (empty($email)) {
    $email = null;
}


try {
    // 1. Kiểm tra UID đã tồn tại chưa?
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE uid = ?");
    $stmtCheck->execute([$uid]);
    if ($stmtCheck->fetchColumn() > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['status' => 'error', 'message' => 'UID này đã tồn tại trong hệ thống.']);
        exit;
    }

    // 2. Thêm người dùng mới
    $stmtInsert = $pdo->prepare("INSERT INTO users (uid, name, email) VALUES (?, ?, ?)");
    $success = $stmtInsert->execute([$uid, $name, $email]);

    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Thêm người dùng thành công.']);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['status' => 'error', 'message' => 'Không thể thêm người dùng vào cơ sở dữ liệu.']);
    }

} catch (PDOException $e) {
    error_log("Database Error in add_user.php: " . $e->getMessage());
    http_response_code(500);
    // Trả về lỗi chung chung hơn ở production
    echo json_encode(['status' => 'error', 'message' => 'Lỗi cơ sở dữ liệu khi thêm người dùng.']);
}
?>