<?php
// public/admin/users.php
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

/* ===== Handle role changes ===== */
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['csrf'] ?? '') !== $_SESSION['csrf']) {
        $msg = 'CSRF token không hợp lệ.';
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $role = $_POST['role'] ?? '';
        if ($id > 0 && in_array($role, ['user', 'admin'], true)) {
            $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
            $stmt->bind_param('si', $role, $id);
            if ($stmt->execute())
                $msg = "Đã đổi quyền người dùng #$id thành $role";
            else
                $msg = 'Không thể cập nhật quyền.';
            $stmt->close();
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
            header('Location: ' . url_to('admin/users.php') . '?ok=1');
            exit;
        } else {
            $msg = 'Dữ liệu không hợp lệ.';
        }
    }
}

/* ===== Filters ===== */
$q = trim($_GET['q'] ?? '');
$role = $_GET['role'] ?? 'all';

$where = [];
$params = [];
$types = '';

if ($q !== '') {
    $where[] = '(name LIKE ? OR email LIKE ?)';
    $params[] = "%$q%";
    $types .= 's';
    $params[] = "%$q%";
    $types .= 's';
}
if ($role !== 'all') {
    $where[] = 'role = ?';
    $params[] = $role;
    $types .= 's';
}
$sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* ===== Query users =====
   Lấy các cột chắc chắn: id, name, email, role, avatar (nếu có) */
$sql = "
  SELECT id, name, email, role, COALESCE(avatar,'') AS avatar
  FROM users
  $sqlWhere
  ORDER BY id DESC
  LIMIT 50
";
$stmt = $conn->prepare($sql);
if ($types)
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$users = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

