<!-- pages/settings.php -->
<div id="settings-container">
    <!-- ================================================== -->
    <!--                 Cài đặt chung                    -->
    <!-- ================================================== -->
    <form id="general-settings-form" class="mb-5 card card-body shadow-sm">
        <h2 class="mb-4"><i class="fas fa-cogs me-2"></i> Cài đặt chung</h2>

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="setting-system-name" class="form-label">Tên hệ thống / Bãi đỗ xe <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="setting-system-name" name="system_name" required placeholder="Ví dụ: Bãi đỗ xe HCMUTE">
                </div>
            </div>
            <div class="col-md-6">
                 <div class="mb-3">
                    <label for="setting-esp32-ip" class="form-label">Địa chỉ IP ESP32 Cổng</label>
                    <input type="text" class="form-control" id="setting-esp32-ip" name="esp32_gate_ip" placeholder="Ví dụ: 192.168.1.100" aria-describedby="ipHelp">
                    <div id="ipHelp" class="form-text">IP để gửi lệnh mở barrier từ xa. Để trống nếu không dùng.</div>
                 </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="setting-notification-email" class="form-label">Email nhận thông báo hệ thống</label>
            <input type="email" class="form-control" id="setting-notification-email" name="notification_email" placeholder="admin@example.com" aria-describedby="emailNotifyHelp">
             <div id="emailNotifyHelp" class="form-text">Email quản trị viên nhận cảnh báo lỗi, v.v. (nếu có).</div>
        </div>

        <!-- KHỐI CHECKBOX GỬI EMAIL ĐÃ BỊ XÓA -->

        <div class="text-end">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i> Lưu Cài đặt chung
            </button>
        </div>
    </form>

    <hr class="my-5">

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

        <div class="table-responsive shadow-sm rounded">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">UID (Mã thẻ)</th>
                        <th scope="col">Tên người dùng</th>
                        <th scope="col">Email</th>
                        <th scope="col" class="text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody id="user-table-body">
                    <tr>
                        <td colspan="4" class="text-center p-4">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Đang tải danh sách người dùng...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Phần quản lý vị trí đỗ (tạm ẩn) -->
    <!-- <hr class="my-5"> ... -->

</div>

<!-- ================================================== -->
<!--          Modal Thêm người dùng (Add User)          -->
<!-- ================================================== -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="add-user-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Thêm người dùng mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                     <div id="add-user-alert-container"></div>
                     <div class="mb-3">
                        <label for="add-user-uid" class="form-label">UID (Mã thẻ RFID) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add-user-uid" name="uid" required placeholder="Quét thẻ hoặc nhập mã thủ công">
                        <div class="form-text">Mã định danh duy nhất của thẻ RFID.</div>
                    </div>
                    <div class="mb-3">
                        <label for="add-user-name" class="form-label">Tên người dùng <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add-user-name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="add-user-email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="add-user-email" name="email" placeholder="vidu@email.com">
                        <div class="form-text">Quan trọng: Dùng để gửi thông báo vị trí đỗ.</div>
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

<!-- TODO: Thêm Modal Sửa người dùng nếu cần -->