<?php
// Không cần require config/db ở đây nữa nếu đã có ở index.php
// require_once __DIR__ . '/../config.php'; // Cần BASE_URL
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bãi Đỗ Xe Thông Minh - Dashboard</title> <?php // Tiêu đề có thể thay đổi theo trang ?>

    <!-- Bootstrap CSS (ưu tiên dùng CDN hoặc file đã tải về) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Font Awesome CSS (ưu tiên dùng CDN hoặc file đã tải về) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- CSS tùy chỉnh của bạn (phải được tạo) -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css"> <?php // Sử dụng BASE_URL nếu được định nghĩa ?>
    <!-- Nếu không có BASE_URL, dùng đường dẫn tương đối cẩn thận: -->
    <!-- <link rel="stylesheet" href="css/style.css"> -->

    <!-- Có thể thêm link Chart.js CSS nếu cần -->

</head>
<body>
    <div id="alert-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1100">
         <!-- Thông báo từ JS sẽ hiển thị ở đây -->
    </div>