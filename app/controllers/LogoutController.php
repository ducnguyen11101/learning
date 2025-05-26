<?php
session_start();
session_unset(); // Xóa tất cả biến session
session_destroy(); // Hủy session

// Chuyển hướng về trang đăng nhập
header('Location: /index.php');
exit();