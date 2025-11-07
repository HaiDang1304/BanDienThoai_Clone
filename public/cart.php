<?php
// public/cart.php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();

$BASE_URL = '/BanDienThoai_Clone/public';
function url_to(string $p){ global $BASE_URL; return rtrim($BASE_URL,'/').'/'.ltrim($p,'/'); }
function vnd($n){ return number_format((int)$n, 0, ',', '.') . 'đ'; }

/* Ảnh: chuẩn hoá URL và fallback placeholder */
function img_url(?string $path): string {
  global $BASE_URL;
  $placeholder = rtrim($BASE_URL,'/') . '/assets/images/placeholder.png';
  if (!$path) return $placeholder;

  if (preg_match('~^https?://~i', $path)) return $path; // URL tuyệt đối

  // bỏ prefix public/ nếu có
  $path = preg_replace('~^/?public/~i', '', $path);
  // về dạng URL dưới /public
  if ($path[0] === '/') $url = rtrim($BASE_URL,'/') . $path;
  else                  $url = rtrim($BASE_URL,'/') . '/' . ltrim($path,'/');

  return $url;
}

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
foreach ($cart as $it) $subtotal += ((int)$it['price']) * ((int)$it['qty']);

/* Tạo link thanh toán:
   - Nếu giỏ có 1 sản phẩm -> giống "Mua ngay": checkout.php?product_id=...&qty=...
   - Nếu >1 sản phẩm -> checkout.php (đọc từ session giỏ) */
