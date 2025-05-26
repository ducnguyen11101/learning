<?php
// Kết nối database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'vmi_edu');

// Khởi tạo kết nối
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once __DIR__ . '/../vendor/autoload.php';
define('GOOGLE_CLIENT_ID', '653751520536-eul1fu1gtgrid4j7d7f9acfagerh7rrn.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', 'http://localhost/education/google-callback');
define('BASE_URL', 'http://localhost/education/');

?>