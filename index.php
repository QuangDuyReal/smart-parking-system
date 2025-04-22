<?php
// (Có thể thêm session_start() nếu cần quản lý đăng nhập)
session_start();

// Kiểm tra đăng nhập (Ví dụ cơ bản - Cần làm phức tạp hơn)
// if (!isset($_SESSION['user_logged_in'])) {
//     // Chuyển hướng đến trang đăng nhập nếu chưa login
//     // header('Location: login.php');
//     // exit;
// }

// Nạp cấu hình và kết nối DB (có thể cần ở đây hoặc trong header/page cụ thể)
// !!! QUAN TRỌNG: Đảm bảo các file này tồn tại và đúng đường dẫn !!!
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

// Phần đầu trang (quan trọng)
require_once __DIR__ . '/includes/header.php';

// Thanh điều hướng trên cùng (quan trọng)
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="d-flex"> <?php // Sử dụng Flexbox của Bootstrap để xếp sidebar và content ?>
    <?php
    // Thanh điều hướng bên trái (quan trọng)
    require_once __DIR__ . '/includes/sidebar.php';
    ?>

    <main class="flex-grow-1 p-3"> <?php // Phần nội dung chính ?>
        <div id="alert-container" class="position-sticky top-0" style="z-index: 1050">
            <?php // Vùng chứa thông báo chung, có thể di chuyển nếu muốn ?>
        </div>
        <?php
        // === Định tuyến đơn giản dựa trên tham số 'page' ===
        $page = $_GET['page'] ?? 'dashboard'; // Trang mặc định là dashboard

        // Danh sách các trang hợp lệ
        $allowed_pages = ['dashboard', 'history', 'settings'];

        if (in_array($page, $allowed_pages)) {
            $page_file = __DIR__ . "/pages/{$page}.php";
            if (file_exists($page_file)) {
                require_once $page_file; // Nạp file nội dung trang tương ứng
            } else {
                // Hiển thị lỗi 404 nếu file không tồn tại
                echo '<div class="alert alert-danger">Lỗi: File trang '.htmlspecialchars($page_file).' không tìm thấy!</div>';
            }
        } else {
             // Hiển thị trang dashboard mặc định nếu page không hợp lệ hoặc không được cung cấp
             $default_page_file = __DIR__ . '/pages/dashboard.php';
             if (file_exists($default_page_file)){
                 require_once $default_page_file;
             } else {
                  echo '<div class="alert alert-danger">Lỗi: File trang dashboard mặc định không tìm thấy!</div>';
             }
        }
        ?>
    </main>
</div>

<?php
// Phần chân trang (quan trọng)
require_once __DIR__ . '/includes/footer.php';
?>