<?php
// public/admin/products.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE)
    session_start();
require_once __DIR__ . '/../../app/config/database.php';

/* ===== Helpers ===== */
$BASE_URL = '/BanDienThoai_Clone/public';
function url_to(string $p)
{
    global $BASE_URL;
    return rtrim($BASE_URL, '/') . '/' . ltrim($p, '/');
}
function vnd($n)
{
    return number_format((int) $n, 0, ',', '.') . 'đ';
}
function flash($k, $v = null)
{
    if ($v === null) {
        $x = $_SESSION['flash'][$k] ?? null;
        unset($_SESSION['flash'][$k]);
        return $x;
    }
    $_SESSION['flash'][$k] = $v;
    return true;
}

/* Ảnh: chuẩn hoá mọi kiểu path về URL hợp lệ để <img> hiển thị */
function img_url(?string $p): string
{
    global $BASE_URL;
    $p = trim((string) $p);
    if ($p === '')
        return url_to('assets/images/no-image.png');
    if (preg_match('~^(https?:)?//~', $p))
        return $p;
    // đã là /BanDienThoai_Clone/public/...
    if (str_starts_with($p, rtrim($BASE_URL, '/') . '/'))
        return $p;
    // /public/... hoặc public/...
    $root = rtrim(dirname($BASE_URL), '/'); // /BanDienThoai_Clone
    if (str_starts_with($p, '/public/'))
        return $root . $p;
    $p = ltrim($p, '/');
    if (str_starts_with($p, 'public/'))
        return $root . '/' . $p;
    // coi như relative dưới /public
    return rtrim($BASE_URL, '/') . '/' . $p;
}

/* ===== Guard admin ===== */
if (empty($_SESSION['auth']) || (($_SESSION['auth']['role'] ?? 'user') !== 'admin')) {
    header('Location: ' . url_to('index.php'));
    exit;
}

/* ===== DB ===== */
try {
    $conn = db();
} catch (Exception $e) {
    http_response_code(500);
    echo "Lỗi CSDL";
    exit;
}

/* ===== CSRF ===== */
if (empty($_SESSION['csrf']))
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];
function ensure_csrf()
{
    if (empty($_POST['csrf']) || (string) $_POST['csrf'] !== (string) ($_SESSION['csrf'] ?? '')) {
        http_response_code(400);
        exit('CSRF token không hợp lệ');
    }
}

/* ===== Utils ===== */
// Lấy từ POST: nếu là mảng thì trả default (tránh trim(array))
function ip(string $k, string $def = ''): string
{
    $v = $_POST[$k] ?? $def;
    if (is_array($v))
        return $def;
    return trim((string) $v);
}
// GET an toàn với mảng
function ig(string $k, string $def = ''): string
{
    $v = $_GET[$k] ?? $def;
    if (is_array($v))
        return $def;
    return trim((string) $v);
}
function iint($v)
{
    return (int) (is_numeric($v) ? $v : 0);
}
function idbl($v)
{
    return (float) (is_numeric($v) ? $v : 0);
}
// Bind động cho mysqli
function bind_and_exec(mysqli_stmt $stmt, array $vals)
{
    $types = '';
    foreach ($vals as $v) {
        $types .= is_int($v) ? 'i' : (is_float($v) ? 'd' : 's');
    }
    $stmt->bind_param($types, ...$vals);
    return $stmt->execute();
}