function role_badge_classes(string $r): string
{
    return ($r === 'admin')
        ? 'bg-purple-50 text-purple-700 border-purple-200'
        : 'bg-sky-50 text-sky-700 border-sky-200';
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Quản lý người dùng</title>
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

    <!-- GRID: sidebar + main -->
    <div class="h-screen w-full md:grid md:grid-cols-[260px_1fr] overflow-hidden">

        <!-- Sidebar -->
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
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100">
                    <i class="fa-solid fa-receipt w-5 text-center"></i><span>Đơn hàng</span>
                </a>
                <a href="<?= htmlspecialchars(url_to('admin/products.php')) ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100">
                    <i class="fa-solid fa-box-open w-5 text-center"></i><span>Sản phẩm</span>
                </a>
                <a href="<?= htmlspecialchars(url_to('admin/users.php')) ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-yellow-500 text-white">
                    <i class="fa-solid fa-users w-5 text-center"></i><span>Người dùng</span>
                </a>
                <hr class="my-3">
                <a href="<?= htmlspecialchars(url_to('logout.php')) ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100 text-red-600">
                    <i class="fa-solid fa-right-from-bracket w-5 text-center"></i><span>Đăng xuất</span>
                </a>
            </nav>
        </aside>

        <!-- Main -->
        <div class="flex flex-col h-screen overflow-hidden">
            <!-- Topbar -->
            <header class="bg-white border-b sticky top-0 z-20">
                <div class="px-4 md:px-6 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <button
                            class="md:hidden inline-flex items-center justify-center h-10 w-10 rounded-xl border hover:bg-gray-50"
                            onclick="document.getElementById('mobileSidebar').classList.toggle('hidden')">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <div>
                            <div class="font-bold text-lg flex items-center gap-2">
                                <i class="fa-solid fa-users text-yellow-600"></i> Người dùng
                            </div>
                            <div class="text-xs text-gray-500">Xin chào,
                                <?= htmlspecialchars($_SESSION['auth']['name']) ?></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="<?= htmlspecialchars(url_to('logout.php')) ?>"
                            class="px-3 py-1.5 rounded-xl border hover:bg-gray-50">
                            <i class="fa-solid fa-right-from-bracket mr-1"></i> Đăng xuất
                        </a>
                    </div>
                </div>
            </header>

            <!-- Content -->
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
                    <div class="grid grid-cols-1 lg:grid-cols-6 gap-3 items-end">
                        <div class="lg:col-span-3">
                            <label class="text-sm text-gray-600 flex items-center gap-2">
                                <i class="fa-solid fa-magnifying-glass"></i> Tìm theo tên/email
                            </label>
                            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
                                placeholder="Nhập tên hoặc email…"
                                class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 outline-none">
                        </div>
                        <div class="lg:col-span-2">
                            <label class="text-sm text-gray-600 flex items-center gap-2">
                                <i class="fa-regular fa-rectangle-list"></i> Quyền
                            </label>
                            <select name="role" class="border rounded-lg px-3 py-2 w-full">
                                <option value="all" <?= $role === 'all' ? 'selected' : '' ?>>(tất cả)</option>
                                <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>user</option>
                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>admin</option>
                            </select>
                        </div>
                        <div class="lg:col-span-1">
                            <button class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg">
                                <i class="fa-solid fa-filter mr-1"></i> Lọc
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Bảng người dùng -->
                <section class="bg-white border rounded-2xl p-4">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <h2 class="text-lg font-semibold flex items-center gap-2">
                            <i class="fa-solid fa-address-book text-yellow-600"></i> Danh sách người dùng (tối đa 50)
                        </h2>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left border-b">#</th>
                                    <th class="px-3 py-2 text-left border-b">Thông tin</th>
                                    <th class="px-3 py-2 text-left border-b">Email</th>
                                    <th class="px-3 py-2 text-left border-b">Quyền</th>
                                    <th class="px-3 py-2 text-right border-b">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$users): ?>
                                    <tr>
                                        <td colspan="5" class="px-3 py-6 text-center text-gray-500">
                                            <i class="fa-regular fa-face-smile-wink mr-1"></i> Không có người dùng.
                                        </td>
                                    </tr>
                                <?php else:
                                    foreach ($users as $u): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 border-b font-medium">#<?= (int) $u['id'] ?></td>
                                            <td class="px-3 py-2 border-b">
                                                <div class="flex items-center gap-3">
                                                    <img src="<?= htmlspecialchars($u['avatar'] ?: url_to('assets/images/avata_user/default.png')) ?>"
                                                        class="w-9 h-9 rounded-full border object-cover" alt="">
                                                    <div><?= htmlspecialchars($u['name'] ?? '-') ?></div>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 border-b"><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                                            <td class="px-3 py-2 border-b">
                                                <span
                                                    class="px-2 py-0.5 rounded-full border text-xs <?= role_badge_classes($u['role']) ?>">
                                                    <?= htmlspecialchars($u['role']) ?>
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 border-b text-right">
                                                <?php if ($u['role'] === 'admin'): ?>
                                                    <form method="post" class="inline">
                                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                                        <input type="hidden" name="role" value="user">
                                                        <button class="px-3 py-1 rounded border hover:bg-gray-100"
                                                            onclick="return confirm('Hạ quyền user #<?= (int) $u['id'] ?> về USER?')">
                                                            <i class="fa-solid fa-user-minus mr-1"></i> Về user
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="post" class="inline">
                                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                                        <input type="hidden" name="role" value="admin">
                                                        <button
                                                            class="px-3 py-1 rounded bg-purple-600 text-white hover:bg-purple-700"
                                                            onclick="return confirm('Nâng quyền user #<?= (int) $u['id'] ?> thành ADMIN?')">
                                                            <i class="fa-solid fa-user-shield mr-1"></i> Thành admin
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

                <footer class="text-xs text-gray-500 py-4">
                    © <?= date('Y') ?> Admin • Users
                </footer>
            </main>
        </div>
    </div>

    <!-- Drawer mobile -->
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
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100">
                    <i class="fa-solid fa-receipt w-5 text-center"></i><span>Đơn hàng</span>
                </a>
                <a href="<?= htmlspecialchars(url_to('admin/products.php')) ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100">
                    <i class="fa-solid fa-box-open w-5 text-center"></i><span>Sản phẩm</span>
                </a>
                <a href="<?= htmlspecialchars(url_to('admin/users.php')) ?>"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-yellow-500 text-white">
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