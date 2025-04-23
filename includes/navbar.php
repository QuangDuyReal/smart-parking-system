<?php
// includes/navbar.php (Phương án 1)
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid">

        <!-- 1. Logo bên trái (Giữ nguyên) -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>index.php">
             <?php /* Có thể thêm class d-flex và align-items-center để logo và text căn tốt hơn nếu có text */ ?>
            <img src="<?php echo BASE_URL; ?>assets/logo_truong.png" alt="HCMUTE Logo" width="30" height="30" class="d-inline-block align-text-top me-2"> <?php /* Giảm kích thước logo 1 chút, thêm me-2 */ ?>
            <span class="d-none d-sm-inline"> <?php // Tên trường có thể ẩn trên màn rất nhỏ ?> HCMUTE</span>
        </a>

        <!-- 2. Tiêu đề căn giữa (Bỏ class ẩn trên mobile) -->
        <div class="navbar-text text-white fw-bold fs-5 mx-auto my-2 my-lg-0 order-0 order-lg-1"> <?php /* Bỏ d-none d-lg-block, có thể giảm fs-4 thành fs-5 */ ?>
             Smart Parking System
        </div>

        <!-- 3. Nút Toggler (Vẫn giữ để responsive nếu cần sau này, hoặc để giữ bố cục) -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavContent" aria-controls="navbarNavContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- 4. Phần Collapse (Giờ có thể trống hoặc chứa bản sao của logo/title cho mobile nếu muốn) -->
        <div class="collapse navbar-collapse order-1 order-lg-2" id="navbarNavContent">
             <?php /* Phần này giờ không có ul chứa link Cài đặt/Đăng xuất nữa */ ?>
             <?php /* Có thể để trống, hoặc thêm lại logo/title nếu muốn hiển thị khác trên mobile */ ?>
        </div>

    </div>
</nav>