/* ===== CRUD sản phẩm ===== */
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'create') {
    ensure_csrf();
    $name = ip('name');
    if ($name === '') {
        flash('err', 'Tên sản phẩm không được để trống');
        header('Location: ' . url_to('admin/products.php'));
        exit;
    }

    $variant = ip('variant') ?: null;
    $screen = ip('screen') ?: null;
    $size_inch = (ip('size_inch') !== '') ? idbl(ip('size_inch')) : null;
    $price = iint(ip('price'));
    $price_old = (ip('price_old') !== '') ? iint(ip('price_old')) : null;
    $gift_value = (ip('gift_value') !== '') ? iint(ip('gift_value')) : 0;
    $rating = (ip('rating') !== '') ? round((float) ip('rating'), 1) : 5.0;
    $sold_k = (ip('sold_k') !== '') ? iint(ip('sold_k')) : 0;
    $installment = isset($_POST['installment']) ? 1 : 0;
    $badge = ip('badge') ?: null;
    $image_url = ip('image_url') ?: null;

    $stmt = $conn->prepare("INSERT INTO products
    (name,variant,screen,size_inch,price,price_old,gift_value,rating,sold_k,installment,badge,image_url)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $ok = bind_and_exec($stmt, [$name, $variant, $screen, $size_inch, $price, $price_old, $gift_value, $rating, $sold_k, $installment, $badge, $image_url]);
    $stmt->close();
    $ok ? flash('ok', 'Đã thêm sản phẩm.') : flash('err', 'Không thể thêm sản phẩm.');
    header('Location: ' . url_to('admin/products.php'));
    exit;
}

if ($action === 'update') {
    ensure_csrf();
    $id = iint(ip('id'));
    if ($id <= 0) {
        flash('err', 'ID không hợp lệ');
        header('Location: ' . url_to('admin/products.php'));
        exit;
    }
    $name = ip('name');
    if ($name === '') {
        flash('err', 'Tên sản phẩm không được để trống');
        header('Location: ' . url_to('admin/products.php'));
        exit;
    }

    $variant = ip('variant') ?: null;
    $screen = ip('screen') ?: null;
    $size_inch = (ip('size_inch') !== '') ? idbl(ip('size_inch')) : null;
    $price = iint(ip('price'));
    $price_old = (ip('price_old') !== '') ? iint(ip('price_old')) : null;
    $gift_value = (ip('gift_value') !== '') ? iint(ip('gift_value')) : 0;
    $rating = (ip('rating') !== '') ? round((float) ip('rating'), 1) : 5.0;
    $sold_k = (ip('sold_k') !== '') ? iint(ip('sold_k')) : 0;
    $installment = isset($_POST['installment']) ? 1 : 0;
    $badge = ip('badge') ?: null;
    $image_url = ip('image_url') ?: null;

    $stmt = $conn->prepare("UPDATE products SET
    name=?, variant=?, screen=?, size_inch=?, price=?, price_old=?, gift_value=?, rating=?, sold_k=?, installment=?, badge=?, image_url=?
    WHERE id=?");
    $ok = bind_and_exec($stmt, [$name, $variant, $screen, $size_inch, $price, $price_old, $gift_value, $rating, $sold_k, $installment, $badge, $image_url, $id]);
    $stmt->close();
    $ok ? flash('ok', "Đã cập nhật sản phẩm #$id") : flash('err', 'Không thể cập nhật.');
    header('Location: ' . url_to('admin/products.php'));
    exit;
}

if ($action === 'delete') {
    ensure_csrf();
    $id = iint(ip('id'));
    if ($id <= 0) {
        flash('err', 'ID không hợp lệ');
        header('Location: ' . url_to('admin/products.php'));
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();
    $ok ? flash('ok', "Đã xóa sản phẩm #$id") : flash('err', 'Không thể xóa.');
    header('Location: ' . url_to('admin/products.php'));
    exit;
}

/* ===== CRUD ảnh (URL + Upload multiple + Reorder + Delete) ===== */
if ($action === 'add_image') {
    ensure_csrf();
    $pid = iint(ip('product_id'));
    $image_url = ip('image_url');
    $sort = iint(ip('sort_order'));
    if ($pid > 0 && $image_url !== '') {
        $stmt = $conn->prepare("INSERT INTO product_images(product_id,image_url,sort_order) VALUES(?,?,?)");
        $stmt->bind_param('isi', $pid, $image_url, $sort);
        $stmt->execute();
        $stmt->close();
        flash('ok', 'Đã thêm ảnh.');
    } else
        flash('err', 'Thiếu product_id hoặc image_url.');
    header('Location: ' . url_to('admin/products.php') . '?images=' . $pid);
    exit;
}

if ($action === 'upload_images') {
    ensure_csrf();
    $pid = (int) ($_POST['product_id'] ?? 0);
    if ($pid <= 0) {
        flash('err', 'Thiếu product_id');
        header('Location: ' . url_to('admin/products.php'));
        exit;
    }

    $projectRoot = rtrim(dirname(__DIR__, 2), DIRECTORY_SEPARATOR); // .../BanDienThoai_Clone
    $destDir = $projectRoot . '/public/assets/images/products';
    if (!is_dir($destDir))
        @mkdir($destDir, 0777, true);

    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $moved = 0;

    if (!empty($_FILES['files']) && is_array($_FILES['files']['name'])) {
        $count = count($_FILES['files']['name']);
        for ($i = 0; $i < $count; $i++) {
            if (($_FILES['files']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)
                continue;
            $tmp = $_FILES['files']['tmp_name'][$i];
            $name = $_FILES['files']['name'][$i];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true))
                continue;
            $safe = preg_replace('~[^a-zA-Z0-9._-]+~', '-', pathinfo($name, PATHINFO_FILENAME));
            $final = $safe . '-' . time() . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
            if (move_uploaded_file($tmp, $destDir . '/' . $final)) {
                $dbPath = '/BanDienThoai_Clone/public/assets/images/products/' . $final;
                $stmt = $conn->prepare("INSERT INTO product_images(product_id,image_url,sort_order) VALUES(?,?,0)");
                $stmt->bind_param('is', $pid, $dbPath);
                $stmt->execute();
                $stmt->close();
                $moved++;
            }
        }
    }
    $moved ? flash('ok', "Đã upload $moved ảnh.") : flash('err', 'Không có ảnh nào được upload.');
    header('Location: ' . url_to('admin/products.php') . '?images=' . $pid);
    exit;
}

if ($action === 'delete_image') {
    ensure_csrf();
    $pid = iint(ip('product_id'));
    $iid = iint(ip('id'));
    if ($pid > 0 && $iid > 0) {
        $stmt = $conn->prepare("DELETE FROM product_images WHERE id=? AND product_id=?");
        $stmt->bind_param('ii', $iid, $pid);
        $stmt->execute();
        $stmt->close();
        flash('ok', 'Đã xóa ảnh.');
    }
    header('Location: ' . url_to('admin/products.php') . '?images=' . $pid);
    exit;
}

if ($action === 'reorder_images') {
    ensure_csrf();
    $pid = iint(ip('product_id'));
    $ids = $_POST['id'] ?? [];
    $sorts = $_POST['sort'] ?? [];
    if ($pid > 0 && is_array($ids) && is_array($sorts)) {
        $stmt = $conn->prepare("UPDATE product_images SET sort_order=? WHERE id=? AND product_id=?");
        foreach ($ids as $i => $idv) {
            $so = (int) ($sorts[$i] ?? 0);
            $idi = (int) $idv;
            $stmt->bind_param('iii', $so, $idi, $pid);
            $stmt->execute();
        }
        $stmt->close();
        flash('ok', 'Đã cập nhật thứ tự.');
    }
    header('Location: ' . url_to('admin/products.php') . '?images=' . $pid);
    exit;
}

/* ===== Filters (list) ===== */
$q = ig('q', '');
$min = ig('min', '');
$max = ig('max', '');
$where = [];
$params = [];
$types = '';
if ($q !== '') {
    $where[] = 'name LIKE ?';
    $params[] = "%$q%";
    $types .= 's';
}
if ($min !== '' && is_numeric($min)) {
    $where[] = 'price >= ?';
    $params[] = (int) $min;
    $types .= 'i';
}
if ($max !== '' && is_numeric($max)) {
    $where[] = 'price <= ?';
    $params[] = (int) $max;
    $types .= 'i';
}
$sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* ===== Query list (đủ cột cho modal sửa) ===== */
$sql = "SELECT id,name,variant,screen,size_inch,price,price_old,gift_value,rating,sold_k,installment,badge,image_url
        FROM products $sqlWhere ORDER BY id DESC LIMIT 200";
$stmt = $conn->prepare($sql);
if ($types)
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$items = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

/* ===== Nếu mở modal ảnh qua ?images=ID ===== */
$imagesPid = (int) ($_GET['images'] ?? 0);
$imagesProduct = null;
$images = [];
if ($imagesPid > 0) {
    $stmt = $conn->prepare("SELECT id,name FROM products WHERE id=?");
    $stmt->bind_param('i', $imagesPid);
    $stmt->execute();
    $imagesProduct = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($imagesProduct) {
        $stmt = $conn->prepare("SELECT id,image_url,sort_order FROM product_images WHERE product_id=? ORDER BY sort_order,id");
        $stmt->bind_param('i', $imagesPid);
        $stmt->execute();
        $images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>QL Sản phẩm</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
        .modal {
            display: none
        }

        .modal.show {
            display: flex
        }
    </style>
</head>

<body class="bg-[#f6f7fb] text-gray-800">
    <div class="min-h-screen grid md:grid-cols-[260px_1fr]">

        <!-- Sidebar -->
        <aside class="hidden md:flex md:flex-col bg-white border-r">
            <div class="px-5 py-4 border-b">
                <a href="<?= htmlspecialchars(url_to('admin/index.php')) ?>" class="flex items-center gap-2">
                    <div class="h-9 w-9 rounded-2xl bg-yellow-400/20 text-yellow-600 grid place-content-center"><i
                            class="fa-solid fa-gauge-high"></i></div>
                    <div>
                        <div class="font-extrabold">Admin</div>
                        <div class="text-xs text-gray-500">Bảng điều khiển</div>
                    </div>
                </a>
            </div>
            <nav class="p-3 space-y-1">
                <a href="<?= htmlspecialchars(url_to('admin/index.php')) ?>"
                    class="px-3 py-2 rounded hover:bg-gray-100 block">Tổng quan</a>
                <a href="<?= htmlspecialchars(url_to('admin/orders.php')) ?>"
                    class="px-3 py-2 rounded hover:bg-gray-100 block">Đơn hàng</a>
                <a href="<?= htmlspecialchars(url_to('admin/products.php')) ?>"
                    class="px-3 py-2 rounded bg-yellow-500 text-white block">Sản phẩm</a>
                <a href="<?= htmlspecialchars(url_to('admin/users.php')) ?>"
                    class="px-3 py-2 rounded hover:bg-gray-100 block">Người dùng</a>
                <hr class="my-2">
                <a href="<?= htmlspecialchars(url_to('logout.php')) ?>"
                    class="px-3 py-2 rounded hover:bg-gray-100 text-red-600 block">Đăng xuất</a>
            </nav>
        </aside>

        <!-- Main -->
        <main class="p-4 md:p-6 space-y-6">
            <header class="flex items-center justify-between">
                <div>
                    <div class="font-bold text-lg">Sản phẩm</div>
                    <div class="text-xs text-gray-500">Xin chào, <?= htmlspecialchars($_SESSION['auth']['name']) ?>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button id="openAdd" class="px-3 py-2 rounded-lg bg-yellow-500 text-white hover:bg-yellow-600">
                        <i class="fa-solid fa-plus mr-1"></i> Thêm sản phẩm
                    </button>
                    <a href="<?= htmlspecialchars(url_to('logout.php')) ?>"
                        class="px-3 py-2 rounded-lg border hover:bg-gray-50">Đăng xuất</a>
                </div>
            </header>

            <?php if ($m = flash('ok')): ?>
                <div class="p-3 bg-green-50 border border-green-200 rounded text-green-700"><?= htmlspecialchars($m) ?>
                </div>
            <?php endif;
            if ($m = flash('err')): ?>
                <div class="p-3 bg-red-50 border border-red-200 rounded text-red-700"><?= htmlspecialchars($m) ?></div>
            <?php endif; ?>

            <!-- Lọc -->
            <form method="get" class="bg-white border rounded-2xl p-4">
                <div class="grid lg:grid-cols-6 gap-3 items-end">
                    <div class="lg:col-span-3">
                        <label class="text-sm text-gray-600">Tìm theo tên</label>
                        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
                            class="w-full border rounded px-3 py-2">
                    </div>
                    <div><label class="text-sm text-gray-600">Giá tối thiểu</label><input type="number" name="min"
                            value="<?= htmlspecialchars($min) ?>" class="w-full border rounded px-3 py-2"></div>
                    <div><label class="text-sm text-gray-600">Giá tối đa</label><input type="number" name="max"
                            value="<?= htmlspecialchars($max) ?>" class="w-full border rounded px-3 py-2"></div>
                    <div><button class="w-full bg-gray-900 text-white rounded px-4 py-2">Lọc</button></div>
                </div>
            </form>

            <!-- Danh sách -->
            <section class="bg-white border rounded-2xl p-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold">Danh sách sản phẩm</h2>
                    <div class="text-sm text-gray-500">Tổng: <?= count($items) ?></div>
                </div>

                <div class="overflow-x-auto mt-3">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left border-b">#</th>
                                <th class="px-3 py-2 text-left border-b">Sản phẩm</th>
                                <th class="px-3 py-2 text-left border-b">Giá</th>
                                <th class="px-3 py-2 text-left border-b">Giá cũ</th>
                                <th class="px-3 py-2 text-left border-b">Rating</th>
                                <th class="px-3 py-2 text-right border-b">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$items): ?>
                                <tr>
                                    <td colspan="6" class="px-3 py-6 text-center text-gray-500">Không có sản phẩm.</td>
                                </tr>
                            <?php else:
                                foreach ($items as $p): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2 border-b">#<?= (int) $p['id'] ?></td>
                                        <td class="px-3 py-2 border-b">
                                            <div class="flex items-center gap-3">
                                                <div class="w-12 h-12 rounded overflow-hidden bg-gray-100 border">
                                                    <img src="<?= htmlspecialchars(img_url($p['image_url'] ?? '')) ?>"
                                                        class="w-full h-full object-cover" alt="">
                                                </div>
                                                <div>
                                                    <div class="font-medium"><?= htmlspecialchars($p['name']) ?></div>
                                                    <div class="text-xs text-gray-500">
                                                        <?= htmlspecialchars($p['variant'] ?? '') ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 border-b"><?= vnd($p['price']) ?></td>
                                        <td
                                            class="px-3 py-2 border-b <?= ((int) $p['price_old'] > (int) $p['price']) ? 'line-through text-gray-500' : '' ?>">
                                            <?= $p['price_old'] ? vnd($p['price_old']) : '-' ?>
                                        </td>
                                        <td class="px-3 py-2 border-b">
                                            <?= htmlspecialchars(number_format((float) $p['rating'], 1)) ?></td>
                                        <td class="px-3 py-2 border-b text-right space-x-1">
                                            <button class="px-3 py-1 rounded border hover:bg-gray-100" onclick="openEdit(this)"
                                                <?php foreach ($p as $k => $v): ?> data-<?= htmlspecialchars($k) ?>="<?= htmlspecialchars((string) $v) ?>" <?php endforeach; ?>>
                                                <i class="fa-regular fa-pen-to-square mr-1"></i>Sửa
                                            </button>
                                            <a class="px-3 py-1 rounded border hover:bg-gray-100"
                                                href="<?= htmlspecialchars(url_to('admin/products.php')) . '?images=' . (int) $p['id'] ?>">
                                                <i class="fa-regular fa-images mr-1"></i>Ảnh
                                            </a>
                                            <form method="post" class="inline"
                                                onsubmit="return confirm('Xóa sản phẩm #<?= (int) $p['id'] ?>?')">
                                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                                <button class="px-3 py-1 rounded border text-red-600 hover:bg-red-50">
                                                    <i class="fa-regular fa-trash-can mr-1"></i>Xóa
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <footer class="text-xs text-gray-500">© <?= date('Y') ?> Admin • Products</footer>
        </main>
    </div>

    <!-- ========== MODALS ========== -->

    <!-- Add Modal -->
    <div id="addModal" class="modal fixed inset-0 z-50 bg-black/50 items-center justify-center p-4">
        <div class="bg-white w-full max-w-2xl rounded-2xl overflow-hidden"> <!-- nhỏ hơn -->
            <div class="px-5 py-3 border-b flex items-center justify-between">
                <h3 class="font-semibold">Thêm sản phẩm</h3>
                <button class="h-9 w-9 rounded hover:bg-gray-100" onclick="closeAdd()"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="post" class="p-5 grid md:grid-cols-3 gap-3">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="create">
                <div class="md:col-span-2"><label class="text-sm">Tên *</label><input name="name" required
                        class="w-full border rounded px-3 py-2"></div>
                <div><label class="text-sm">Variant</label><input name="variant"
                        class="w-full border rounded px-3 py-2"></div>
                <div><label class="text-sm">Màn hình</label><input name="screen"
                        class="w-full border rounded px-3 py-2"></div>
                <div><label class="text-sm">Kích thước (inch)</label><input name="size_inch" type="number" step="0.1"
                        class="w-full border rounded px-3 py-2"></div>
                <div><label class="text-sm">Giá *</label><input name="price" type="number" min="0" required
                        class="w-full border rounded px-3 py-2"></div>
                <div><label class="text-sm">Giá cũ</label><input name="price_old" type="number" min="0"
                        class="w-full border rounded px-3 py-2"></div>
                <div><label class="text-sm">Quà tặng</label><input name="gift_value" type="number" min="0"
                        class="w-full border rounded px-3 py-2" value="0"></div>
                <div><label class="text-sm">Rating (0-5)</label><input name="rating" type="number" step="0.1" min="0"
                        max="5" class="w-full border rounded px-3 py-2" value="5.0"></div>
                <div><label class="text-sm">Đã bán (k)</label><input name="sold_k" type="number" min="0"
                        class="w-full border rounded px-3 py-2" value="0"></div>
                <div class="flex items-end"><label class="text-sm mr-3">Trả góp</label><input name="installment"
                        type="checkbox" class="h-5 w-5"></div>
                <div><label class="text-sm">Badge</label><input name="badge" class="w-full border rounded px-3 py-2">
                </div>
                <div class="md:col-span-3"><label class="text-sm">Ảnh chính (đường dẫn)</label>
                    <input name="image_url" class="w-full border rounded px-3 py-2"
                        placeholder="/BanDienThoai_Clone/public/assets/images/products/xxx.jpg">
                </div>
                <div class="md:col-span-3 flex justify-end gap-2 pt-2">
                    <button type="button" class="px-4 py-2 rounded border hover:bg-gray-50"
                        onclick="closeAdd()">Hủy</button>
                    <button class="px-4 py-2 rounded bg-yellow-500 text-white hover:bg-yellow-600"><i
                            class="fa-regular fa-floppy-disk mr-1"></i> Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal fixed inset-0 z-50 bg-black/50 items-center justify-center p-4">
        <div class="bg-white w-full max-w-2xl rounded-2xl overflow-hidden"> <!-- nhỏ hơn -->
            <div class="px-5 py-3 border-b flex items-center justify-between">
                <h3 class="font-semibold">Sửa sản phẩm</h3>
                <button class="h-9 w-9 rounded hover:bg-gray-100" onclick="closeEdit()"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="post" class="p-5 grid md:grid-cols-3 gap-3" id="editForm">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="e_id">
                <div class="md:col-span-2"><label class="text-sm">Tên *</label><input name="name" id="e_name" required
                        class="w-full border rounded px-3 py-2"></div>
                <div><label class="text-sm">Variant</label><input name="variant" id="e_variant"
                        class="w-full border rounded px-3 py-2"></div>
                <div><label class="text-sm">Màn hình</label><input name="screen" id="e_screen"
                        class="w-full border rounded px-3 py-2"></div>
                <div><label class="text-sm">Kích thước (inch)</label><input name="size_inch" id="e_size_inch"
                        type="number" step="0.1" class="w-full border rounded px-3 py-2"></div>
                <div><label class="text-sm">Giá *</label><input name="price" id="e_price" type="number" min="0" required
                        class="w-full border rounded px-3 py-2"></div>
                <div><label class="text-sm">Giá cũ</label><input name="price_old" id="e_price_old" type="number" min="0"
                        class="w-full border rounded px-3 py-2"></div>
                <div><label class="text-sm">Quà tặng</label><input name="gift_value" id="e_gift_value" type="number"
                        min="0" class="w-full border rounded px-3 py-2"></div>
                <div><label class="text-sm">Rating (0-5)</label><input name="rating" id="e_rating" type="number"
                        step="0.1" min="0" max="5" class="w-full border rounded px-3 py-2"></div>
                <div><label class="text-sm">Đã bán (k)</label><input name="sold_k" id="e_sold_k" type="number" min="0"
                        class="w-full border rounded px-3 py-2"></div>
                <div class="flex items-end"><label class="text-sm mr-3">Trả góp</label><input name="installment"
                        id="e_installment" type="checkbox" class="h-5 w-5"></div>
                <div><label class="text-sm">Badge</label><input name="badge" id="e_badge"
                        class="w-full border rounded px-3 py-2"></div>
                <div class="md:col-span-3"><label class="text-sm">Ảnh chính (đường dẫn)</label><input name="image_url"
                        id="e_image_url" class="w-full border rounded px-3 py-2"></div>
                <div class="md:col-span-3 flex justify-end gap-2 pt-2">
                    <button type="button" class="px-4 py-2 rounded border hover:bg-gray-50"
                        onclick="closeEdit()">Hủy</button>
                    <button class="px-4 py-2 rounded bg-yellow-500 text-white hover:bg-yellow-600"><i
                            class="fa-regular fa-floppy-disk mr-1"></i> Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Images Modal (đÃ THU NHỎ) -->
    <div id="imagesModal" class="modal fixed inset-0 z-50 bg-black/50 items-center justify-center p-4">
        <div class="bg-white w-full max-w-3xl rounded-2xl overflow-hidden"> <!-- từ 5xl -> 3xl -->
            <div class="px-5 py-3 border-b flex items-center justify-between">
                <h3 class="font-semibold">
                    Ảnh sản
                    phẩm<?= $imagesProduct ? ': ' . htmlspecialchars($imagesProduct['name']) . ' (#' . (int) $imagesProduct['id'] . ')' : '' ?>
                </h3>
                <a class="h-9 w-9 grid place-content-center rounded hover:bg-gray-100"
                    href="<?= htmlspecialchars(url_to('admin/products.php')) ?>">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            </div>

            <?php if ($imagesProduct): ?>
                <!-- Thêm ảnh qua URL -->
                <form method="post" class="p-4 grid md:grid-cols-6 gap-3 border-b">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="action" value="add_image">
                    <input type="hidden" name="product_id" value="<?= (int) $imagesProduct['id'] ?>">
                    <input type="text" name="image_url" class="md:col-span-4 w-full border rounded px-3 py-2"
                        placeholder="/BanDienThoai_Clone/public/assets/images/products/xxx_2.jpg" required>
                    <input type="number" name="sort_order" class="w-full border rounded px-3 py-2" value="0" min="0">
                    <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">Thêm ảnh</button>
                </form>

                <!-- Upload nhiều ảnh -->
                <form method="post" enctype="multipart/form-data" class="p-4 grid md:grid-cols-6 gap-3 border-b">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="action" value="upload_images">
                    <input type="hidden" name="product_id" value="<?= (int) $imagesProduct['id'] ?>">
                    <div class="md:col-span-4">
                        <label class="text-sm text-gray-600">Upload nhiều ảnh (jpg, png, webp, gif)</label>
                        <input type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.gif"
                            class="w-full border rounded px-3 py-2 bg-white">
                    </div>
                    <div class="md:col-span-2 flex items-end">
                        <button class="w-full bg-gray-900 text-white px-4 py-2 rounded">Tải lên</button>
                    </div>
                </form>

                <!-- Reorder -->
                <form method="post" class="p-4">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="action" value="reorder_images">
                    <input type="hidden" name="product_id" value="<?= (int) $imagesProduct['id'] ?>">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4"> <!-- lưới nhỏ lại -->
                        <?php foreach ($images as $img): ?>
                            <div class="border rounded-xl overflow-hidden">
                                <div class="bg-gray-100 h-44 grid place-items-center"> <!-- chiều cao cố định -->
                                    <img src="<?= htmlspecialchars(img_url($img['image_url'])) ?>"
                                        class="max-h-40 object-contain" alt="">
                                </div>
                                <div class="p-3 flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <input type="hidden" name="id[]" value="<?= (int) $img['id'] ?>">
                                        <label class="text-sm text-gray-600">Thứ tự</label>
                                        <input type="number" name="sort[]" value="<?= (int) $img['sort_order'] ?>"
                                            class="w-20 border rounded px-2 py-1">
                                    </div>
                                    <!-- Nút xóa dùng form rời (tránh lồng form) -->
                                    <button type="submit" form="del-<?= (int) $img['id'] ?>"
                                        class="px-3 py-1 rounded border text-red-600 hover:bg-red-50">Xóa</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($images): ?>
                        <div class="mt-4 text-right">
                            <button class="px-4 py-2 rounded bg-gray-900 text-white">Lưu thứ tự</button>
                        </div>
                    <?php endif; ?>
                </form>

                <!-- Các form xoá rời -->
                <?php foreach ($images as $img): ?>
                    <form id="del-<?= (int) $img['id'] ?>" method="post" class="hidden"
                        onsubmit="return confirm('Xóa ảnh này?')">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="action" value="delete_image">
                        <input type="hidden" name="product_id" value="<?= (int) $imagesProduct['id'] ?>">
                        <input type="hidden" name="id" value="<?= (int) $img['id'] ?>">
                    </form>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const addModal = document.getElementById('addModal');
        const editModal = document.getElementById('editModal');
        const imagesModal = document.getElementById('imagesModal');

        document.getElementById('openAdd').addEventListener('click', () => {
            addModal.classList.add('show', 'items-center', 'justify-center');
        });
        function closeAdd() { addModal.classList.remove('show'); }
        function closeEdit() { editModal.classList.remove('show'); }

        // Đổ dữ liệu vào modal sửa từ data-*
        function openEdit(btn) {
            const d = btn.dataset;
            const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = (val ?? ''); }
            document.getElementById('e_id').value = d.id || '';
            setVal('e_name', d.name); setVal('e_variant', d.variant); setVal('e_screen', d.screen);
            setVal('e_size_inch', d.size_inch); setVal('e_price', d.price); setVal('e_price_old', d.price_old);
            setVal('e_gift_value', d.gift_value); setVal('e_rating', d.rating); setVal('e_sold_k', d.sold_k);
            document.getElementById('e_installment').checked = (parseInt(d.installment || '0', 10) === 1);
            setVal('e_badge', d.badge); setVal('e_image_url', d.image_url);
            editModal.classList.add('show', 'items-center', 'justify-center');
        }

        [addModal, editModal, imagesModal].forEach(m => {
            m.addEventListener('click', (e) => { if (e.target === m) m.classList.remove('show'); });
        });

        <?php if ($imagesPid > 0 && $imagesProduct): ?>
            imagesModal.classList.add('show', 'items-center', 'justify-center');
        <?php endif; ?>
    </script>
</body>

</html>