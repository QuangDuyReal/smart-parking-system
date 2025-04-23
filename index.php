<?php
// index.php

// Bắt đầu session nếu cần quản lý đăng nhập
session_start();

// --- Ví dụ Kiểm tra Đăng nhập ---
// Bỏ comment và tùy chỉnh logic kiểm tra của bạn
/*
if (!isset($_SESSION['user_logged_in'])) {
    // Có thể lưu lại trang định đến để redirect sau khi login
    // $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php'); // Chuyển đến trang đăng nhập
    exit;
}
*/
// --- Kết thúc Kiểm tra Đăng nhập ---


// Nạp cấu hình và kết nối DB
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php'; // Giả sử db.php tạo biến $pdo

// Nạp phần đầu HTML (<head>, bắt đầu <body>, container alert toàn cục)
require_once __DIR__ . '/includes/header.php';

// Nạp thanh điều hướng trên cùng (Navbar)
require_once __DIR__ . '/includes/navbar.php';
?>

<?php // Container chính sử dụng Flexbox để sắp xếp Sidebar và Main Content ?>
<div class="d-flex main-layout">

    <?php
    // Nạp thanh điều hướng bên trái (Sidebar)
    require_once __DIR__ . '/includes/sidebar.php';
    ?>

    <?php // Phần nội dung chính, cho phép cuộn độc lập ?>
    <main class="flex-grow-1 p-3 main-content">
        <?php
        // Định tuyến đơn giản dựa trên tham số 'page'
        $page = $_GET['page'] ?? 'dashboard'; // Trang mặc định

        // Danh sách các trang hợp lệ (quan trọng để bảo mật)
        $allowed_pages = ['dashboard', 'history', 'settings']; // Thêm các trang khác nếu có

        if (in_array($page, $allowed_pages)) {
            $page_file = __DIR__ . "/pages/{$page}.php"; // Đường dẫn đến file trang
            if (file_exists($page_file)) {
                // Đặt tiêu đề trang động (ví dụ)
                $pageTitle = ucfirst($page); // Dashboard, History, Settings
                require_once $page_file; // Nạp file nội dung trang
            } else {
                // Hiển thị lỗi nếu file trang không tồn tại
                 $pageTitle = "Lỗi 404";
                echo '<div class="alert alert-danger" role="alert">';
                echo '<h4>Lỗi 404 - Không tìm thấy trang</h4>';
                echo '<p>File trang yêu cầu <code>' . htmlspecialchars($page_file) . '</code> không tồn tại trên server.</p>';
                echo '</div>';
            }
        } else {
             // Nếu 'page' không hợp lệ, hiển thị trang dashboard mặc định
             $default_page_file = __DIR__ . '/pages/dashboard.php';
             if (file_exists($default_page_file)){
                  $pageTitle = "Dashboard";
                 require_once $default_page_file;
             } else {
                   $pageTitle = "Lỗi";
                   echo '<div class="alert alert-danger" role="alert">Lỗi nghiêm trọng: Không tìm thấy file trang dashboard mặc định!</div>';
             }
        }
        ?>
    </main> <?php // Kết thúc thẻ <main> ?>

</div> <?php // Kết thúc thẻ <div class="d-flex main-layout"> ?>

<?php
// Nạp phần chân trang (Footer) - Nằm ngoài layout chính để chỉ xuất hiện khi cuộn hết main
require_once __DIR__ . '/includes/footer.php';
?>