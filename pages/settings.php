<!-- pages/settings.php -->
<div id="settings-container">
    <!-- ================================================== -->
    <!--             Cài đặt chung (ví dụ)                 -->
    <!-- ================================================== -->
    <form id="general-settings-form" class="mb-5">
        <h2><i class="fas fa-cogs me-2"></i> Cài đặt chung</h2>
        <div class="mb-3">
            <label for="setting-esp32-ip" class="form-label">Địa chỉ IP ESP32 Cổng</label>
            <input type="text" class="form-control" id="setting-esp32-ip" name="esp32_gate_ip" placeholder="Ví dụ: 192.168.1.100" aria-describedby="ipHelp">
            <div id="ipHelp" class="form-text">Địa chỉ IP để gửi lệnh mở barrier từ xa.</div>
        </div>
        <div class="mb-3">
            <label for="setting-notification-email" class="form-label">Email nhận thông báo hệ thống</label>
            <input type="email" class="form-control" id="setting-notification-email" name="notification_email" placeholder="admin@example.com">
        </div>
        <!-- Thêm các input khác cho cài đặt chung nếu cần -->
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-2"></i> Lưu Cài đặt chung
        </button>
    </form>

    <hr>

    <!-- ================================================== -->
    <!--              Quản lý người dùng                   -->
    <!-- ================================================== -->
    <div id="user-management-section" class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2><i class="fas fa-users me-2"></i> Quản lý người dùng</h2>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-plus me-2"></i> Thêm người dùng
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th scope="col">UID (Mã thẻ)</th>
                        <th scope="col">Tên người dùng</th>
                        <th scope="col">Email</th>
                        <th scope="col">Hành động</th>
                    </tr>
                </thead>
                <tbody id="user-table-body">
                    <!-- Dữ liệu user sẽ được JS load vào đây -->
                    <tr>
                        <td colspan="4" class="text-center">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            Đang tải danh sách người dùng...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Thêm các phần quản lý khác (Slot, Lịch sử...) ở đây nếu muốn -->
    <hr>
     <h2><i class="fas fa-parking me-2"></i> Quản lý Vị trí đỗ</h2>
     <p class="text-muted">Chức năng quản lý vị trí đỗ xe sẽ được thêm vào sau.</p>
     <!-- Tương tự, bạn sẽ thêm bảng và modal cho Slot ở đây -->

</div>

<!-- ================================================== -->
<!--          Modal Thêm người dùng (Add User)          -->
<!-- ================================================== -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="add-user-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Thêm người dùng mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                     <!-- Vùng hiển thị lỗi trong modal -->
                     <div id="add-user-alert-container"></div>

                    <div class="mb-3">
                        <label for="add-user-uid" class="form-label">UID (Mã thẻ RFID) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add-user-uid" name="uid" required>
                        <div class="form-text">Nhập mã duy nhất của thẻ RFID.</div>
                    </div>
                    <div class="mb-3">
                        <label for="add-user-name" class="form-label">Tên người dùng <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add-user-name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="add-user-email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="add-user-email" name="email">
                        <div class="form-text">Không bắt buộc. Dùng để gửi thông báo.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                         <i class="fas fa-plus me-2"></i> Thêm người dùng
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- (Tùy chọn) Modal Sửa người dùng (Edit User Modal) -->
<!-- Bạn sẽ cần tạo cấu trúc tương tự như Add User Modal, với ID khác (vd: #editUserModal) -->
<!-- và thêm một input hidden để chứa UID cần sửa -->