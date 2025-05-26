<?php
require_once __DIR__ . '/../../views/templates/header.php';
?>

<div class="auth-container">
    <form method="POST" action="/app/controllers/LoginController.php">
        <h2>Đăng nhập</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Mật khẩu</label>
            <input type="password" name="password" required>
        </div>

        <a href="/login/google" class="google-btn">  <!-- Đảm bảo khớp với rule trong .htaccess -->
            <img src="/assets/images/google-icon.png" alt="Google">
            Đăng nhập bằng Google
        </a>

        <button type="submit">Đăng nhập</button>
    </form>
</div>

<?php require_once __DIR__ . '/../../views/templates/footer.php'; ?>