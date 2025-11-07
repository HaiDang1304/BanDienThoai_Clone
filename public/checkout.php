<?php
// public/checkout.php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../app/config/database.php';

/* ===== Helpers ===== */
$BASE_URL = '/BanDienThoai_Clone/public';
function url_to(string $p){ global $BASE_URL; return rtrim($BASE_URL,'/').'/'.ltrim($p,'/'); }
function vnd($n){ return number_format((int)$n, 0, ',', '.') . 'đ'; }
function img_url(?string $path): string {
  global $BASE_URL;
  $ph = rtrim($BASE_URL,'/').'/assets/images/placeholder.png';
  if (!$path) return $ph;
  if (preg_match('~^https?://~i', $path)) return $path;
  $path = preg_replace('~^/?public/~i', '', $path);
  return rtrim($BASE_URL,'/').'/'.ltrim($path,'/');
}

/* ===== Input ===== */
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$qty       = isset($_GET['qty']) ? max(1,(int)$_GET['qty']) : 1;
$variant   = isset($_GET['variant']) ? trim((string)$_GET['variant']) : '';

/* ===== DB helper ===== */
function db_find_product(int $id): ?array {
  try { $conn = db(); } catch (\Throwable $e) { return null; }
  $sql = "
    SELECT p.id, p.name, p.price, p.variant,
           COALESCE(
             (SELECT pi.image_url FROM product_images pi WHERE pi.product_id=p.id ORDER BY pi.sort_order,pi.id LIMIT 1),
             p.image_url
           ) AS image_url
    FROM products p WHERE p.id = ? LIMIT 1
  ";
  $stm = $conn->prepare($sql);
  $stm->bind_param('i',$id);
  $stm->execute();
  $row = $stm->get_result()->fetch_assoc();
  $stm->close();
  if (!$row) return null;
  // chuẩn đường dẫn ảnh
  $row['image_url'] = preg_replace('~^/?public/~i','', (string)$row['image_url']);
  return $row;
}

/* ===== Build order items ===== */
$items = []; // mỗi item: id,name,price,qty,image_url,variant

if ($productId > 0) {
  // Luồng MUA NGAY (1 sản phẩm)
  if ($p = db_find_product($productId)) {
    $items[] = [
      'id'        => (int)$p['id'],
      'name'      => (string)$p['name'],
      'price'     => (int)$p['price'],
      'qty'       => $qty,
      'variant'   => $variant ?: (string)($p['variant'] ?? ''),
      'image_url' => (string)$p['image_url'],
    ];
  }
} else {
  // Luồng THANH TOÁN GIỎ (nhiều sản phẩm)
  $cart = $_SESSION['cart'] ?? [];
  foreach ($cart as $line) {
    // chấp nhận cả thumbnail & image_url đã lưu ở cart
    $img = $line['thumbnail'] ?? ($line['image_url'] ?? '');
    $items[] = [
      'id'        => (int)($line['id'] ?? 0),
      'name'      => (string)($line['name'] ?? ''),
      'price'     => (int)($line['price'] ?? 0),
      'qty'       => max(1,(int)($line['qty'] ?? 1)),
      'variant'   => (string)($line['variant'] ?? ''),
      'image_url' => preg_replace('~^/?public/~i','', (string)$img),
    ];
  }
}

/* Không có item nào -> quay lại giỏ */
if (empty($items)) {
  header('Location: '.url_to('cart.php')); exit;
}

/* ===== Tính tiền ===== */
$subtotal = 0;
foreach ($items as $it) $subtotal += $it['price'] * $it['qty'];

// Ví dụ: phí ship cố định 30k nếu subtotal < 5tr, còn lại miễn phí
$shipping = ($subtotal > 5_000_000) ? 0 : 30000;
$discount = 0; // demo
$total    = $subtotal + $shipping - $discount;

