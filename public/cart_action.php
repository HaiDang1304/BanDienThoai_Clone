<?php
// public/cart_action.php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../app/config/database.php';

$BASE_URL = '/BanDienThoai_Clone/public';
function url_to(string $p){ global $BASE_URL; return rtrim($BASE_URL,'/').'/'.ltrim($p,'/'); }

/* ---- Helpers ---- */
function normalize_img(?string $p): string {
  if (!$p) return '';
  // bỏ prefix public/ nếu lỡ lưu kèm
  $p = preg_replace('~^/?public/~i', '', $p);
  // chuẩn về tương đối từ thư mục /public (không có slash đầu)
  return ltrim($p, '/');
}

/* ---- CSRF ---- */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
if (($_POST['csrf'] ?? '') !== $_SESSION['csrf']) {
  http_response_code(400); echo 'CSRF token không hợp lệ.'; exit;
}

/* ---- Action ---- */
$action = $_POST['action'] ?? '';
if (!in_array($action, ['add','update','remove','clear'], true)) {
  header('Location: '.url_to('cart.php')); exit;
}
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

/* ---- DB fetch ---- */
function find_product(int $id): ?array {
  try { $conn = db(); } catch (\Throwable $e) { return null; }

  // Lấy ảnh đại diện: ưu tiên product_images.sort_order nhỏ nhất, fallback về products.image_url
  $sql = "
    SELECT p.id, p.name, p.price,
           COALESCE(
             (SELECT pi.image_url
                FROM product_images pi
               WHERE pi.product_id = p.id
               ORDER BY pi.sort_order ASC, pi.id ASC
               LIMIT 1),
             p.image_url
           ) AS image_url
    FROM products p
    WHERE p.id = ?
    LIMIT 1
  ";
  $stm = $conn->prepare($sql);
  $stm->bind_param('i', $id);
  $stm->execute();
  $res = $stm->get_result();
  $row = $res?->fetch_assoc() ?: null;
  $stm->close();

  if (!$row) return null;
  // chuẩn hoá đường dẫn ảnh
  $row['image_url'] = normalize_img($row['image_url'] ?? '');
  return $row;
}

/* ---- Handlers ---- */
if ($action === 'add') {
  $pid     = (int)($_POST['product_id'] ?? 0);
  $qty     = max(1, (int)($_POST['qty'] ?? 1));
  $variant = trim((string)($_POST['variant'] ?? '')); // dùng nếu có biến thể

  if ($pid <= 0) { header('Location: '.url_to('cart.php')); exit; }

  $p = find_product($pid);
  if ($p) {
    // key tách theo biến thể (nếu không dùng variant, key sẽ chỉ là id)
    $key = $p['id'] . '::' . $variant;

    if (!isset($_SESSION['cart'][$key])) {
      $_SESSION['cart'][$key] = [
        'id'        => (int)$p['id'],
        'name'      => (string)$p['name'],
        'price'     => (int)$p['price'],
        'variant'   => $variant,
        // LƯU CẢ 2 KEY ảnh cho chắc
        'thumbnail' => $p['image_url'],     // ví dụ: uploads/iphone.jpg
        'image_url' => $p['image_url'],
        'qty'       => 0,
      ];
    }
    $_SESSION['cart'][$key]['qty'] += $qty;
  }

  header('Location: '.url_to('cart.php')); exit;
}

if ($action === 'update') {
  // items["productId::variant"] = qty
  $items = $_POST['items'] ?? [];
  foreach ($items as $k => $qty) {
    $k = (string)$k; $qty = max(0, (int)$qty);
    if (!isset($_SESSION['cart'][$k])) continue;
    if ($qty === 0) unset($_SESSION['cart'][$k]);
    else $_SESSION['cart'][$k]['qty'] = $qty;
  }
  header('Location: '.url_to('cart.php')); exit;
}

if ($action === 'remove') {
  $k = (string)($_POST['key'] ?? '');
  unset($_SESSION['cart'][$k]);
  header('Location: '.url_to('cart.php')); exit;
}

if ($action === 'clear') {
  $_SESSION['cart'] = [];
  header('Location: '.url_to('cart.php')); exit;
}
