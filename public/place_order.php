<?php
// public/place_order.php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE)
    session_start();

require_once __DIR__ . '/../app/config/database.php';

/* ===== Helpers ===== */
$BASE_URL = '/BanDienThoai_Clone/public';
function url_to(string $p)
{
    global $BASE_URL;
    return rtrim($BASE_URL, '/') . '/' . ltrim($p, '/');
}

/* ===== CSRF check ===== */
if (empty($_SESSION['csrf']) || ($_POST['csrf'] ?? '') !== $_SESSION['csrf']) {
    http_response_code(400);
    exit('CSRF token không hợp lệ.');
}

/* ===== Input form ===== */
$fullname = trim($_POST['fullname'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
$district = trim($_POST['district'] ?? '');
$province = trim($_POST['province'] ?? '');
$note = trim($_POST['note'] ?? '');
$shippingMethod = $_POST['shipping'] ?? 'standard';
$paymentMethod = $_POST['payment'] ?? 'cod';

/* Voucher (nếu có) */
$voucherCode = trim($_POST['voucher_code'] ?? '');
$voucherCode = ($voucherCode === '') ? null : $voucherCode;

if ($fullname === '' || $phone === '' || $address === '' || $district === '' || $province === '') {
    http_response_code(400);
    exit('Thiếu thông tin bắt buộc.');
}

/* ===== User info ===== */
$userId = (int) ($_SESSION['auth']['id'] ?? 0);
$userEmail = trim((string) ($_SESSION['auth']['email'] ?? ''));
$userPhone = trim((string) ($_SESSION['auth']['phone'] ?? ''));

if ($userEmail !== '')
    $email = $userEmail;
if ($userPhone !== '')
    $phone = $userPhone;

/* ===== Xác định sản phẩm ===== */
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$qtyBuyNow = isset($_POST['qty']) ? max(1, (int) $_POST['qty']) : 0;

function db_find_product(int $id): ?array
{
    $conn = db();
    $sql = "SELECT id,name,variant,price FROM products WHERE id=? LIMIT 1";
    $stm = $conn->prepare($sql);
    $stm->bind_param('i', $id);
    $stm->execute();
    $row = $stm->get_result()->fetch_assoc();
    $stm->close();
    return $row ?: null;
}

$items = [];
if ($productId > 0 && $qtyBuyNow > 0) {
    if ($p = db_find_product($productId)) {
        $items[] = [
            'id' => (int) $p['id'],
            'name' => (string) $p['name'],
            'variant' => (string) $p['variant'],
            'price' => (int) $p['price'],
            'qty' => $qtyBuyNow
        ];
    }
} else {
    $cart = $_SESSION['cart'] ?? [];
    foreach ($cart as $line) {
        if (empty($line['id']))
            continue;
        $items[] = [
            'id' => (int) $line['id'],
            'name' => (string) $line['name'],
            'variant' => (string) ($line['variant'] ?? ''),
            'price' => (int) $line['price'],
            'qty' => max(1, (int) $line['qty'])
        ];
    }
}

if (empty($items)) {
    header('Location: ' . url_to('cart.php'));
    exit;
}

/* ===== Tính tiền (tạm không áp dụng voucher vào discount ở đây) ===== */
$subtotal = 0;
foreach ($items as $it)
    $subtotal += $it['price'] * $it['qty'];
$shippingFee = ($subtotal > 5_000_000) ? 0 : 30000;
$discount = 0; // nếu có logic áp mã, bạn cập nhật biến này
$total = $subtotal + $shippingFee - $discount;

/* ===== Ghi DB ===== */
$conn = db();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->begin_transaction();

try {
    // Mã đơn hàng riêng (không liên quan mã voucher)
    $orderCode = 'DH' . date('ymdHis') . strtoupper(bin2hex(random_bytes(2)));
    $status = 'pending';

    // INSERT orders: thêm voucher_code (nullable)
    $sqlOrder = "INSERT INTO orders
      (user_id, code, voucher_code, fullname, phone, email, address, district, province, note,
       shipping_method, payment_method, subtotal, shipping_fee, discount, total, status, created_at)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, NOW())";

    $st = $conn->prepare($sqlOrder);

    $st->bind_param(
        'isssssssssssiiiis',
        $userId,        // i
        $orderCode,     // s
        $voucherCode,   // s (NULL nếu không có)
        $fullname,      // s
        $phone,         // s
        $email,         // s
        $address,       // s
        $district,      // s
        $province,      // s
        $note,          // s
        $shippingMethod,// s
        $paymentMethod, // s
        $subtotal,      // i
        $shippingFee,   // i
        $discount,      // i
        $total,         // i
        $status         // s
    );


    $st->execute();
    $orderId = $conn->insert_id;
    $st->close();

    // INSERT order_items
    $sqlItem = "INSERT INTO order_items (order_id, product_id, name, variant, price, qty)
                VALUES (?,?,?,?,?,?)";
    $stItem = $conn->prepare($sqlItem);
    foreach ($items as $it) {
        $stItem->bind_param(
            'iissii',
            $orderId,
            $it['id'],
            $it['name'],
            $it['variant'],
            $it['price'],
            $it['qty']
        );
        $stItem->execute();
    }
    $stItem->close();

    $conn->commit();

    if ($productId === 0)
        unset($_SESSION['cart']);

    header('Location: ' . url_to('order_success.php?code=' . urlencode($orderCode)));
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    echo "❌ Lỗi lưu đơn hàng: " . htmlspecialchars($e->getMessage());
    exit;
}
