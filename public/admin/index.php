<?php
// public/admin/index.php
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
    echo "Lỗi CSDL: " . htmlspecialchars($e->getMessage());
    exit;
}

/* ===== Bộ lọc ngày ===== */
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$where = '';
$params = [];
$types = '';

if ($from !== '') {
    $where .= ($where ? ' AND' : ' WHERE') . ' created_at >= ?';
    $params[] = $from . ' 00:00:00';
    $types .= 's';
}
if ($to !== '') {
    $where .= ($where ? ' AND' : ' WHERE') . ' created_at <= ?';
    $params[] = $to . ' 23:59:59';
    $types .= 's';
}

/* ===== Số liệu tổng ===== */
// Doanh thu
$sqlRevenue = "SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ('paid','completed')";
if ($where)
    $sqlRevenue .= ' AND ' . substr($where, 7);
$stmt = $conn->prepare($sqlRevenue);
if ($stmt === false) {
    $revenue = 0;
} else {
    if ($types)
        $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->bind_result($revenue);
    $stmt->fetch();
    $stmt->close();
}

// Tổng đơn
$sqlOrders = "SELECT COUNT(*) FROM orders WHERE status IN ('paid','completed')";
if ($where)
    $sqlOrders .= ' AND ' . substr($where, 7);
$stmt = $conn->prepare($sqlOrders);
if ($stmt === false) {
    $totalOrders = 0;
} else {
    if ($types)
        $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->bind_result($totalOrders);
    $stmt->fetch();
    $stmt->close();
}

// Pending
$sqlPending = "SELECT COUNT(*) FROM orders WHERE status='pending'";
if ($where)
    $sqlPending .= ' AND ' . substr($where, 7);
$stmt = $conn->prepare($sqlPending);
if ($stmt === false) {
    $pendingOrders = 0;
} else {
    if ($types)
        $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->bind_result($pendingOrders);
    $stmt->fetch();
    $stmt->close();
}

// Sản phẩm & người dùng
$res = $conn->query("SELECT COUNT(*) FROM products");
$totalProducts = (int) ($res->fetch_row()[0] ?? 0);
$res = $conn->query("SELECT COUNT(*) FROM users WHERE role='user'");
$totalUsers = (int) ($res->fetch_row()[0] ?? 0);

