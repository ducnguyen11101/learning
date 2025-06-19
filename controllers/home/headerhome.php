<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VMIEd - Hệ thống đào tạo</title>
    <link rel="stylesheet" href="/controllers/home/assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <img src="/controllers/home/assets/images/logo.png" alt="Logo" class="logo">
            <!-- Thêm menu giữa -->
            <nav style="flex:1;display:flex;justify-content:center;">
                <ul style="display:flex;gap:32px;list-style:none;margin:0;">
                    <li><a href="/">My IXL</a></li>
                    <li><a href="#">Học tập</a></li>
                    <li><a href="#">Thi đua</a></li>
                    <li><a href="/analytics">Phân tích</a></li>
                </ul>
            </nav>
            <nav>
                <a href="/register">Đăng ký</a>
                <?php 
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                if (isset($_SESSION['user_name']) && !empty($_SESSION['user_name'])): ?>
                    <a href="/app/controllers/LogoutController.php">Đăng xuất</a>
                <?php else: ?>
                    <a href="/login">Đăng nhập</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main>