/* ===== CSRF form đặt hàng ===== */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Thanh toán</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50">
  <?php include __DIR__ . '/../app/views/partials/header.php'; ?>

  <main class="max-w-7xl mx-auto px-4 py-6">
    <nav class="text-sm text-gray-500 mb-4">
      <a href="<?= url_to('index.php') ?>" class="hover:text-red-600">Trang chủ</a> <span class="mx-1">/</span> Thanh toán
    </nav>

    <h1 class="text-2xl font-bold mb-5">Thông tin thanh toán</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
      <!-- Form thông tin -->
      <form action="<?= url_to('place_order.php') ?>" method="post" class="lg:col-span-2 space-y-4">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <!-- Nếu là mua ngay, giữ product_id/qty để backend biết -->
        <?php if ($productId > 0): ?>
          <input type="hidden" name="product_id" value="<?= (int)$productId ?>">
          <input type="hidden" name="qty" value="<?= (int)$qty ?>">
        <?php endif; ?>

        <div class="bg-white rounded-2xl border p-4 space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-sm text-gray-600">Họ và tên *</label>
              <input name="fullname" class="w-full mt-1 border rounded-lg px-3 py-2" placeholder="VD: Nguyễn Văn A" required>
            </div>
            <div>
              <label class="text-sm text-gray-600">Số điện thoại *</label>
              <input name="phone" class="w-full mt-1 border rounded-lg px-3 py-2" placeholder="VD: 09xxxxxxxx" required>
            </div>
          </div>
          <div>
            <label class="text-sm text-gray-600">Email</label>
            <input name="email" type="email" class="w-full mt-1 border rounded-lg px-3 py-2" placeholder="VD: email@domain.com">
          </div>
          <div>
            <label class="text-sm text-gray-600">Địa chỉ *</label>
            <input name="address" class="w-full mt-1 border rounded-lg px-3 py-2" placeholder="Số nhà, đường, phường/xã" required>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-sm text-gray-600">Quận/Huyện *</label>
              <input name="district" class="w-full mt-1 border rounded-lg px-3 py-2" required>
            </div>
            <div>
              <label class="text-sm text-gray-600">Tỉnh/Thành *</label>
              <input name="province" class="w-full mt-1 border rounded-lg px-3 py-2" required>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl border p-4">
          <h3 class="font-semibold mb-3">Phương thức giao hàng</h3>
          <label class="flex items-center gap-3 py-2">
            <input type="radio" name="shipping" value="standard" class="accent-yellow-500" checked>
            <span>Giao tiêu chuẩn (2–4 ngày) – <?= vnd($shipping) ?></span>
          </label>
          <label class="flex items-center gap-3 py-2 opacity-60">
            <input type="radio" disabled class="accent-yellow-500">
            <span>Hỏa tốc (trong ngày) – Tạm khóa</span>
          </label>
        </div>

        <div class="bg-white rounded-2xl border p-4">
          <h3 class="font-semibold mb-3">Phương thức thanh toán</h3>
          <label class="flex items-center gap-3 py-2">
            <input type="radio" name="payment" value="cod" class="accent-yellow-500" checked>
            <span>Thanh toán khi nhận hàng (COD)</span>
          </label>
          <label class="flex items-center gap-3 py-2">
            <input type="radio" name="payment" value="bank" class="accent-yellow-500">
            <span>Chuyển khoản ngân hàng</span>
          </label>
        </div>
      </form>

      <!-- Tóm tắt đơn hàng -->
      <aside class="bg-white rounded-2xl border p-4 h-fit">
        <h3 class="font-semibold mb-3">Tóm tắt đơn hàng</h3>

        <?php foreach ($items as $it): ?>
          <div class="flex items-center gap-3 py-2">
            <img src="<?= htmlspecialchars(img_url($it['image_url'])) ?>" class="w-14 h-14 rounded-lg object-cover border" alt="">
            <div class="flex-1">
              <div class="text-sm font-medium line-clamp-2"><?= htmlspecialchars($it['name']) ?></div>
              <?php if (!empty($it['variant'])): ?>
                <div class="text-xs text-gray-500">Biến thể: <?= htmlspecialchars($it['variant']) ?></div>
              <?php endif; ?>
              <div class="text-xs text-gray-500">Số lượng: <?= (int)$it['qty'] ?></div>
            </div>
            <div class="text-red-600 font-semibold"><?= vnd($it['price']) ?></div>
          </div>
        <?php endforeach; ?>

        <hr class="my-3">
        <div class="flex justify-between text-sm mb-1"><span>Tạm tính</span><span><?= vnd($subtotal) ?></span></div>
        <div class="flex justify-between text-sm mb-1"><span>Phí vận chuyển</span><span><?= vnd($shipping) ?></span></div>
        <div class="flex justify-between text-sm mb-1"><span>Giảm giá</span><span>-<?= vnd($discount) ?></span></div>
        <div class="flex justify-between font-bold text-lg mt-2">
          <span>Tổng thanh toán</span><span class="text-red-600"><?= vnd($total) ?></span>
        </div>

        <!-- Nút đặt hàng submit form thông tin (form ở cột trái) -->
        <button form="formPlace" class="hidden"></button>
        <button
          onclick="document.querySelector('form[action$=\'place_order.php\']').requestSubmit();"
          class="w-full mt-4 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold">
          Đặt hàng
        </button>

        <p class="text-[12px] text-gray-500 mt-2">
          Bấm “Đặt hàng” là bạn đồng ý với điều khoản mua hàng của chúng tôi.
        </p>
      </aside>
    </div>
  </main>
</body>
</html>
