<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

try {
    // Lấy danh sách chi tiết các slot và tên người dùng nếu có
    $stmtSlots = $pdo->query("
        SELECT
            s.slot_id,
            s.slot_name,
            s.status,
            s.is_special,
            s.occupied_since,
            u.name AS user_name
        FROM slots s
        LEFT JOIN users u ON s.current_user_uid = u.uid
        ORDER BY s.slot_id ASC
    ");
    $slots = $stmtSlots->fetchAll(PDO::FETCH_ASSOC);

    // Tính toán số liệu tổng quan
    $total_slots = 0;
    $occupied_count = 0;
    $available_count = 0;
    $reserved_count = 0; // Thêm các trạng thái khác nếu có

    foreach ($slots as $slot) {
        $total_slots++;
        switch ($slot['status']) {
            case 'occupied':
                $occupied_count++;
                break;
            case 'available':
                $available_count++;
                break;
            case 'reserved': // Ví dụ
                 $reserved_count++;
                 break;
            // Thêm các case khác nếu cần
        }
    }

    // Lấy trạng thái kết nối ESP32 (ví dụ: từ bảng settings)
    $stmtStatus = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'esp32_connection_status'");
    $esp_status = $stmtStatus->fetchColumn();
    if ($esp_status === false) {
         $esp_status = 'unknown'; // Hoặc giá trị mặc định
    }

    // Chuẩn bị dữ liệu trả về
    $data = [
        'status' => 'success',
        'overview' => [
            'total' => $total_slots,
            'occupied' => $occupied_count,
            'available' => $available_count,
            'reserved' => $reserved_count, // Thêm vào nếu dùng
            'esp_connection' => $esp_status // Trạng thái kết nối ESP
        ],
        'slots' => $slots
    ];

    echo json_encode($data);

} catch (PDOException $e) {
    error_log("Database Error in get_status.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Lỗi truy vấn cơ sở dữ liệu.']);
} catch (Exception $e) {
    error_log("General Error in get_status.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống.']);
}
?>