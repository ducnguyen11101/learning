<?php
// modules/auth/controllers/RegisterController.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    $errors = [];
    
    if (empty($username)) {
        $errors['username'] = 'Vui lòng nhập tên đăng nhập';
    } elseif (strlen($username) < 5) {
        $errors['username'] = 'Tên đăng nhập phải có ít nhất 5 ký tự';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email không hợp lệ';
    }

    if (strlen($password) < 8) {
        $errors['password'] = 'Mật khẩu phải có ít nhất 8 ký tự';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Mật khẩu không khớp';
    }

    // Check if email exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM accounts WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $errors['email'] = 'Email đã được đăng ký';
        }
    }

    // Insert new user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO accounts (username, email, password, role) VALUES (?, ?, ?, 'student')");
        $stmt->bind_param("sss", $username, $email, $hashed_password);

        if ($stmt->execute()) {
            $_SESSION['register_success'] = true;
            header('Location: /app/views/auth/register_success.php');
            exit();
        } else {
            $errors['database'] = 'Lỗi hệ thống: ' . $conn->error;
        }
    }

    $_SESSION['register_errors'] = $errors;
    $_SESSION['old_input'] = $_POST;
    header('Location: /app/views/auth/register_view.php');
    exit();
}
?>