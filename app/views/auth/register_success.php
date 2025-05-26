<?php
session_start();

// modules/auth/views/register_success.php
require_once __DIR__ . '/../templates/header.php';

if (!isset($_SESSION['register_success'])) {
    header('Location: register_view.php');
    exit();
}
unset($_SESSION['register_success']);
?>

<div class="auth-container">
    <div class="success-message">
        <h2><i class="fas fa-check-circle"></i> Đăng ký thành công!</h2>
        <p>Tài khoản của bạn đã được tạo. Vui lòng <a href="login_view.php">đăng nhập</a> để bắt đầu.</p>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>