/* ===== Đơn gần đây ===== */
$sqlRecent = "SELECT id, fullname, total, status, created_at FROM orders ORDER BY created_at DESC LIMIT 10";
$res = $conn->query($sqlRecent);
$recent = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

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
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
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
    <!-- GRID: sidebar + main -->
    <div class="h-screen w-full md:grid md:grid-cols-[260px_1fr] overflow-hidden">

        <!-- Sidebar (Cố định, scroll riêng) -->
        <aside class="hidden md:flex md:flex-col h-screen overflow-y-auto bg-white/80 glass border-r thin-scroll">
            <div class="px-5 py-4 border-b">
                <a href="<?= url_to('admin/index.php') ?>" class="flex items-center gap-3">
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
                <a href="<?= url_to('admin/index.php') ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-yellow-500 text-white">
                    <i class="fa-solid fa-chart-column w-5 text-center"></i><span>Tổng quan</span>
                </a>
                <a href="<?= url_to('admin/orders.php') ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100">
                    <i class="fa-solid fa-receipt w-5 text-center"></i><span>Đơn hàng</span>
                    <?php if ($pendingOrders): ?>
                        <span
                            class="ml-auto text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-800"><?= $pendingOrders ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= url_to('admin/products.php') ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100">
                    <i class="fa-solid fa-box-open w-5 text-center"></i><span>Sản phẩm</span>
                    <span
                        class="ml-auto text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-700"><?= $totalProducts ?></span>
                </a>
                <a href="<?= url_to('admin/users.php') ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100">
                    <i class="fa-solid fa-users w-5 text-center"></i><span>Người dùng</span>
                    <span
                        class="ml-auto text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-700"><?= $totalUsers ?></span>
                </a>
                <hr class="my-3">
                <a href="<?= url_to('logout.php') ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100 text-red-600">
                    <i class="fa-solid fa-right-from-bracket w-5 text-center"></i><span>Đăng xuất</span>
                </a>
            </nav>
        </aside>

        <!-- Main -->
        <div class="flex flex-col h-screen overflow-hidden">
            <!-- Topbar sticky -->
            <header class="bg-white border-b sticky top-0 z-20">
                <div class="px-4 md:px-6 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <!-- Mobile menu btn -->
                        <button
                            class="md:hidden inline-flex items-center justify-center h-10 w-10 rounded-xl border hover:bg-gray-50"
                            onclick="document.getElementById('mobileSidebar').classList.toggle('hidden')">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <div>
                            <div class="font-bold text-lg flex items-center gap-2">
                                <i class="fa-solid fa-chart-pie text-yellow-600"></i> Dashboard
                            </div>
                            <div class="text-xs text-gray-500">Xin chào,
                                <?= htmlspecialchars($_SESSION['auth']['name']) ?></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <?php if ($from || $to): ?>
                            <span class="text-xs px-2 py-1 rounded-lg bg-slate-100 text-slate-700">
                                <i class="fa-regular fa-calendar"></i>
                                <?= $from ?: '…' ?> → <?= $to ?: '…' ?>
                            </span>
                        <?php endif; ?>
                        <a href="<?= url_to('logout.php') ?>" class="px-3 py-1.5 rounded-xl border hover:bg-gray-50">
                            <i class="fa-solid fa-right-from-bracket mr-1"></i> Đăng xuất
                        </a>
                    </div>
                </div>
            </header>

            <!-- Scrollable content -->
            <main class="flex-1 overflow-y-auto px-4 md:px-6 py-6 space-y-6">
                <!-- Bộ lọc -->
                <form method="get" class="bg-white border rounded-2xl p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
                        <div class="sm:col-span-2">
                            <label class="text-sm text-gray-600 flex items-center gap-2">
                                <i class="fa-regular fa-calendar"></i> Từ ngày
                            </label>
                            <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"
                                class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 outline-none">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-sm text-gray-600 flex items-center gap-2">
                                <i class="fa-regular fa-calendar-check"></i> Đến ngày
                            </label>
                            <input type="date" name="to" value="<?= htmlspecialchars($to) ?>"
                                class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 outline-none">
                        </div>
                        <div>
                            <button class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg">
                                <i class="fa-solid fa-filter mr-1"></i> Lọc
                            </button>
                            <?php if ($from || $to): ?>
                                <a href="<?= url_to('admin/index.php') ?>"
                                    class="inline-block w-full mt-2 px-4 py-2 border rounded-lg text-center hover:bg-gray-50">
                                    <i class="fa-solid fa-rotate-right mr-1"></i> Bỏ lọc
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <!-- Cards -->
                <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
                    <div class="bg-white border rounded-2xl p-5">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-500">Doanh thu</div>
                            <span class="h-9 w-9 grid place-content-center rounded-xl bg-emerald-50 text-emerald-600">
                                <i class="fa-solid fa-sack-dollar"></i>
                            </span>
                        </div>
                        <div class="text-3xl font-extrabold mt-2"><?= vnd($revenue) ?></div>
                        <div class="text-xs text-gray-500 mt-1">đã thanh toán / hoàn tất</div>
                    </div>

                    <div class="bg-white border rounded-2xl p-5">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-500">Đơn đã thanh toán</div>
                            <span class="h-9 w-9 grid place-content-center rounded-xl bg-sky-50 text-sky-600">
                                <i class="fa-solid fa-cart-shopping"></i>
                            </span>
                        </div>
                        <div class="text-3xl font-extrabold mt-2"><?= (int) $totalOrders ?></div>
                        <div class="text-xs text-gray-500 mt-1">đơn hàng</div>
                    </div>

                    <div class="bg-white border rounded-2xl p-5">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-500">Đơn chờ xác nhận</div>
                            <span class="h-9 w-9 grid place-content-center rounded-xl bg-amber-50 text-amber-600">
                                <i class="fa-solid fa-hourglass-half"></i>
                            </span>
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <div class="text-3xl font-extrabold"><?= (int) $pendingOrders ?></div>
                            <a href="<?= url_to('admin/orders.php') ?>?status=pending"
                                class="px-3 py-1.5 rounded-lg bg-yellow-500 text-white hover:bg-yellow-600 text-sm">
                                <i class="fa-solid fa-bolt mr-1"></i> Xác nhận ngay
                            </a>
                        </div>
                    </div>

                    <div class="bg-white border rounded-2xl p-5">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-500">Sản phẩm</div>
                            <span class="h-9 w-9 grid place-content-center rounded-xl bg-indigo-50 text-indigo-600">
                                <i class="fa-solid fa-boxes-stacked"></i>
                            </span>
                        </div>
                        <div class="text-3xl font-extrabold mt-2"><?= (int) $totalProducts ?></div>
                        <div class="text-xs text-gray-500 mt-1">đang bán</div>
                    </div>

                    <div class="bg-white border rounded-2xl p-5">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-500">Người dùng</div>
                            <span class="h-9 w-9 grid place-content-center rounded-xl bg-fuchsia-50 text-fuchsia-600">
                                <i class="fa-solid fa-user-group"></i>
                            </span>
                        </div>
                        <div class="text-3xl font-extrabold mt-2"><?= (int) $totalUsers ?></div>
                        <div class="text-xs text-gray-500 mt-1">tài khoản</div>
                    </div>
                </section>

                <!-- Đơn gần đây -->
                <section class="bg-white border rounded-2xl p-5">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <h2 class="text-lg font-semibold flex items-center gap-2">
                            <i class="fa-solid fa-clock-rotate-left text-yellow-600"></i> Đơn hàng gần đây
                        </h2>
                        <a href="<?= url_to('admin/orders.php') ?>"
                            class="text-blue-600 text-sm hover:underline flex items-center gap-1">
                            Xem tất cả <i class="fa-solid fa-angle-right"></i>
                        </a>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left">
                                    <th class="px-3 py-2 border-b">Mã đơn</th>
                                    <th class="px-3 py-2 border-b">Khách hàng</th>
                                    <th class="px-3 py-2 border-b">Ngày tạo</th>
                                    <th class="px-3 py-2 border-b">Trạng thái</th>
                                    <th class="px-3 py-2 border-b text-right">Tổng tiền</th>
                                    <th class="px-3 py-2 border-b text-right"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$recent): ?>
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-gray-500">
                                            <i class="fa-regular fa-face-smile-wink mr-1"></i> Chưa có đơn hàng
                                        </td>
                                    </tr>
                                <?php else:
                                    foreach ($recent as $r): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 border-b font-medium">#<?= (int) $r['id'] ?></td>
                                            <td class="px-3 py-2 border-b"><?= htmlspecialchars($r['fullname'] ?? '-') ?></td>
                                            <td class="px-3 py-2 border-b"><?= htmlspecialchars($r['created_at']) ?></td>
                                            <td class="px-3 py-2 border-b">
                                                <span
                                                    class="px-2 py-0.5 rounded-full border text-xs <?= status_badge_classes($r['status']) ?>">
                                                    <?= htmlspecialchars($r['status']) ?>
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 border-b text-right font-semibold"><?= vnd($r['total']) ?></td>
                                            <td class="px-3 py-2 border-b text-right">
                                                <?php if (strtolower($r['status']) !== 'completed'): ?>
                                                    <a href="<?= url_to('admin/orders.php') ?>?status=pending"
                                                        class="text-blue-600 hover:underline">
                                                        <i class="fa-solid fa-check-to-slot mr-1"></i> Xác nhận
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-emerald-600"><i class="fa-solid fa-circle-check"></i></span>
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
                    © <?= date('Y') ?> Admin Dashboard • Built with TailwindCSS & Font Awesome
                </footer>
            </main>
        </div>
    </div>

    <!-- Mobile temporary sidebar (drawer) -->
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
                <a href="<?= url_to('admin/index.php') ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-yellow-500 text-white">
                    <i class="fa-solid fa-chart-column w-5 text-center"></i><span>Tổng quan</span>
                </a>
                <a href="<?= url_to('admin/orders.php') ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100">
                    <i class="fa-solid fa-receipt w-5 text-center"></i><span>Đơn hàng</span>
                    <?php if ($pendingOrders): ?>
                        <span
                            class="ml-auto text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-800"><?= $pendingOrders ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= url_to('admin/products.php') ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100">
                    <i class="fa-solid fa-box-open w-5 text-center"></i><span>Sản phẩm</span>
                </a>
                <a href="<?= url_to('admin/users.php') ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100">
                    <i class="fa-solid fa-users w-5 text-center"></i><span>Người dùng</span>
                </a>
                <hr class="my-3">
                <a href="<?= url_to('logout.php') ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100 text-red-600">
                    <i class="fa-solid fa-right-from-bracket w-5 text-center"></i><span>Đăng xuất</span>
                </a>
            </nav>
        </div>
    </div>
</body>

</html>