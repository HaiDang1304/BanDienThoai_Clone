<?php
// public/checkout_submit.php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once __DIR__ . '/../app/config/database.php';

/* Helpers */
$BASE_URL = '/BanDienThoai_Clone/public';
function url_to(string $path): string {
  global $BASE_URL;
  return rtrim($BASE_URL, '/') . '/' . ltrim($path, '/');
}
function vnd($n){ return number_format((int)$n, 0, ',', '.') . 'đ'; }

/* CSRF check */
if (empty($_POST['csrf']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf'])) {
  http_response_code(400);
  exit('Yêu cầu không hợp lệ (CSRF).');
}

/* Lấy input */
$productId = (int)($_POST['product_id'] ?? 0);
$qty       = max(1, (int)($_POST['qty'] ?? 1));
$fullname  = trim((string)($_POST['fullname'] ?? ''));
$phone     = trim((string)($_POST['phone'] ?? ''));
$email     = trim((string)($_POST['email'] ?? ''));
$address   = trim((string)($_POST['address'] ?? ''));
$district  = trim((string)($_POST['district'] ?? ''));
$province  = trim((string)($_POST['province'] ?? ''));
$note      = trim((string)($_POST['note'] ?? ''));
$shippingMethod = $_POST['shipping_method'] ?? 'standard';
$paymentMethod  = $_POST['payment_method'] ?? 'cod';
$voucherCode    = trim((string)($_POST['voucher_code'] ?? ''));

if ($productId <= 0 || $fullname === '' || $phone === '' || $address === '' || $district === '' || $province === '') {
  http_response_code(400);
  exit('Thiếu thông tin bắt buộc.');
}

try { $conn = db(); } 
catch (Exception $e) { http_response_code(500); exit('Không kết nối được CSDL'); }

/* Lấy sản phẩm */
$stmt = $conn->prepare("SELECT id,name,variant,price FROM products WHERE id=?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$product) { http_response_code(404); exit('Sản phẩm không tồn tại'); }

/* Tính tiền cơ bản */
$subTotal = (int)$product['price'] * $qty;
$shipping = 30000;
$discount = 0;

/* (Tùy chọn) Áp voucher nếu có bảng vouchers(code, type:percent/fixed, value,int, active, expiry_date) */
if ($voucherCode !== '') {
  if ($v = $conn->prepare("SELECT type,value,expiry_date,active FROM vouchers WHERE code=?")) {
    $v->bind_param("s", $voucherCode);
    $v->execute();
    $vr = $v->get_result()->fetch_assoc();
    $v->close();
    if ($vr && (int)$vr['active'] === 1 && (empty($vr['expiry_date']) || strtotime($vr['expiry_date']) >= time())) {
      if ($vr['type'] === 'percent') {
        $discount = (int) floor($subTotal * ((int)$vr['value'] / 100));
      } else {
        $discount = (int)$vr['value'];
      }
      if ($discount > $subTotal) $discount = $subTotal;
    }
  }
}
$total = $subTotal + $shipping - $discount;

/* Ghi đơn hàng (bảng mẫu: orders, order_items)
   - orders(id, code, fullname, phone, email, address, district, province, note, shipping_method, payment_method, subtotal, shipping_fee, discount, total, status, created_at)
   - order_items(id, order_id, product_id, name, variant, price, qty)
*/
$conn->begin_transaction();
try {
  $code = 'OD' . date('ymdHis') . rand(100,999);

  $o = $conn->prepare("INSERT INTO orders
    (code, fullname, phone, email, address, district, province, note, shipping_method, payment_method, subtotal, shipping_fee, discount, total, status, created_at)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
  $status = 'pending'; // chờ xác nhận
  $o->bind_param(
    "ssssssssssiiiis",
    $code, $fullname, $phone, $email, $address, $district, $province, $note,
    $shippingMethod, $paymentMethod, $subTotal, $shipping, $discount, $total, $status
  );
  $o->execute();
  $orderId = (int)$o->insert_id;
  $o->close();

  $oi = $conn->prepare("INSERT INTO order_items (order_id, product_id, name, variant, price, qty)
                        VALUES (?,?,?,?,?,?)");
  $pname = (string)$product['name'];
  $pvar  = (string)$product['variant'];
  $pprice= (int)$product['price'];
  $oi->bind_param("iissii", $orderId, $productId, $pname, $pvar, $pprice, $qty);
  $oi->execute();
  $oi->close();

  $conn->commit();

  // Thanh toán online demo: điều hướng tới cổng thực tế ở đây (MoMo/VNPAY)
  if ($paymentMethod === 'momo' || $paymentMethod === 'vnpay') {
    // TODO: tạo URL thanh toán và redirect
    // For now, coi như thành công giả lập:
    header('Location: ' . url_to('order_success.php?code=' . urlencode($code)));
    exit;
  }

  // COD → chuyển trang cảm ơn
  header('Location: ' . url_to('order_success.php?code=' . urlencode($code)));
  exit;
} catch (Throwable $e) {
  $conn->rollback();
  http_response_code(500);
  echo "Không thể tạo đơn hàng. Lỗi: " . htmlspecialchars($e->getMessage());
  exit;
}
