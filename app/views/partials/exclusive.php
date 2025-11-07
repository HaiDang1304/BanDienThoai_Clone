<?php
$items = $items ?? [];

if (!function_exists('vnd')) {
  function vnd($n)
  {
    return number_format((int) $n, 0, ',', '.') . 'đ';
  }
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$projectFolder = '/BanDienThoai_Clone';
$baseUrl = $protocol . $host . $projectFolder;

function img_src(string $baseUrl, ?string $dbPath): string
{
  $rel = trim($dbPath ?? '');
  if ($rel === '')
    $rel = '/public/assets/images/placeholder-240.png';
  return $baseUrl . $rel;
}
?>
<link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />

<div class="rounded-2xl border border-orange-300 bg-gradient-to-br from-[#ffe9cc] to-[#fff3d6] p-3 md:p-4 mt-5">
  <div class="flex items-center gap-2 mb-3 md:mb-4">
    <img src="<?= htmlspecialchars($baseUrl . '/public/assets/images/11-11.png') ?>" class="w-7 h-7" alt="">
    <h2 class="text-xl font-bold">Sản Phẩm Đặc Quyền</h2>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
    <!-- Banner trái -->
    <div class="lg:col-span-4">
      <a href="#" class="block w-full h-full rounded-xl overflow-hidden">
        <!-- thêm class exclusive-banner, bỏ h-[220px] md:h-[320px] lg:h-[300px] -->
        <div class="exclusive-banner w-full">
          <img src="<?= htmlspecialchars($baseUrl . '/public/assets/images/banner_qc/exclusive.png') ?>"
            alt="Ưu đãi đặc quyền" class="w-full h-[410px] object-cover object-center" loading="lazy">
        </div>
      </a>
    </div>

    <!-- Slider phải -->
    <div class="relative lg:col-span-8">
      <!-- Nút điều hướng -->
      <button
        class="exclusive-prev absolute -left-2 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-white shadow hover:scale-105 grid place-items-center">
        ‹
      </button>
      <button
        class="exclusive-next absolute -right-2 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-white shadow hover:scale-105 grid place-items-center">
        ›
      </button>

      <div class="swiper exclusive-swiper">
        <div class="swiper-wrapper">
          <?php foreach ($items as $p): ?>
            <?php
            $imageSrc = img_src($baseUrl, $p['image_url'] ?? null);
            ?>
            <div class="swiper-slide">
              <a href="/product.php?id=<?= (int) $p['id'] ?>"
                class="block h-full rounded-xl border border-gray-200 bg-white p-3 hover:shadow-lg transition flex flex-col">
                <!-- tag trả góp -->
                <?php if (!empty($p['installment'])): ?>
                  <div class="text-[11px] bg-orange-100 text-orange-700 px-2 py-0.5 rounded w-max mb-2">
                    Trả góp 0% trả trước 0đ
                  </div>
                <?php endif; ?>

                <!-- ẢNH SẢN PHẨM -->
                <div class="w-full h-40 md:h-44 bg-gray-50 rounded-lg overflow-hidden flex items-center justify-center">
                  <img src="<?= htmlspecialchars($imageSrc) ?>" alt="<?= htmlspecialchars($p['name'] ?? '') ?>"
                    class="max-h-full max-w-full object-contain">
                </div>

                <!-- NỘI DUNG -->
                <div class="mt-2 flex-1 flex flex-col">
                  <h3 class="text-sm font-semibold line-clamp-2 min-h-[44px]">
                    <?= htmlspecialchars($p['name'] ?? '') ?>
                    <?php if (!empty($p['variant'])): ?>
                      <span class="font-normal"> <?= htmlspecialchars($p['variant']) ?></span>
                    <?php endif; ?>
                  </h3>

                  <div class="mt-1 text-[11px] text-gray-500 flex items-center gap-1 min-h-6">
                    <?php if (!empty($p['screen'])): ?>
                      <span class="px-1.5 py-0.5 bg-gray-100 rounded"><?= htmlspecialchars($p['screen']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($p['size_inch'])): ?>
                      <span class="px-1.5 py-0.5 bg-gray-100 rounded">
                        <?= rtrim(rtrim(number_format((float) $p['size_inch'], 1, '.', ''), '0'), '.') ?>"</span>
                    <?php endif; ?>
                  </div>

                  <div class="mt-2 min-h-[56px]">
                    <div class="text-red-600 font-bold text-lg leading-tight"><?= vnd($p['price'] ?? 0) ?></div>
                    <?php if (!empty($p['price_old']) && (int) $p['price_old'] > (int) ($p['price'] ?? 0)): ?>
                      <?php $discount = 100 - (($p['price'] ?? 0) / $p['price_old'] * 100); ?>
                      <div class="text-[12px] text-gray-400 leading-tight">
                        <span class="line-through mr-1"><?= vnd($p['price_old']) ?></span>
                        <span>Online giá rẻ quá
                          <span class="ml-1 text-[#e11d48]">-<?= number_format($discount, 0) ?>%</span>
                        </span>
                      </div>
                    <?php elseif ((int) ($p['gift_value'] ?? 0) > 0): ?>
                      <div class="text-[12px] text-gray-600 mt-0.5 leading-tight">Quà <?= vnd($p['gift_value']) ?></div>
                    <?php else: ?>
                      <div class="text-[12px] opacity-0 select-none">placeholder</div>
                    <?php endif; ?>
                  </div>

                  <!-- FOOTER -->
                  <div class="mt-auto pt-2">
                    <div class="h-7 flex items-center justify-between">
                      <span class="text-[10px] bg-black text-amber-300 px-2 py-1 rounded">
                        <?= htmlspecialchars(($p['badge'] ?? '') ?: 'ĐẶC QUYỀN') ?>
                      </span>
                      <span class="text-[12px] text-gray-500">
                        ★ <?= number_format((float) ($p['rating'] ?? 5), 1) ?> - Đã bán <?= (int) ($p['sold_k'] ?? 0) ?>k
                      </span>
                    </div>
                  </div>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script>
  new Swiper('.exclusive-swiper', {
    spaceBetween: 12,
    slidesPerView: 1.2,
    navigation: {
      nextEl: '.exclusive-next',
      prevEl: '.exclusive-prev',
    },
    breakpoints: {
      640: { slidesPerView: 2.2, spaceBetween: 12 },
      768: { slidesPerView: 3.2, spaceBetween: 12 },
      1024: { slidesPerView: 3.2, spaceBetween: 12 },
      1280: { slidesPerView: 3.2, spaceBetween: 12 },
    }
  });
</script>