$checkoutHref = url_to('checkout.php');
if (count($cart) === 1) {
  $first = reset($cart);
  $pid   = (int)($first['id'] ?? 0);
  $qty   = max(1, (int)($first['qty'] ?? 1));
  $variant = isset($first['variant']) ? trim((string)$first['variant']) : '';
  $qs = http_build_query([
    'product_id' => $pid,
    'qty'        => $qty,
    // 'variant' => $variant, // bật nếu checkout xử lý variant theo query
  ]);
  $checkoutHref = url_to('checkout.php?'.$qs);
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Giỏ hàng</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">

  <?php include __DIR__ . '/../app/views/partials/header.php'; ?>

  <main class="flex-1 max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center gap-2 mb-6">
      <i class="fa-solid fa-cart-shopping text-yellow-600 text-2xl"></i>
      <h1 class="text-2xl font-bold">Giỏ hàng của bạn</h1>
      <?php if (!empty($cart)): ?>
        <span class="ml-2 text-sm text-gray-600">(<?= count($cart) ?> sản phẩm)</span>
      <?php endif; ?>
    </div>

    <?php if (!$cart): ?>
      <div class="bg-white rounded-2xl shadow p-10 text-center">
        <i class="fa-solid fa-face-frown text-gray-400 text-5xl mb-4"></i>
        <p class="text-gray-700 mb-4 text-lg">Giỏ hàng của bạn đang trống!</p>
        <a href="<?= url_to('index.php') ?>"
           class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-yellow-500 text-white font-medium hover:bg-yellow-600 transition">
          <i class="fa-solid fa-store"></i> Tiếp tục mua sắm
        </a>
      </div>
    <?php else: ?>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Danh sách sản phẩm -->
        <section class="lg:col-span-2 space-y-4">

          <!-- Form cập nhật số lượng (độc lập, không bọc các item để tránh lồng form) -->
          <form id="formUpdate" action="<?= url_to('cart_action.php') ?>" method="post" class="hidden">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
          </form>

          <?php foreach ($cart as $key => $it): ?>
            <?php $thumb = $it['thumbnail'] ?? ($it['image_url'] ?? ''); ?>
            <div class="bg-white border rounded-xl shadow-sm p-4 flex items-center gap-4 hover:shadow-md transition">
              <div class="w-24 h-24 flex-shrink-0">
                <img
                  src="<?= htmlspecialchars(img_url($thumb)) ?>"
                  alt="Ảnh sản phẩm"
                  class="w-24 h-24 object-cover rounded-lg border"
                  loading="lazy"
                  onerror="this.onerror=null;this.src='<?= htmlspecialchars(url_to('assets/images/placeholder.png')) ?>';"
                />
              </div>

              <div class="flex-1 min-w-0">
                <div class="font-semibold text-gray-800 truncate pr-4">
                  <?= htmlspecialchars($it['name'] ?? '') ?>
                </div>
                <?php if (!empty($it['variant'])): ?>
                  <div class="text-sm text-gray-500 mt-0.5">Biến thể: <?= htmlspecialchars($it['variant']) ?></div>
                <?php endif; ?>
                <div class="text-red-600 font-semibold mt-1"><?= vnd((int)$it['price']) ?></div>
              </div>

              <div class="flex items-center gap-3">
                <!-- Stepper số lượng (tham gia formUpdate bằng thuộc tính form) -->
                <div class="flex items-center rounded-lg border">
                  <button type="button" class="px-2 py-1" onclick="
                    const i=this.nextElementSibling;
                    i.stepDown(); if(i.value<0)i.value=0;
                  "><i class="fa-solid fa-minus"></i></button>
                  <input
                    form="formUpdate"
                    type="number" min="0"
                    name="items[<?= htmlspecialchars((string)$key) ?>]"
                    value="<?= (int)$it['qty'] ?>"
                    class="w-16 border-l border-r px-2 py-1 text-center focus:ring-2 focus:ring-yellow-400">
                  <button type="button" class="px-2 py-1" onclick="this.previousElementSibling.stepUp()">
                    <i class="fa-solid fa-plus"></i>
                  </button>
                </div>

                <!-- Xoá sản phẩm (form riêng, không lồng) -->
                <form action="<?= url_to('cart_action.php') ?>" method="post" class="inline">
                  <input type="hidden" name="action" value="remove">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="key" value="<?= htmlspecialchars((string)$key) ?>">
                  <button class="text-red-500 hover:text-red-700 p-2 rounded-lg hover:bg-red-50 transition" title="Xóa sản phẩm">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>

          <!-- Hành động cập nhật / xóa toàn bộ -->
          <div class="flex flex-wrap items-center gap-3 pt-2">
            <button form="formUpdate"
              class="px-5 py-2 rounded-full bg-yellow-500 hover:bg-yellow-600 text-white font-medium flex items-center gap-2 transition">
              <i class="fa-solid fa-rotate"></i> Cập nhật giỏ hàng
            </button>

            <form action="<?= url_to('cart_action.php') ?>" method="post">
              <input type="hidden" name="action" value="clear">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
              <button
                class="px-5 py-2 rounded-full border border-gray-300 hover:bg-gray-100 flex items-center gap-2 transition">
                <i class="fa-solid fa-trash-can"></i> Xóa toàn bộ
              </button>
            </form>
          </div>
        </section>

        <!-- Tóm tắt đơn hàng -->
        <aside class="bg-white rounded-2xl border shadow p-6 h-fit lg:sticky lg:top-6">
          <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
            <i class="fa-solid fa-receipt text-green-600"></i> Thông tin đơn hàng
          </h2>

          <div class="flex justify-between text-gray-700 text-lg mb-2">
            <span>Tạm tính</span>
            <span class="font-semibold"><?= vnd($subtotal) ?></span>
          </div>
          <div class="flex justify-between text-gray-700 text-sm mb-1">
            <span>Phí vận chuyển</span>
            <span class="text-green-600">Miễn phí</span>
          </div>
          <hr class="my-3">
          <div class="flex justify-between text-xl font-bold text-gray-800">
            <span>Tổng cộng</span>
            <span class="text-red-600"><?= vnd($subtotal) ?></span>
          </div>

          <a href="<?= htmlspecialchars($checkoutHref) ?>"
             class="block w-full mt-5 text-center py-3 rounded-full bg-green-600 hover:bg-green-700 text-white font-semibold flex items-center justify-center gap-2 transition">
            <i class="fa-solid fa-credit-card"></i> Tiến hành thanh toán
          </a>
        </aside>
      </div>

    <?php endif; ?>
  </main>
</body>
</html>
