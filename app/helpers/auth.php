<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function require_admin_then_or_back_home(): void {
  $base = '/BanDienThoai_Clone/public';
  $isAdmin = !empty($_SESSION['auth']) && ($_SESSION['auth']['role'] ?? 'user') === 'admin';
  if (!$isAdmin) {
    header('Location: '.$base.'/index.php');
    exit;
  }
}
