<?php
// includes/sidebar.php
$currentPage = $_GET['page'] ?? 'dashboard'; // Lấy trang hiện tại để active link
?>

<?php // Thêm class 'sidebar' vào đây ?>
<div class="sidebar d-flex flex-column flex-shrink-0 p-3 bg-light vh-100" style="width: 280px;">
    <?php // vh-100 làm sidebar cao hết màn hình ?>

    <ul class="nav nav-pills flex-column mb-auto"> <?php // mb-auto đẩy phần tử bên dưới xuống đáy ?>
        <li class="nav-item mb-1"> <?php // Thêm khoảng cách nhỏ giữa các item ?>
            <a href="<?php echo BASE_URL; ?>index.php?page=dashboard" class="nav-link <?php echo ($currentPage === 'dashboard') ? 'active' : 'link-dark'; ?>" aria-current="<?php echo ($currentPage === 'dashboard') ? 'page' : 'false'; ?>">
                <i class="fas fa-tachometer-alt fa-fw me-2"></i> <?php // fa-fw căn chỉnh icon đẹp hơn ?>
                Tổng quan
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="<?php echo BASE_URL; ?>index.php?page=history" class="nav-link <?php echo ($currentPage === 'history') ? 'active' : 'link-dark'; ?>" aria-current="<?php echo ($currentPage === 'history') ? 'page' : 'false'; ?>">
                <i class="fas fa-history fa-fw me-2"></i>
                Lịch sử vào ra
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="<?php echo BASE_URL; ?>index.php?page=settings" class="nav-link <?php echo ($currentPage === 'settings') ? 'active' : 'link-dark'; ?>" aria-current="<?php echo ($currentPage === 'settings') ? 'page' : 'false'; ?>">
                <i class="fas fa-users-cog fa-fw me-2"></i>
                Cài đặt
            </a>
        </li>
         <!-- Thêm các mục menu khác nếu cần -->
         <!--
         <li class="nav-item mb-1">
             <a href="#" class="nav-link link-dark">
                 <i class="fas fa-exclamation-triangle fa-fw me-2"></i>
                 Cảnh báo
             </a>
         </li>
         -->
    </ul>

    <hr>
    <?php // Ví dụ thêm thông tin người dùng đăng nhập ở cuối ?>
    <!--
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center link-dark text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="https://github.com/mdo.png" alt="" width="32" height="32" class="rounded-circle me-2">
            <strong><?php // echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></strong>
        </a>
        <ul class="dropdown-menu text-small shadow" aria-labelledby="dropdownUser">
            <li><a class="dropdown-item" href="#">Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php // echo BASE_URL; ?>logout.php">Sign out</a></li>
        </ul>
    </div>
     -->
     <div class="text-center text-muted small">
          <?php // Có thể thêm thông tin phiên bản hoặc tên hệ thống nhỏ ở đây ?>
     </div>
</div>