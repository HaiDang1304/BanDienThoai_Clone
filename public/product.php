<?php
// public/product.php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/database.php';

/* ========= Helpers ========= */
$BASE_URL = '/BanDienThoai_Clone/public';
function url_to(string $path): string {
  global $BASE_URL; return rtrim($BASE_URL, '/') . '/' . ltrim($path, '/');
}
function vnd($n){ return number_format((int)$n, 0, ',', '.') . 'đ'; }

/* ========= DB connect ========= */
try { $conn = db(); } 
catch (Exception $e) {
  http_response_code(500);
  echo "Lỗi kết nối CSDL: " . htmlspecialchars($e->getMessage());
  exit;
}

/* ========= Input ========= */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: ' . url_to('index.php')); exit; }

/* ========= Product ========= */
$sql = "SELECT id,name,variant,screen,size_inch,price,price_old,gift_value,rating,
               sold_k,installment,badge,image_url
        FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) { http_response_code(404); echo "Không tìm thấy sản phẩm"; exit; }

/* ========= Images ========= */
$images = [];
$st = $conn->prepare("SELECT image_url FROM product_images WHERE product_id=? ORDER BY sort_order, id");
$st->bind_param('i', $id);
$st->execute();
$rs = $st->get_result();
while ($row = $rs->fetch_assoc()) $images[] = (string)$row['image_url'];
$st->close();
if (empty($images) && !empty($product['image_url'])) $images[] = (string)$product['image_url'];

/* Chuẩn hoá ảnh (bỏ prefix /public nếu có) */
$stripPublic = fn($u) => preg_replace('#^/public#', '', (string)$u);
$images = array_map($stripPublic, $images);
$product['image_url'] = $stripPublic($product['image_url']);

/* ========= Description & Specs ========= */
$desc = '';
$st = $conn->prepare("SELECT content FROM product_descriptions WHERE product_id=?");
$st->bind_param('i', $id);
$st->execute();
if ($r = $st->get_result()->fetch_assoc()) $desc = (string)$r['content'];
$st->close();

