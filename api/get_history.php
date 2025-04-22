<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

try {
    // --- Base SQL ---
    $sql = "SELECT
                pl.log_id,
                pl.uid,
                u.name AS user_name,
                pl.slot_id,
                s.slot_name,
                pl.action,
                pl.timestamp
            FROM parking_log pl
            LEFT JOIN users u ON pl.uid = u.uid
            LEFT JOIN slots s ON pl.slot_id = s.slot_id
            WHERE 1=1"; // Mệnh đề WHERE luôn đúng để dễ nối AND

    // Mảng lưu các điều kiện WHERE động (chỉ để debug nếu cần)
    // $conditions = []; // Không cần thiết cho việc bind

    // --- Chuẩn bị cho binding ---
    // Không cần mảng $params nữa nếu bind trực tiếp
    // $params = [];


    // --- Xử lý các bộ lọc và xây dựng SQL động ---

    // (Phần này giữ nguyên logic thêm AND ...)

    // --- Chuẩn bị câu lệnh NGAY TRƯỚC khi bind ---
    // Làm vậy để biết chắc chắn có bao nhiêu token (?) trước khi bind
    // Tách riêng phần filter khỏi phần LIMIT/OFFSET

    $filterConditionsSQL = ""; // Chuỗi chứa các điều kiện AND
    $filterParams = []; // Mảng chứa giá trị cho các filter

    // Lọc theo UID hoặc Tên người dùng
    if (!empty($_GET['search'])) {
        $searchTerm = '%' . $_GET['search'] . '%';
        $filterConditionsSQL .= " AND (pl.uid LIKE ? OR u.name LIKE ?)";
        $filterParams[] = $searchTerm;
        $filterParams[] = $searchTerm;
    }

    // Lọc theo Slot ID
    if (!empty($_GET['slot_id']) && is_numeric($_GET['slot_id'])) {
        $filterConditionsSQL .= " AND pl.slot_id = ?";
        $filterParams[] = (int)$_GET['slot_id'];
    }

    // Lọc theo hành động
    if (!empty($_GET['action']) && in_array($_GET['action'], ['entry', 'exit'])) {
        $filterConditionsSQL .= " AND pl.action = ?";
        $filterParams[] = $_GET['action'];
    }

    // Lọc theo khoảng thời gian
    if (!empty($_GET['start_date'])) {
        $filterConditionsSQL .= " AND pl.timestamp >= ?";
        $filterParams[] = $_GET['start_date'];
    }
    if (!empty($_GET['end_date'])) {
        $filterConditionsSQL .= " AND pl.timestamp <= ?";
        $filterParams[] = $_GET['end_date'];
    }

    // Nối các điều kiện filter vào câu SQL chính
    $sql .= $filterConditionsSQL;

    // Sắp xếp (luôn có)
    $sql .= " ORDER BY pl.timestamp DESC";

    // Phân trang (luôn có)
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 25;
    $offset = ($page - 1) * $limit;

    // Thêm LIMIT và OFFSET vào SQL
    $sql .= " LIMIT ? OFFSET ?"; // Thêm 2 placeholder cuối

    // === Thực thi truy vấn với binding rõ ràng ===
    $stmt = $pdo->prepare($sql);

    // Bind các giá trị filter trước
    $paramIndex = 1;
    foreach ($filterParams as $value) {
         // Xác định kiểu dữ liệu nếu cần, ví dụ:
         if (is_int($value)) {
             $stmt->bindValue($paramIndex++, $value, PDO::PARAM_INT);
         } elseif (is_string($value)) {
             $stmt->bindValue($paramIndex++, $value, PDO::PARAM_STR);
         } else {
             // Kiểu khác (NULL, boolean...)
              $stmt->bindValue($paramIndex++, $value); // Để PDO tự quyết định
         }
    }

    // Bind LIMIT và OFFSET với kiểu INT
    $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);

    // Thực thi câu lệnh
    $stmt->execute(); // Không cần truyền tham số vào đây nữa
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Code lấy tổng số bản ghi (nếu cần) ---
    // ...

    echo json_encode([
        'status' => 'success',
        'data' => $history,
        // 'pagination' => ...
    ]);


} catch (PDOException $e) {
    // Ghi log lỗi chi tiết hơn để dễ debug
    error_log("Database Error in get_history.php: " . $e->getMessage() . " | SQL: " . ($sql ?? 'Not available') . " | Filter Params: " . json_encode($filterParams ?? []) . " | Limit: " . ($limit ?? 'N/A') . " | Offset: " . ($offset ?? 'N/A') );
    // Trả về lỗi chung chung cho client
    echo json_encode(['status' => 'error', 'message' => 'Lỗi truy vấn cơ sở dữ liệu lịch sử.']);
} catch (Exception $e) {
    error_log("General Error in get_history.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống khi lấy lịch sử.']);
}
?>