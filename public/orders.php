<?php
// public/orders.php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../app/config/database.php';

/* ===== Helpers ===== */
$BASE_URL = '/BanDienThoai_Clone/public';
function url_to(string $p){ global $BASE_URL; return rtrim($BASE_URL,'/').'/'.ltrim($p,'/'); }
function vnd($n){ return number_format((int)$n, 0, ',', '.') . 'đ'; }
function status_badge_class(string $s): string {
  $s = strtolower($s);
  return match (true) {
    str_contains($s,'pending')   => 'bg-amber-50 text-amber-700 border-amber-200',
    str_contains($s,'paid')      => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    str_contains($s,'shipping')  => 'bg-sky-50 text-sky-700 border-sky-200',
    str_contains($s,'completed') => 'bg-green-50 text-green-700 border-green-200',
    str_contains($s,'cancel')    => 'bg-rose-50 text-rose-700 border-rose-200',
    default                      => 'bg-gray-50 text-gray-700 border-gray-200',
  };
}

/* ===== Auth ===== */
if (empty($_SESSION['auth'])) {
  header('Location: '.url_to('index.php')); exit;
}
$userId    = (int)($_SESSION['auth']['id'] ?? 0);
$userEmail = trim((string)($_SESSION['auth']['email'] ?? ''));
$userPhone = trim((string)($_SESSION['auth']['phone'] ?? ''));

