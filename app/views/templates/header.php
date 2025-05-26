<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VMIEd - Hệ thống đào tạo</title>
    <link rel="stylesheet" href="/public/assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <img src="/public/assets/images/logo.png" alt="Logo" class="logo">
            <nav>
                <a href="/app/views/auth/register_view.php">Đăng ký</a>
                <?php 
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                if (isset($_SESSION['user_name']) && !empty($_SESSION['user_name'])): ?>
                    <a href="/app/controllers/LogoutController.php">Đăng xuất</a>
                <?php else: ?>
                    <a href="/app/views/auth/login_view.php">Đăng nhập</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main>