$specsBySection = [];
$st = $conn->prepare("SELECT section, label, value
                      FROM product_specs
                      WHERE product_id=? ORDER BY section, sort_order, id");
$st->bind_param('i', $id);
$st->execute();
$res = $st->get_result();
while ($row = $res->fetch_assoc()) {
  $sec = (string)$row['section'];
  $specsBySection[$sec][] = ['label'=>(string)$row['label'], 'value'=>(string)$row['value']];
}
$st->close();

/* ========= Related ========= */
$related = [];
$st = $conn->prepare("SELECT id,name,variant,price,image_url
                      FROM products WHERE id<>? ORDER BY RAND() LIMIT 8");
$st->bind_param('i', $id);
$st->execute();
$res = $st->get_result();
while ($r = $res->fetch_assoc()) {
  $r['image_url'] = $stripPublic($r['image_url']);
  $related[] = $r;
}
$st->close();

/* ========= CSRF ========= */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($product['name']) . ' ' . htmlspecialchars($product['variant']) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <!-- Swiper -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

  <style>
    .swiper .swiper-button-next,.swiper .swiper-button-prev{color:#374151}
    .prose p{margin:.5rem 0}
  </style>
</head>
<body class="bg-gray-50 text-gray-800">
  <?php
    // nhiều partial đang dùng $baseUrl
    $baseUrl = $BASE_URL;
    include __DIR__ . '/../app/views/partials/header.php';
  ?>

  <main class="max-w-6xl mx-auto px-4 md:px-5 lg:px-6 py-5">
    <!-- Breadcrumb -->
    <nav class="text-[13px] text-gray-500 mb-3 md:mb-4">
      <a href="<?= url_to('index.php') ?>" class="hover:text-red-600"><i class="fa-solid fa-house"></i> Trang chủ</a>
      <span class="mx-2">/</span>
      <span class="text-gray-700">Chi tiết sản phẩm</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-6">
      <!-- LEFT: Gallery + brief -->
      <section class="lg:col-span-7">
        <!-- Main -->
        <div class="swiper mySwiperMain rounded-xl bg-white border overflow-hidden max-w-[634px] mx-auto">
          <div class="swiper-wrapper">
            <?php foreach ($images as $url): ?>
              <div class="swiper-slide flex items-center justify-center bg-white aspect-[4/5]">
                <img src="<?= htmlspecialchars(url_to($url)) ?>" alt="Hình sản phẩm"
                     class="object-contain w-auto h-auto max-w-[65%] max-h-[65%]" />
              </div>
            <?php endforeach; ?>
          </div>
          <div class="swiper-button-next"></div>
          <div class="swiper-button-prev"></div>
          <div class="swiper-pagination !bottom-2"></div>
        </div>

        <!-- Thumbs -->
        <?php if (count($images) > 1): ?>
          <div class="swiper mySwiperThumb mt-2.5">
            <div class="swiper-wrapper">
              <?php foreach ($images as $url): ?>
                <div class="swiper-slide rounded-lg border bg-white overflow-hidden aspect-square">
                  <img src="<?= htmlspecialchars(url_to($url)) ?>" class="object-cover w-full h-full" alt="Thumb">
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Brief info -->
        <div class="mt-4 rounded-xl border bg-white p-4">
          <h1 class="text-xl md:text-2xl font-semibold leading-snug">
            <?= htmlspecialchars($product['name']) ?>
            <span class="text-gray-500 font-normal"><?= htmlspecialchars($product['variant']) ?></span>
          </h1>

          <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-sm text-gray-600">
            <span><i class="fa-solid fa-star text-yellow-500"></i> <?= number_format((float)$product['rating'], 1) ?></span>
            <span class="text-gray-300">•</span>
            <span><i class="fa-solid fa-fire text-red-500"></i> Đã bán <?= (int)$product['sold_k'] ?>k</span>
            <?php if (!empty($product['badge'])): ?>
              <span class="px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 text-[12px]"><?= htmlspecialchars($product['badge']) ?></span>
            <?php endif; ?>
          </div>

          <div class="mt-3 grid grid-cols-2 gap-2 text-[13px]">
            <div class="p-3 rounded-lg bg-gray-50">
              <div class="text-gray-500">Màn hình</div>
              <div class="font-medium"><?= htmlspecialchars($product['screen']) ?>, <?= htmlspecialchars($product['size_inch']) ?>"</div>
            </div>
            <div class="p-3 rounded-lg bg-gray-50">
              <div class="text-gray-500">Trả góp</div>
              <div class="font-medium"><?= ((int)$product['installment'] === 1) ? 'Hỗ trợ trả góp' : 'Không hỗ trợ' ?></div>
            </div>
          </div>
        </div>

        <!-- Tabs -->
        <div class="mt-4">
          <div class="inline-flex rounded-full bg-gray-100 p-1 text-[13px]">
            <button type="button" class="tab-btn px-4 py-2 rounded-full bg-white shadow font-medium" data-tab="specs">
              <i class="fa-solid fa-list"></i> Thông số
            </button>
            <button type="button" class="tab-btn px-4 py-2 rounded-full" data-tab="desc">
              <i class="fa-regular fa-file-lines"></i> Mô tả
            </button>
          </div>

          <!-- Specs -->
          <div id="tab-specs" class="tab-panel mt-3">
            <?php if (!empty($specsBySection)): ?>
              <?php foreach ($specsBySection as $section => $rows): ?>
                <details class="group border rounded-xl bg-white mb-3" open>
                  <summary class="cursor-pointer flex items-center justify-between px-4 py-3">
                    <span class="font-semibold text-[15px]"><?= htmlspecialchars($section) ?></span>
                    <i class="fa-solid fa-chevron-down text-gray-500 group-open:rotate-180 transition"></i>
                  </summary>
                  <div class="px-4 pb-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6">
                      <?php foreach ($rows as $r): ?>
                        <div class="flex items-start gap-3 py-2 border-b last:border-b-0">
                          <div class="w-40 shrink-0 text-gray-500 text-[13px]"><?= htmlspecialchars($r['label']) ?></div>
                          <div class="font-medium text-[14px] leading-snug"><?= htmlspecialchars($r['value']) ?></div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </details>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="text-gray-500 text-[14px]">Chưa có thông số cho sản phẩm này.</div>
            <?php endif; ?>
          </div>

          <!-- Description -->
          <div id="tab-desc" class="tab-panel mt-3 hidden">
            <div class="prose max-w-none prose-img:rounded-xl prose-p:my-2">
              <?= $desc ? $desc : '<p class="text-gray-500 text-[14px]">Chưa có mô tả cho sản phẩm này.</p>' ?>
            </div>
          </div>
        </div>
      </section>

      <!-- RIGHT: Purchase box -->
      <aside class="lg:col-span-5">
        <?php
          $price = (int)$product['price'];
          $priceOld = (int)($product['price_old'] ?? 0);
          $hasOld = $priceOld > 0 && $priceOld > $price;
          $saving = $hasOld ? ($priceOld - $price) : 0;
          $discount = $hasOld ? round(100 - ($price * 100 / $priceOld)) : 0;
        ?>
        <div class="lg:sticky lg:top-20 space-y-3">
          <div class="overflow-hidden rounded-2xl border bg-white shadow-sm">
            <!-- Price header -->
            <div class="p-4 md:p-5 bg-gradient-to-r from-yellow-50 to-white border-b">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <div class="text-xs uppercase tracking-wide text-gray-500">Giá bán</div>
                  <div class="flex items-center gap-2">
                    <div class="text-3xl md:text-4xl font-extrabold text-red-600"><?= vnd($price) ?></div>
                    <?php if ($hasOld): ?>
                      <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
                        -<?= (int)$discount ?>%
                      </span>
                    <?php endif; ?>
                  </div>
                  <div class="mt-1 flex items-center gap-3 text-sm">
                    <?php if ($hasOld): ?>
                      <span class="text-gray-400 line-through"><?= vnd($priceOld) ?></span>
                      <span class="text-green-600 font-medium"><i class="fa-solid fa-piggy-bank"></i> Tiết kiệm <?= vnd($saving) ?></span>
                    <?php endif; ?>
                    <?php if ((int)$product['gift_value'] > 0): ?>
                      <span class="text-green-700"><i class="fa-solid fa-gift"></i> <?= vnd($product['gift_value']) ?> quà tặng</span>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="w-20 h-20 rounded-xl overflow-hidden bg-white/60 ring-1 ring-gray-100 hidden sm:block">
                  <img src="<?= htmlspecialchars(url_to($product['image_url'])) ?>" class="w-full h-full object-contain" alt="Thumb">
                </div>
              </div>

              <?php if ((int)$product['installment'] === 1): ?>
                <div class="mt-3 inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100 text-amber-800 text-xs">
                  <i class="fa-regular fa-credit-card"></i>
                  <span>Hỗ trợ trả góp • Thủ tục nhanh</span>
                </div>
              <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="p-4 md:p-5">
              <!-- Qty -->
              <div class="mb-3">
                <div class="text-sm text-gray-600 mb-1">Số lượng</div>
                <div class="inline-flex items-center rounded-full border bg-gray-50">
                  <button type="button" class="qty-down px-3 py-2"><i class="fa-solid fa-minus"></i></button>
                  <input id="qty-input" type="number" name="qty" min="1" value="1"
                         class="w-16 text-center bg-transparent border-x py-2 outline-none" />
                  <button type="button" class="qty-up px-3 py-2"><i class="fa-solid fa-plus"></i></button>
                </div>
              </div>

              <div class="grid grid-cols-1 gap-2">
                <!-- Mua ngay -->
                <a href="<?= url_to('checkout.php?product_id='.(int)$product['id'].'&qty=1') ?>"
                   id="buy-now-link"
                   class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-red-600 hover:bg-red-700 text-white font-semibold transition">
                  <i class="fa-solid fa-bolt"></i> Mua ngay
                </a>

                <!-- Thêm vào giỏ -->
                <form action="<?= url_to('cart_action.php') ?>" method="post" class="w-full">
                  <input type="hidden" name="action" value="add">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                  <!-- Nếu có biến thể:
                  <input type="hidden" name="variant" value="<?= htmlspecialchars($product['variant'] ?? '') ?>">
                  -->
                  <input type="hidden" name="qty" id="qty-hidden" value="1">
                  <button class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-yellow-500 hover:bg-yellow-600 text-white font-semibold transition">
                    <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ
                  </button>
                </form>
              </div>

              <!-- Benefits -->
              <ul class="mt-4 grid grid-cols-1 gap-2 text-sm text-gray-700">
                <li class="flex items-start gap-2"><i class="fa-solid fa-shield-halved mt-0.5"></i><span>Hàng chính hãng, đổi trả 7 ngày</span></li>
                <li class="flex items-start gap-2"><i class="fa-solid fa-truck-fast mt-0.5"></i><span>Giao nhanh nội thành</span></li>
                <li class="flex items-start gap-2"><i class="fa-regular fa-credit-card mt-0.5"></i><span>Hỗ trợ trả góp qua thẻ</span></li>
              </ul>
            </div>
          </div>
        </div>
      </aside>
    </div>

    <!-- Related -->
    <?php if (!empty($related)): ?>
      <section class="mt-8 md:mt-10">
        <h2 class="text-[18px] md:text-xl font-bold mb-3"><i class="fa-solid fa-layer-group"></i> Sản phẩm liên quan</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
          <?php foreach ($related as $item): ?>
            <a href="<?= url_to('product.php?id='.(int)$item['id']) ?>"
               class="group rounded-xl border bg-white hover:shadow-md transition p-3 flex flex-col">
              <div class="aspect-square overflow-hidden rounded-lg bg-gray-50">
                <img src="<?= htmlspecialchars(url_to($item['image_url'])) ?>" class="w-full h-full object-contain" alt="">
              </div>
              <div class="mt-2 text-[14px] line-clamp-2 group-hover:text-red-600">
                <?= htmlspecialchars($item['name']) ?> <?= htmlspecialchars($item['variant']) ?>
              </div>
              <div class="mt-1 font-semibold text-red-600 text-[15px]"><?= vnd($item['price']) ?></div>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  </main>

  <?php
    if (file_exists(__DIR__ . '/../app/views/partials/footer.php')) {
      include __DIR__ . '/../app/views/partials/footer.php';
    }
  ?>

  <script>
    // Swiper
    const swiperThumb = new Swiper('.mySwiperThumb', {
      spaceBetween: 8, slidesPerView: 5, freeMode: true, watchSlidesProgress: true,
      breakpoints: { 640:{slidesPerView:6}, 1024:{slidesPerView:7} }
    });
    const swiperMain = new Swiper('.mySwiperMain', {
      spaceBetween: 10, loop: true,
      navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
      pagination: { el: '.swiper-pagination', clickable: true },
      thumbs: { swiper: swiperThumb }
    });

    // Tabs
    const tabs = document.querySelectorAll('.tab-btn');
    const panels = { specs: document.getElementById('tab-specs'), desc: document.getElementById('tab-desc') };
    tabs.forEach(btn => btn.addEventListener('click', () => {
      tabs.forEach(b => b.classList.remove('bg-white','shadow','font-medium'));
      btn.classList.add('bg-white','shadow','font-medium');
      Object.values(panels).forEach(p => p.classList.add('hidden'));
      panels[btn.dataset.tab].classList.remove('hidden');
    }));

    // Qty stepper sync
    (function () {
      const input = document.getElementById('qty-input');
      const hidden = document.getElementById('qty-hidden');
      const buyNow = document.getElementById('buy-now-link');
      const down = document.querySelector('.qty-down');
      const up = document.querySelector('.qty-up');

      function clamp(v){ v = parseInt(v || 1, 10); return v < 1 ? 1 : v; }
      function sync(){
        const v = clamp(input.value);
        input.value = v; hidden.value = v;
        const url = new URL(buyNow.href, window.location.origin);
        url.searchParams.set('qty', v);
        buyNow.href = url.toString();
      }

      down.addEventListener('click', () => { input.value = clamp(input.value) - 1; sync(); });
      up.addEventListener('click',   () => { input.value = clamp(input.value) + 1; sync(); });
      input.addEventListener('input', sync);
      sync();
    })();
  </script>
</body>
</html>
