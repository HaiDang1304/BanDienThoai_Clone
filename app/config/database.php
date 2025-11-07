<?php
// app/config/database.php
declare(strict_types=1);

/**
 * Trả về kết nối MySQLi dạng singleton (không còn dùng $conn global).
 * Được bọc trong if (!function_exists('db')) để tránh lỗi "Cannot redeclare".
 */
if (!function_exists('db')) {
    function db(): mysqli
    {
        static $conn = null;
        if ($conn instanceof mysqli) {
            return $conn;
        }

        $host = "localhost";
        $user = "root";
        $pass = "";
        $db   = "bandienthoai_clone";

        $conn = @new mysqli($host, $user, $pass, $db);
        if ($conn->connect_error) {
            // Ném Exception thay vì die() để controller bắt được
            throw new Exception("Kết nối thất bại: " . $conn->connect_error);
        }

        // Nên dùng utf8mb4
        $conn->set_charset("utf8mb4");
        return $conn;
    }
}
