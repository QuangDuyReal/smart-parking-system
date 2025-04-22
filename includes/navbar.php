<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid">

        <!-- 1. Logo bên trái -->
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>index.php">
            <img src="<?php echo BASE_URL; ?>assets/logo_truong.png" alt="HCMUTE" width="80" height="80" class="d-inline-block align-middle"> <?php /* Tăng kích thước logo, align-middle để căn giữa dọc tốt hơn */ ?>
        </a>

        <!-- 2. Tiêu đề căn giữa (Chỉ hiển thị trên màn hình lớn) -->
        <div class="mx-auto my-2 my-lg-0 order-0 order-lg-1"> <?php /* mx-auto để căn giữa ngang, order để định vị trí khi collapse */?>
             <span class="navbar-text text-white fw-bold fs-4 d-none d-lg-block"> <?php /* Thêm class để style và ẩn trên mobile */ ?>
                 Smart Parking System
             </span>
             <?php /* Trên mobile, có thể hiển thị title này trong menu collapse nếu muốn */ ?>
        </div>


        <!-- 3. Nút Toggler (Vẫn giữ nguyên vị trí bootstrap mặc định cho mobile) -->
         <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
             <span class="navbar-toggler-icon"></span>
         </button>

        <!-- 4. Links bên phải (trong collapse) -->
        <div class="collapse navbar-collapse order-1 order-lg-2" id="navbarNav"> <?php /* order để định vị trí khi collapse */ ?>
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0"> <?php /* ms-auto đẩy sang phải */ ?>
                <li class="nav-item">
                     <?php // Ví dụ: Hiển thị tên người dùng đã đăng nhập ?>
                     <!-- <span class="navbar-text me-3">Chào, <?php // echo htmlspecialchars($_SESSION['user_name'] ?? 'Guest'); ?></span> -->
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>index.php?page=settings"> <i class="fas fa-cog"></i> Cài đặt</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>logout.php"> <i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </li>
            </ul>
        </div>

    </div>
</nav>