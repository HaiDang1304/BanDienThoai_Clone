<?php
session_start();
session_destroy();

// Quay về trang chủ của bạn (Home)
header('Location: /BanDienThoai_Clone/public/index.php');
exit;
