<?php $currentPage = $_GET['page'] ?? 'dashboard'; // Lấy trang hiện tại để active link ?>

<div class="d-flex flex-column flex-shrink-0 p-3 bg-light vh-100" style="width: 280px;"> <?php // vh-100 làm sidebar cao hết màn hình ?>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>index.php?page=dashboard" class="nav-link <?php echo ($currentPage === 'dashboard') ? 'active' : 'link-dark'; ?>">
                <i class="fas fa-tachometer-alt me-2"></i>
                Tổng quan
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>index.php?page=history" class="nav-link <?php echo ($currentPage === 'history') ? 'active' : 'link-dark'; ?>">
                <i class="fas fa-history me-2"></i>
                Lịch sử vào ra
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>index.php?page=settings" class="nav-link <?php echo ($currentPage === 'settings') ? 'active' : 'link-dark'; ?>">
                <i class="fas fa-users-cog me-2"></i>
                Cài đặt
            </a>
        </li>
         <!-- Thêm các mục menu khác nếu cần -->
         <!--
         <li>
             <a href="#" class="nav-link link-dark">
                 <i class="fas fa-exclamation-triangle me-2"></i>
                 Cảnh báo
             </a>
         </li>
         -->
    </ul>
    <hr>
    <div class="text-center text-muted small">
    </div>
</div>