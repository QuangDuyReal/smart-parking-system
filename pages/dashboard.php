<?php
// Trang này được require bởi index.php, nên có thể dùng $pdo nếu cần
?>
<div id="dashboard-container"> <?php // Container cho JS nhận diện ?>
    <h1 class="mb-4">Bảng điều khiển</h1>

     <!-- Khu vực thông báo (sẽ được điền bởi showAlert trong JS) -->
     <!-- <div id="alert-container" class="mb-3"></div> -->
     <?php // Alert container đã chuyển ra header.php để cố định trên cùng ?>


    <!-- Thông tin tổng quan -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-car me-2"></i> Tổng số chỗ</h5>
                    <p class="card-text fs-4 fw-bold" id="total-slots">...</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
             <div class="card text-white bg-danger">
                 <div class="card-body">
                     <h5 class="card-title"><i class="fas fa-times-circle me-2"></i> Đã đỗ</h5>
                     <p class="card-text fs-4 fw-bold" id="occupied-slots">...</p>
                 </div>
             </div>
         </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-check-circle me-2"></i> Còn trống</h5>
                    <p class="card-text fs-4 fw-bold" id="available-slots">...</p>
                </div>
            </div>
        </div>
         <div class="col-md-3">
             <div class="card text-dark bg-light"> <?php // Thẻ trạng thái kết nối ?>
                 <div class="card-body">
                     <h5 class="card-title"><i class="fas fa-wifi me-2"></i> Kết nối</h5>
                      <p class="card-text fw-bold" id="esp-connection-status">...</p>
                 </div>
             </div>
         </div>
    </div>

    <!-- Khu vực hiển thị các slot -->
    <h2>Trạng thái các vị trí đỗ</h2>
    <div class="row mb-4" id="slots-container">
        <!-- Các div slot sẽ được JS tự động thêm vào đây -->
        <div class="col-12 text-center p-5">
            <span class="spinner-border text-primary" role="status"></span>
            <p>Đang tải trạng thái các vị trí...</p>
        </div>
    </div>

    <!-- Biểu đồ và Nút Barrier -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Biểu đồ tỉ lệ
                </div>
                <div class="card-body" style="height: 300px;"> <?php // Set chiều cao cho canvas ?>
                    <canvas id="parkingChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
             <div class="card">
                 <div class="card-header">
                     Điều khiển thủ công
                 </div>
                 <div class="card-body text-center">
                      <p>Mở barrier cổng vào từ xa.</p>
                      <button id="openBarrierBtn" class="btn btn-warning btn-lg">
                          <i class="fas fa-door-open me-2"></i> Mở Barrier Thủ Công
                      </button>
                 </div>
             </div>
        </div>
    </div>
</div>