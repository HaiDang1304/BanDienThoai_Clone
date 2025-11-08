<?php
// public/admin/orders.php
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

/* ===== Handle actions (POST) ===== */
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['csrf'] ?? '') !== $_SESSION['csrf']) {
        $msg = 'CSRF token không hợp lệ.';
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';
        if ($id > 0 && in_array($action, ['complete', 'cancel'], true)) {
            $stmt = $conn->prepare(
                $action === 'complete'
                ? "UPDATE orders SET status='completed' WHERE id=?"
                : "UPDATE orders SET status='cancelled' WHERE id=?"
            );
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $msg = ($action === 'complete') ? "Đã xác nhận đơn #$id" : "Đã hủy đơn #$id";
            } else {
                $msg = 'Không thể cập nhật đơn.';
            }
            $stmt->close();
            // refresh CSRF + tránh double-submit
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
            header('Location: ' . url_to('admin/orders.php') . '?ok=1');
            exit;
        } else {
            $msg = 'Dữ liệu không hợp lệ.';
        }
    }
}

/* ===== Filters ===== */
$status = $_GET['status'] ?? 'pending'; // mặc định show pending
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$where = [];
$params = [];
$types = '';
if ($status !== 'all') {
    $where[] = 'status = ?';
    $params[] = $status;
    $types .= 's';
}
if ($from) {
    $where[] = 'created_at >= ?';
    $params[] = $from . ' 00:00:00';
    $types .= 's';
}
if ($to) {
    $where[] = 'created_at <= ?';
    $params[] = $to . ' 23:59:59';
    $types .= 's';
}
$sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* ===== Query orders list ===== */
$sql = "
  SELECT id, fullname, email, phone, total, status, created_at
  FROM orders
  $sqlWhere
  ORDER BY created_at DESC
  LIMIT 50
";
$stmt = $conn->prepare($sql);
if ($types)
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$orders = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

/* ===== Badge màu ===== */
function status_badge_classes(string $s): string
{
    $s = strtolower($s);
    return match ($s) {
        'completed' => 'bg-green-50 text-green-700 border-green-200',
        'paid' => 'bg-sky-50 text-sky-700 border-sky-200',
        'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
        'cancelled' => 'bg-red-50 text-red-700 border-red-200',
        default => 'bg-gray-50 text-gray-700 border-gray-200'
    };
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Quản lý đơn hàng</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
        .thin-scroll::-webkit-scrollbar {
            width: 6px;
            height: 6px
        }

        .thin-scroll::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 9999px
        }

        .glass {
            backdrop-filter: saturate(140%) blur(8px)
        }
    </style>
</head>

