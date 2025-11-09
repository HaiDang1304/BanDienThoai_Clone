<?php
// public/order_success.php
declare(strict_types=1);
$BASE_URL = '/BanDienThoai_Clone/public';
function url_to(string $path): string {
  global $BASE_URL;
  return rtrim($BASE_URL, '/') . '/' . ltrim($path, '/');
}
$code = htmlspecialchars($_GET['code'] ?? '');
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Đặt hàng thành công</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800">
  <main class="max-w-xl mx-auto px-4 py-10 text-center">
    <div class="bg-white border rounded-2xl p-6">
      <div class="text-4xl mb-3">🎉</div>
      <h1 class="text-xl md:text-2xl font-bold">Đặt hàng thành công!</h1>
      <?php if ($code): ?>
        <p class="mt-2 text-gray-600 text-sm">Mã đơn hàng của bạn: <span class="font-semibold"><?= $code ?></span></p>
      <?php endif; ?>
      <p class="mt-2 text-gray-600 text-sm">Chúng tôi sẽ liên hệ xác nhận và giao hàng sớm nhất.</p>
      <a href="<?= url_to('index.php') ?>" class="mt-5 inline-block px-5 py-2 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700">
        Về trang chủ 
      </a>
    </div>
  </main>
</body>
</html>
