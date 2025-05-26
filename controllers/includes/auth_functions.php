<?php
function sanitize_input($data) {
    global $conn;
    return htmlspecialchars(stripslashes($conn->real_escape_string(trim($data))));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}
?>