<?php
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

class LoginController {
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    // Xử lý đăng nhập thông thường (POST)
    public function handleLogin() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = sanitize_input($_POST['email']);
            $password = $_POST['password'];
            
            $stmt = $this->conn->prepare("SELECT * FROM accounts WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                $this->setUserSession($user);
                redirect('/index.php');
            } else {
                $_SESSION['error'] = "Email hoặc mật khẩu không đúng";
                redirect('/modules/auth/views/login_view.php');
            }
        }
    }

    // Xử lý đăng nhập Google (GET)
    public function handleGoogleAuth() {
        require_once __DIR__ . '/../modules/auth/GoogleAuth.php';
        $googleAuth = new GoogleAuth();
        redirect($googleAuth->getAuthUrl());
    }

    // Xử lý callback từ Google
    public function handleGoogleCallback() {
        require_once __DIR__ . '/../modules/auth/GoogleAuth.php';
        $googleAuth = new GoogleAuth();
        
        if (!isset($_GET['code'])) {
            $_SESSION['error'] = "Thiếu mã xác thực từ Google";
            redirect('/modules/auth/views/login_view.php');
            return;
        }

        try {
            $userInfo = $googleAuth->getUserInfo($_GET['code']);
            
            // Kiểm tra hoặc tạo user
            $stmt = $this->conn->prepare("SELECT * FROM accounts WHERE email = ?");
            $stmt->bind_param("s", $userInfo->email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!$user) {
                $username = explode('@', $userInfo->email)[0];
                $stmt = $this->conn->prepare("INSERT INTO accounts (email, username, role) VALUES (?, ?, 'student')");
                $stmt->bind_param("ss", $userInfo->email, $username);
                $stmt->execute();
                $user_id = $this->conn->insert_id;
                $user = ['id' => $user_id, 'role' => 'student'];
            }

            $this->setUserSession([
                'id' => $user['id'],
                'username' => $userInfo->name ?? $username,
                'role' => $user['role']
            ]);
            $redirectUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/index.php';
            error_log("Redirecting to: " . $redirectUrl); // Ghi log
            header("Location: " . $redirectUrl);
            exit(); // Luôn dùng exit sau header
        } catch (Exception $e) {
            error_log("Google Login Error: " . $e->getMessage());
            $_SESSION['error'] = "Đăng nhập bằng Google thất bại";
            redirect('/app/views/auth/login_view.php');
        }
    }

    // Thiết lập session người dùng
    private function setUserSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['username'] ?? $user['email'];
        $_SESSION['role'] = $user['role'];
    }
}

// Khởi tạo và xử lý action
$controller = new LoginController();

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'googleAuth':
            $controller->handleGoogleAuth();
            break;
        case 'googleCallback':
            $controller->handleGoogleCallback();
            break;
        default:
            $controller->handleLogin();
    }
} else {
    $controller->handleLogin();
}
?>