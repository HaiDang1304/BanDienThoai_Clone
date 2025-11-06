<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "bandienthoai_clone";

// Kết nối MySQL
$conn = new mysqli($host, $user, $pass, $db);

// Kiểm tra lỗi
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Set utf8
$conn->set_charset("utf8");
?>