<body class="bg-[#f6f7fb] text-gray-800 overflow-hidden">

    <!-- GRID: sidebar + main (đồng bộ với index) -->
    <div class="h-screen w-full md:grid md:grid-cols-[260px_1fr] overflow-hidden">

        <!-- Sidebar cố định -->
        <aside class="hidden md:flex md:flex-col h-screen overflow-y-auto bg-white/80 glass border-r thin-scroll">
            <div class="px-5 py-4 border-b">
                <a href="<?= htmlspecialchars(url_to('admin/index.php')) ?>" class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-2xl bg-yellow-400/20 text-yellow-600 grid place-content-center">
                        <i class="fa-solid fa-gauge-high"></i>
                    </div>
                    <div>
                        <div class="font-extrabold text-lg">Admin</div>
                        <div class="text-xs text-gray-500">Bảng điều khiển</div>
                    </div>
                </a>
            </div>
            <nav class="p-3 space-y-1">
                <a href="<?= htmlspecialchars(url_to('admin/index.php')) ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100">
                    <i class="fa-solid fa-chart-column w-5 text-center"></i><span>Tổng quan</span>
                </a>
                <a href="<?= htmlspecialchars(url_to('admin/orders.php')) ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-yellow-500 text-white">
                    <i class="fa-solid fa-receipt w-5 text-center"></i><span>Đơn hàng</span>
                </a>
                <a href="<?= htmlspecialchars(url_to('admin/products.php')) ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100">
                    <i class="fa-solid fa-box-open w-5 text-center"></i><span>Sản phẩm</span>
                </a>
                <a href="<?= htmlspecialchars(url_to('admin/users.php')) ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100">
                    <i class="fa-solid fa-users w-5 text-center"></i><span>Người dùng</span>
                </a>
                <hr class="my-3">
                <a href="<?= htmlspecialchars(url_to('logout.php')) ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100 text-red-600">
                    <i class="fa-solid fa-right-from-bracket w-5 text-center"></i><span>Đăng xuất</span>
                </a>
            </nav>
        </aside>

        <!-- Main cột phải -->
        <div class="flex flex-col h-screen overflow-hidden">
            <!-- Topbar sticky -->
            <header class="bg-white border-b sticky top-0 z-20">
                <div class="px-4 md:px-6 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <!-- Nút mở sidebar mobile -->
                        <button
                            class="md:hidden inline-flex items-center justify-center h-10 w-10 rounded-xl border hover:bg-gray-50"
                            onclick="document.getElementById('mobileSidebar').classList.toggle('hidden')">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <div>
                            <div class="font-bold text-lg flex items-center gap-2">
                                <i class="fa-solid fa-receipt text-yellow-600"></i> Đơn hàng
                            </div>
                            <div class="text-xs text-gray-500">Xin chào,
                                <?= htmlspecialchars($_SESSION['auth']['name']) ?></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <?php if ($from || $to || ($status && $status !== 'pending')): ?>
                            <span class="text-xs px-2 py-1 rounded-lg bg-slate-100 text-slate-700">
                                <i class="fa-regular fa-calendar"></i>
                                <?= $from ? htmlspecialchars($from) : '…' ?> → <?= $to ? htmlspecialchars($to) : '…' ?>
                                • Trạng thái: <?= htmlspecialchars($status) ?>
                            </span>
                        <?php endif; ?>
                        <a href="<?= htmlspecialchars(url_to('logout.php')) ?>"
                            class="px-3 py-1.5 rounded-xl border hover:bg-gray-50">
                            <i class="fa-solid fa-right-from-bracket mr-1"></i> Đăng xuất
                        </a>
                    </div>
                </div>
            </header>

            <!-- Nội dung cuộn -->
            <main class="flex-1 overflow-y-auto px-4 md:px-6 py-6 space-y-6">
                <?php if (!empty($_GET['ok'])): ?>
                    <div class="bg-green-50 text-green-700 border border-green-200 rounded-xl px-4 py-2">
                        <i class="fa-solid fa-circle-check mr-1"></i> Thao tác thành công.
                    </div>
                <?php elseif ($msg): ?>
                    <div class="bg-red-50 text-red-700 border border-red-200 rounded-xl px-4 py-2">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> <?= htmlspecialchars($msg) ?>
                    </div>
                <?php endif; ?>

                <!-- Bộ lọc -->
                <form method="get" class="bg-white border rounded-2xl p-4">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                        <div>
                            <label class="text-sm text-gray-600 flex items-center gap-2">
                                <i class="fa-regular fa-rectangle-list"></i> Trạng thái
                            </label>
                            <select name="status" class="border rounded-lg px-3 py-2 w-full">
                                <?php
                                $statuses = [
                                    'pending' => 'pending',
                                    'paid' => 'paid',
                                    'completed' => 'completed',
                                    'cancelled' => 'cancelled',
                                    'all' => '(tất cả)'
                                ];
                                foreach ($statuses as $k => $label):
                                    ?>
                                    <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600 flex items-center gap-2">
                                <i class="fa-regular fa-calendar"></i> Từ ngày
                            </label>
                            <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"
                                class="border rounded-lg px-3 py-2 w-full">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600 flex items-center gap-2">
                                <i class="fa-regular fa-calendar-check"></i> Đến ngày
                            </label>
                            <input type="date" name="to" value="<?= htmlspecialchars($to) ?>"
                                class="border rounded-lg px-3 py-2 w-full">
                        </div>
                        <div class="md:col-span-2">
                            <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg">
                                <i class="fa-solid fa-filter mr-1"></i> Lọc
                            </button>
                            <?php if ($status !== 'pending' || $from || $to): ?>
                                <a href="<?= htmlspecialchars(url_to('admin/orders.php')) ?>"
                                    class="ml-2 px-4 py-2 border rounded-lg hover:bg-gray-50">
                                    <i class="fa-solid fa-rotate-right mr-1"></i> Bỏ lọc
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <!-- Danh sách đơn -->
                <section class="bg-white border rounded-2xl p-4">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <h2 class="text-lg font-semibold flex items-center gap-2">
                            <i class="fa-solid fa-list-check text-yellow-600"></i> Danh sách đơn (tối đa 50)
                        </h2>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left border-b">Mã đơn</th>
                                    <th class="px-3 py-2 text-left border-b">Khách hàng</th>
                                    <th class="px-3 py-2 text-left border-b">Liên hệ</th>
                                    <th class="px-3 py-2 text-left border-b">Ngày tạo</th>
                                    <th class="px-3 py-2 text-left border-b">Trạng thái</th>
                                    <th class="px-3 py-2 text-right border-b">Tổng tiền</th>
                                    <th class="px-3 py-2 text-right border-b">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$orders): ?>
                                    <tr>
                                        <td colspan="7" class="px-3 py-6 text-center text-gray-500">
                                            <i class="fa-regular fa-face-smile-wink mr-1"></i> Không có đơn nào.
                                        </td>
                                    </tr>
                                <?php else:
                                    foreach ($orders as $o): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 border-b font-medium">#<?= (int) $o['id'] ?></td>
                                            <td class="px-3 py-2 border-b"><?= htmlspecialchars($o['fullname'] ?? '-') ?></td>
                                            <td class="px-3 py-2 border-b">
                                                <div><?= htmlspecialchars($o['phone'] ?? '-') ?></div>
                                                <div class="text-gray-500"><?= htmlspecialchars($o['email'] ?? '-') ?></div>
                                            </td>
                                            <td class="px-3 py-2 border-b"><?= htmlspecialchars($o['created_at']) ?></td>
                                            <td class="px-3 py-2 border-b">
                                                <span
                                                    class="px-2 py-0.5 rounded-full border text-xs <?= status_badge_classes($o['status']) ?>">
                                                    <?= htmlspecialchars($o['status']) ?>
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 border-b text-right font-semibold"><?= vnd($o['total']) ?></td>
                                            <td class="px-3 py-2 border-b text-right">
                                                <?php if (strtolower($o['status']) !== 'completed'): ?>
                                                    <form method="post" class="inline">
                                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                                        <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                                                        <input type="hidden" name="action" value="complete">
                                                        <button class="px-3 py-1 rounded bg-green-600 text-white hover:bg-green-700"
                                                            onclick="return confirm('Xác nhận hoàn tất đơn #<?= (int) $o['id'] ?>?')">
                                                            <i class="fa-solid fa-check mr-1"></i> Xác nhận
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if (strtolower($o['status']) !== 'cancelled'): ?>
                                                    <form method="post" class="inline ml-2">
                                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                                        <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                                                        <input type="hidden" name="action" value="cancel">
                                                        <button class="px-3 py-1 rounded border hover:bg-gray-100"
                                                            onclick="return confirm('Hủy đơn #<?= (int) $o['id'] ?>?')">
                                                            <i class="fa-solid fa-xmark mr-1"></i> Hủy
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Footer -->
                <footer class="text-xs text-gray-500 py-4">
                    © <?= date('Y') ?> Admin • Orders
                </footer>
            </main>
        </div>
    </div>

    <!-- Sidebar mobile dạng drawer -->
    <div id="mobileSidebar" class="md:hidden hidden fixed inset-0 z-30">
        <div class="absolute inset-0 bg-black/30" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="absolute top-0 bottom-0 left-0 w-[260px] bg-white border-r thin-scroll overflow-y-auto">
            <div class="px-5 py-4 border-b flex items-center justify-between">
                <div class="font-bold">Menu</div>
                <button class="h-9 w-9 grid place-content-center rounded-lg hover:bg-gray-100"
                    onclick="document.getElementById('mobileSidebar').classList.add('hidden')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <nav class="p-3 space-y-1">
                <a href="<?= htmlspecialchars(url_to('admin/index.php')) ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100">
                    <i class="fa-solid fa-chart-column w-5 text-center"></i><span>Tổng quan</span>
                </a>
                <a href="<?= htmlspecialchars(url_to('admin/orders.php')) ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-yellow-500 text-white">
                    <i class="fa-solid fa-receipt w-5 text-center"></i><span>Đơn hàng</span>
                </a>
                <a href="<?= htmlspecialchars(url_to('admin/products.php')) ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100">
                    <i class="fa-solid fa-box-open w-5 text-center"></i><span>Sản phẩm</span>
                </a>
                <a href="<?= htmlspecialchars(url_to('admin/users.php')) ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100">
                    <i class="fa-solid fa-users w-5 text-center"></i><span>Người dùng</span>
                </a>
                <hr class="my-3">
                <a href="<?= htmlspecialchars(url_to('logout.php')) ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100 text-red-600">
                    <i class="fa-solid fa-right-from-bracket w-5 text-center"></i><span>Đăng xuất</span>
                </a>
            </nav>
        </div>
    </div>
</body>

</html>