<?php
// includes/footer.php
$appVersion = defined('APP_VERSION') ? APP_VERSION : '1.0.0'; // Có giá trị mặc định
$systemName = 'Smart Parking System'; // Hoặc lấy từ DB nếu muốn $systemName = $settings_raw['system_name'] ?? 'Smart Parking System'; (Cần lấy $settings_raw)
$contactEmail = 'iot.parkingmanager@gmail.com'; // Hoặc lấy từ DB $contactEmail = $settings_raw['notification_email'] ?? '';

// Mảng tên tác giả và MSSV
$authors = [
    ['name' => 'Nguyen Van Quang Duy', 'mssv' => '23110086'],
    ['name' => 'Vo Nguyen Quynh Nhu', 'mssv' => '23162074'],
    ['name' => 'Le Truong Hong Phuoc', 'mssv' => '23162077'],
];

?>
    <!-- *** BẮT ĐẦU FOOTER TÙY CHỈNH *** -->
    <footer class="footer mt-auto py-3 bg-dark text-white-50"> <?php // Nền tối, chữ màu xám nhạt ?>
        <div class="container text-center">
            <p class="mb-1"> <?php // Giảm margin bottom ?>
                <strong><?php echo htmlspecialchars($systemName); ?></strong> -
                <?php if ($contactEmail): ?>
                    Contact: <a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>" class="text-white-50"><?php echo htmlspecialchars($contactEmail); ?></a>
                <?php endif; ?>
            </p>
             <p class="mb-2 small"> <?php // Kích thước nhỏ hơn, margin bottom ?>
                 <?php
                    $authorLinks = [];
                    foreach ($authors as $author) {
                         // Tạo link cho từng tác giả nếu muốn, hoặc chỉ hiển thị text
                         $authorLinks[] = htmlspecialchars($author['name']) . ' (' . htmlspecialchars($author['mssv']) . ')';
                    }
                    echo implode(' - ', $authorLinks); // Nối các tên bằng dấu gạch ngang
                 ?>
            </p>
            <p class="small mb-0"> <?php // Margin bottom nhỏ nhất ?>
                © <?php echo date('Y'); ?> HCMC University of Technology and Education - Faculty of Information Technology
            </p>
        </div>
    </footer>
    <!-- *** KẾT THÚC FOOTER TÙY CHỈNH *** -->

    <!-- Các thẻ <script> -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?php echo BASE_URL; ?>js/app.js" defer></script>

</body>
</html>