/* ===== Query orders ===== */
try {
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  $conn = db();

  // kiểm tra có cột user_id không
  $hasUserId = false;
  $ck = $conn->prepare(
    "SELECT 1 FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME=?"
  );
  $col = 'user_id';
  $ck->bind_param('s', $col);
  $ck->execute();
  $hasUserId = (bool)$ck->get_result()->fetch_row();
  $ck->close();

  if ($hasUserId && $userId > 0) {
    $sql = "SELECT id, code, fullname, phone, email, address, district, province, note,
                   shipping_method, payment_method, subtotal, shipping_fee, discount, total,
                   status, created_at
            FROM orders
            WHERE user_id = ?
            ORDER BY id DESC";
    $stm = $conn->prepare($sql);
    $stm->bind_param('i', $userId);
  } elseif ($userEmail !== '') {
    $sql = "SELECT id, code, fullname, phone, email, address, district, province, note,
                   shipping_method, payment_method, subtotal, shipping_fee, discount, total,
                   status, created_at
            FROM orders
            WHERE email = ?
            ORDER BY id DESC";
    $stm = $conn->prepare($sql);
    $stm->bind_param('s', $userEmail);
  } elseif ($userPhone !== '') {
    $sql = "SELECT id, code, fullname, phone, email, address, district, province, note,
                   shipping_method, payment_method, subtotal, shipping_fee, discount, total,
                   status, created_at
            FROM orders
            WHERE phone = ?
            ORDER BY id DESC";
    $stm = $conn->prepare($sql);
    $stm->bind_param('s', $userPhone);
  } else {
    throw new RuntimeException('Không tìm được tiêu chí nhận diện (user_id/email/phone).');
  }

  $stm->execute();
  $orders = $stm->get_result()->fetch_all(MYSQLI_ASSOC);
  $stm->close();

  // Load items theo danh sách order_id
  $itemsByOrder = [];
  $ids = array_column($orders, 'id');
  if ($ids) {
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $sql2 = "SELECT id, order_id, product_id, name, variant, price, qty
             FROM order_items
             WHERE order_id IN ($ph)
             ORDER BY id ASC";
    $stm2 = $conn->prepare($sql2);
    // bind by reference
    foreach ($ids as $k => $v) $ids[$k] = (int)$v;
    $refs = [];
    foreach ($ids as $k => &$v) $refs[$k] = &$v;
    $params = array_merge([$types], $refs);
    $stm2->bind_param(...$params);
    $stm2->execute();
    $rs2 = $stm2->get_result();
    while ($row = $rs2->fetch_assoc()) {
      $itemsByOrder[(int)$row['order_id']][] = $row;
    }
    $stm2->close();
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo "Lỗi tải lịch sử: ".htmlspecialchars($e->getMessage());
  exit;
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Lịch sử mua hàng</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f7f7f9] text-gray-800">
  <?php
    // include header theo đường dẫn bạn yêu cầu; nếu không có thì fallback
    if (file_exists(__DIR__ . '/partials/header.php')) {
      include __DIR__ . '/partials/header.php';
    } elseif (file_exists(__DIR__ . '/../app/views/partials/header.php')) {
      include __DIR__ . '/../app/views/partials/header.php';
    }
  ?>

  <main class="max-w-6xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-5">
      <div>
        <h1 class="text-2xl font-bold"> Lịch sử mua hàng</h1>
        <p class="text-sm text-gray-500 mt-1">Xem lại các đơn hàng bạn đã đặt.</p>
      </div>
      <a href="<?= url_to('index.php') ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border bg-white hover:bg-gray-50">
        ⟵ Tiếp tục mua sắm
      </a>
    </div>

    <?php if (empty($orders)): ?>
      <div class="bg-white rounded-2xl border shadow-sm p-8 text-center">
        <div class="text-4xl mb-2">🛍️</div>
        <div class="text-lg font-semibold">Bạn chưa có đơn hàng nào</div>
        <p class="text-gray-500 mt-1">Khi bạn đặt mua, đơn hàng sẽ hiển thị tại đây.</p>
        <a href="<?= url_to('index.php') ?>" class="mt-4 inline-block px-5 py-2 rounded-xl bg-red-600 text-white hover:bg-red-700">Mua ngay</a>
      </div>
    <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($orders as $o): 
          $badge = status_badge_class((string)($o['status'] ?? ''));
        ?>
          <div class="bg-white rounded-2xl border shadow-sm p-5">
            <!-- Header đơn -->
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div class="flex items-center gap-3">
                <span class="font-semibold">Mã đơn:</span>
                <span class="font-mono px-2 py-1 rounded bg-gray-100"><?= htmlspecialchars($o['code'] ?: ('#'.$o['id'])) ?></span>
                <span class="text-sm text-gray-500"><?= htmlspecialchars($o['created_at']) ?></span>
              </div>
              <div class="flex items-center gap-3">
                <span class="px-2 py-1 text-sm border rounded-full <?= $badge ?>">
                  <?= htmlspecialchars($o['status'] ?? 'pending') ?>
                </span>
                <div class="text-lg font-bold text-red-600"><?= vnd((int)$o['total']) ?></div>
              </div>
            </div>

            <!-- Địa chỉ / phương thức -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-4 text-sm">
              <div class="bg-gray-50 rounded-xl p-3">
                <div class="font-semibold mb-1">Người nhận</div>
                <div><?= htmlspecialchars($o['fullname'] ?: '-') ?> · <?= htmlspecialchars($o['phone'] ?: '-') ?></div>
                <div class="text-gray-600"><?= htmlspecialchars($o['email'] ?: '-') ?></div>
              </div>
              <div class="bg-gray-50 rounded-xl p-3">
                <div class="font-semibold mb-1">Địa chỉ</div>
                <div><?= htmlspecialchars($o['address'] ?: '-') ?></div>
                <div class="text-gray-600">
                  <?= htmlspecialchars($o['district'] ?: '-') ?>, <?= htmlspecialchars($o['province'] ?: '-') ?>
                </div>
              </div>
              <div class="bg-gray-50 rounded-xl p-3">
                <div class="font-semibold mb-1">Vận chuyển & Thanh toán</div>
                <div>Vận chuyển: <span class="font-medium"><?= htmlspecialchars($o['shipping_method'] ?: '-') ?></span></div>
                <div>Thanh toán: <span class="font-medium"><?= htmlspecialchars($o['payment_method'] ?: '-') ?></span></div>
              </div>
            </div>

            <!-- Danh sách sản phẩm -->
            <div class="mt-4">
              <div class="rounded-xl border overflow-hidden">
                <?php foreach ($itemsByOrder[$o['id']] ?? [] as $it): ?>
                  <div class="flex items-center justify-between px-4 py-3 border-b last:border-0 bg-white">
                    <div>
                      <div class="font-medium"><?= htmlspecialchars($it['name']) ?></div>
                      <?php if (!empty($it['variant'])): ?>
                        <div class="text-xs text-gray-500">Biến thể: <?= htmlspecialchars($it['variant']) ?></div>
                      <?php endif; ?>
                      <div class="text-xs text-gray-500">SL: <?= (int)$it['qty'] ?> × <?= vnd((int)$it['price']) ?></div>
                    </div>
                    <div class="font-semibold"><?= vnd((int)$it['price'] * (int)$it['qty']) ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Tổng kết -->
            <div class="mt-4 md:flex md:items-center md:justify-end md:gap-6 text-sm">
              <div class="md:text-right bg-gray-50 rounded-xl p-3 md:min-w-[320px]">
                <div class="flex justify-between"><span>Tạm tính</span><span><?= vnd((int)$o['subtotal']) ?></span></div>
                <div class="flex justify-between"><span>Phí vận chuyển</span><span><?= vnd((int)$o['shipping_fee']) ?></span></div>
                <div class="flex justify-between"><span>Giảm giá</span><span>-<?= vnd((int)$o['discount']) ?></span></div>
                <div class="flex justify-between font-semibold text-base mt-1">
                  <span>Tổng thanh toán</span><span class="text-red-600"><?= vnd((int)$o['total']) ?></span>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>
