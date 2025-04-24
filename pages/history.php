<!-- pages/history.php -->
<div id="history-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-history me-2"></i> Lịch sử Ra Vào</h1>
        
        <?php // --- NÚT XÓA LỊCH SỬ --- ?>
        <button type="button" class="btn btn-danger" id="clearHistoryBtn">
            <i class="fas fa-trash-alt me-2"></i> Xóa Toàn Bộ Lịch Sử
        </button>
        <?php // --- KẾT THÚC NÚT XÓA --- ?>
    </div>

    <!-- Form Filter (Cần hoàn thiện form này nếu chưa có) -->
    <form id="history-filters" class="row g-3 align-items-end mb-4 p-3 bg-light rounded border">
        <div class="col-md-4">
            <label for="history-search" class="form-label">Tìm theo UID / Tên</label>
            <input type="text" class="form-control" id="history-search" placeholder="Nhập UID hoặc Tên...">
        </div>
        <div class="col-md-3">
            <label for="history-slot-filter" class="form-label">Lọc theo Slot</label>
            <select id="history-slot-filter" class="form-select">
                <option value="">-- Tất cả Slot --</option>
                <?php
                // Tùy chọn: Load danh sách slot từ DB để tạo options
                // try {
                //     $stmtSlots = $pdo->query("SELECT slot_id, slot_name FROM slots ORDER BY slot_id");
                //     while ($slot = $stmtSlots->fetch()) {
                //         echo "<option value='{$slot['slot_id']}'>" . htmlspecialchars($slot['slot_name']) . "</option>";
                //     }
                // } catch (PDOException $e) { /* Xử lý lỗi */ }
                 echo "<option value='1'>Slot 1</option>"; // Ví dụ
                 echo "<option value='2'>Slot 2</option>";
                 echo "<option value='3'>Slot 3</option>";
                 echo "<option value='4'>Slot 4</option>";
                ?>
            </select>
        </div>
         <div class="col-md-2">
            <label for="history-start-date" class="form-label">Từ ngày</label>
            <input type="date" class="form-control" id="history-start-date">
        </div>
         <div class="col-md-2">
            <label for="history-end-date" class="form-label">Đến ngày</label>
            <input type="date" class="form-control" id="history-end-date">
        </div>
        <div class="col-md-1">
             <button type="submit" class="btn btn-primary w-100">Lọc</button>
        </div>
         <div class="col-12">
              <div id="history-loading" class="text-center py-2" style="display: none;">
                  <span class="spinner-border spinner-border-sm text-primary me-2" role="status"></span> Đang tải...
              </div>
         </div>
    </form>
    <!-- Kết thúc Form Filter -->


    <div class="table-responsive shadow-sm rounded">
        <table class="table table-striped table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col">ID Log</th>
                    <th scope="col">UID</th>
                    <th scope="col">Tên Người dùng</th>
                    <th scope="col">Slot</th>
                    <th scope="col">Thời gian</th>
                    <th scope="col">Hành động</th>
                </tr>
            </thead>
            <tbody id="historyTableBody">
                <!-- Dữ liệu lịch sử sẽ được JS load vào đây -->
                 <tr><td colspan="6" class="text-center p-4"><span class="spinner-border spinner-border-sm me-2"></span> Đang tải...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Phân trang (nếu có) -->
    <div id="history-pagination" class="mt-4"></div>

</div>