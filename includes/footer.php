<?php
// includes/footer.php

// Giả sử bạn có biến $appVersion trong config.php
// define('APP_VERSION', '1.0.2');
$appVersion = defined('APP_VERSION') ? APP_VERSION : '';
?>

    <?php // Phần này nằm sau thẻ </main> trong index.php ?>

    <!-- *** BẮT ĐẦU FOOTER TÙY CHỈNH *** -->
    <footer class="footer mt-auto py-3 bg-light border-top"> <?php // Thêm class border-top ?>
        <div class="container text-center">
            <span class="text-muted">
                HCMC University of Technology and Education - Faculty of Information Technology .
                <?php if ($appVersion) : ?>
                     | Phiên bản: <?php echo htmlspecialchars($appVersion); ?>
                <?php endif; ?>
            </span>
            <br>
             <span class="text-muted small">
                Liên hệ: <a href="mailto:admin@example.com">admin@example.com</a>
                <?php // | <a href="#">Chính sách bảo mật</a> | <a href="#">Điều khoản sử dụng</a> ?>
            </span>
        </div>
    </footer>
    <!-- *** KẾT THÚC FOOTER TÙY CHỈNH *** -->


    <!-- Các thẻ <script> (Bootstrap, Chart.js, app.js) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?php echo BASE_URL; ?>js/app.js" defer></script>

</body>
</html>