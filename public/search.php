<?php
// public/search.php
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
function vnd($n)
{
    return number_format((int) $n, 0, ',', '.') . 'đ';
}

/* ===== Input ===== */
$q = trim($_GET['q'] ?? '');
if ($q === '') {
    header('Location: ' . url_to('index.php'));
    exit;
}

/* ===== DB query ===== */
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = db();

    $sql = "SELECT id, name, price, price_old, gift_value, rating, sold_k, image_url, size_inch, screen
          FROM products
          WHERE name LIKE ?
          ORDER BY name ASC";
    $like = '%' . $q . '%';
    $stm = $conn->prepare($sql);
    $stm->bind_param('s', $like);
    $stm->execute();
    $products = $stm->get_result()->fetch_all(MYSQLI_ASSOC);
    $stm->close();

} catch (Throwable $e) {
    http_response_code(500);
    echo "Lỗi khi tìm kiếm: " . htmlspecialchars($e->getMessage());
    exit;
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Kết quả tìm kiếm: <?= htmlspecialchars($q) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="bg-gray-50 text-gray-800">
    <?php
    if (file_exists(__DIR__ . '/partials/header.php')) {
        include __DIR__ . '/partials/header.php';
    } elseif (file_exists(__DIR__ . '/../app/views/partials/header.php')) {
        include __DIR__ . '/../app/views/partials/header.php';
    }
    ?>

    <main class="max-w-7xl mx-auto px-4 py-6">
        <h1 class="text-2xl font-bold mb-5"> Kết quả tìm kiếm cho “<?= htmlspecialchars($q) ?>”</h1>

        <?php if (empty($products)): ?>
            <div class="bg-white border rounded-2xl shadow-sm p-8 text-center">
                <div class="text-4xl mb-3">😕</div>
                <div class="text-lg font-semibold">Không tìm thấy sản phẩm phù hợp.</div>
                <a href="<?= url_to('index.php') ?>"
                    class="mt-4 inline-block px-5 py-2 bg-yellow-400 rounded-xl font-semibold hover:bg-yellow-500">Quay lại
                    trang chủ</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <?php foreach ($products as $p):
                    $price = (int) ($p['price'] ?? 0);
                    $priceOld = (int) ($p['price_old'] ?? 0);
                    $discount = $priceOld > 0 ? round(100 - ($price / $priceOld) * 100) : 0;
                    $rating = (float) ($p['rating'] ?? 0);
                    $soldK = (int) ($p['sold_k'] ?? 0);
                    $gift = (int) ($p['gift_value'] ?? 0);
                    ?>
                    <a href="<?= url_to('product.php?id=' . (int) $p['id']) ?>"
                        class="group bg-white rounded-2xl border hover:shadow-xl transition overflow-hidden flex flex-col">
                        <div class="relative w-full pb-[100%]">
                            <img src="<?= htmlspecialchars('/BanDienThoai_Clone' . $p['image_url']) ?>"
                                alt="<?= htmlspecialchars($p['name']) ?>"
                                class="absolute inset-0 w-full h-full object-cover rounded-t-xl">

                            <?php if ($discount > 0): ?>
                                <div
                                    class="absolute top-2 left-2 bg-red-600 text-white text-xs font-semibold px-2 py-0.5 rounded-md">
                                    -<?= $discount ?>%
                                </div>
                            <?php endif; ?>
                            <div
                                class="absolute top-2 right-2 bg-yellow-100 text-[11px] text-gray-800 px-2 py-0.5 rounded-md font-medium">
                                Trả góp 0% trước 0đ
                            </div>
                        </div>

                        <div class="p-3 flex-1 flex flex-col">
                            <h3 class="text-sm font-semibold leading-tight line-clamp-2 group-hover:text-red-600 mb-1">
                                <?= htmlspecialchars($p['name']) ?>
                            </h3>
                            <div class="flex items-center flex-wrap gap-1 text-[11px] text-gray-600 mb-1">
                                <?php if (!empty($p['screen'])): ?>
                                    <span class="border px-1.5 rounded"><?= htmlspecialchars($p['screen']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($p['size_inch'])): ?>
                                    <span class="border px-1.5 rounded"><?= htmlspecialchars($p['size_inch']) ?>"</span>
                                <?php endif; ?>
                            </div>

                            <div class="mt-auto">
                                <div class="text-red-600 font-bold text-lg"><?= vnd($price) ?></div>
                                <?php if ($priceOld > $price): ?>
                                    <div class="text-sm text-gray-400 line-through"><?= vnd($priceOld) ?></div>
                                <?php endif; ?>
                                <?php if ($gift > 0): ?>
                                    <div class="text-sm text-green-600 mt-0.5">Quà <?= vnd($gift) ?></div>
                                <?php endif; ?>
                                <div class="flex items-center justify-between text-xs text-gray-500 mt-1">
                                    <span><i class="fa-solid fa-star text-yellow-400"></i>
                                        <?= number_format($rating, 1) ?></span>
                                    <span>Đã bán <?= $soldK ?>k</span>
                                </div>
                                <div class="mt-2">
                                    <span class="inline-block bg-black text-white text-[11px] px-2 py-0.5 rounded">ĐẶC
                                        QUYỀN</span>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>

</html>