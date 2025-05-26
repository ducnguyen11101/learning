<?php
// modules/auth/views/register_view.php
require_once __DIR__ . '/../templates/header.php';

$errors = $_SESSION['register_errors'] ?? [];
$old_input = $_SESSION['old_input'] ?? [];
unset($_SESSION['register_errors'], $_SESSION['old_input']);
?>

<div class="auth-container">
    <form method="POST" action="/app/controllers/RegisterController.php">        
        <h2>Đăng ký tài khoản</h2>   
        <?php if (isset($errors['database'])): ?>
            <div class="alert alert-danger"><?= $errors['database'] ?></div>
        <?php endif; ?>

        <div class="form-group">
            <label for="username">Tên đăng nhập</label>
            <input type="text" id="username" name="username" 
                   value="<?= htmlspecialchars($old_input['username'] ?? '') ?>" required>
            <?php if (isset($errors['username'])): ?>
                <span class="error"><?= $errors['username'] ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" 
                   value="<?= htmlspecialchars($old_input['email'] ?? '') ?>" required>
            <?php if (isset($errors['email'])): ?>
                <span class="error"><?= $errors['email'] ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Mật khẩu (tối thiểu 8 ký tự)</label>
            <input type="password" id="password" name="password" required minlength="8">
            <?php if (isset($errors['password'])): ?>
                <span class="error"><?= $errors['password'] ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="confirm_password">Nhập lại mật khẩu</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            <?php if (isset($errors['confirm_password'])): ?>
                <span class="error"><?= $errors['confirm_password'] ?></span>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn-submit">Đăng ký</button>
        
        <div class="auth-footer">
            Đã có tài khoản? <a href="login_view.php">Đăng nhập ngay</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>