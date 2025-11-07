<?php
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$projectFolder = '/BanDienThoai_Clone';
$baseUrl = $protocol . $host . $projectFolder;

$asset = function (string $relPath) use ($baseUrl): string {
    return rtrim($baseUrl, '/') . '/' . ltrim($relPath, '/');
};

if (!isset($promoItems) || !is_array($promoItems) || empty($promoItems)) {
    $promoItems = [
        ['title' => 'vivo X300 Series', 'image_url' => '/public/assets/images/banner_uudai/banner1.png', 'link_url' => '#', 'cta_text' => 'ĐẶT NGAY'],
        ['title' => 'HONOR X7d', 'image_url' => '/public/assets/images/banner_uudai/banner2.png', 'link_url' => '#', 'cta_text' => 'MUA NGAY'],
        ['title' => 'Redmi Note 14', 'image_url' => '/public/assets/images/banner_uudai/banner3.png', 'link_url' => '#', 'cta_text' => 'MUA NGAY'],
        ['title' => 'Samsung Show', 'image_url' => '/public/assets/images/banner_uudai/banner4.png', 'link_url' => '#', 'cta_text' => 'MUA NGAY'],
    ];
}

// Chỉ lấy tối đa 4 item
$cards = array_slice($promoItems, 0, 4);
?>
<!-- KHÔNG cần swiper CSS nữa, có thể xóa dòng import nếu trước đó có -->
<div
    class="rounded-2xl border border-orange-400 bg-gradient-to-b from-[#ff5f2a] to-[#ff7a2d] p-3 md:p-4 text-white mt-5">
    <div class="flex items-center gap-2 mb-3">
        <h2 class="text-xl font-bold">Gian hàng ưu đãi</h2>
    </div>

    <!-- Lưới 4 ảnh, responsive -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4">
        <?php foreach ($cards as $b):
            $title = htmlspecialchars($b['title'] ?? '');
            $relImg = $b['image_url'] ?? '/public/assets/images/placeholder-640x360.jpg';
            $img = htmlspecialchars($asset($relImg));
            $link = htmlspecialchars($b['link_url'] ?? '#');
            $cta = htmlspecialchars($b['cta_text'] ?? 'MUA NGAY');
            ?>
            <a href="<?= $link ?>" class="block rounded-xl overflow-hidden bg-white text-black hover:shadow-lg transition">
                <!-- CHIỀU CAO CỐ ĐỊNH CHO CHA -->
                <div class="relative h-full md:h-[360px]">
                    <img src="<?= $img ?>" alt="<?= $title ?>" class="w-full h-full object-cover" loading="lazy">
                    <div class="absolute bottom-3 left-0 right-0 flex justify-center">
                        <span class="px-4 py-2 rounded-full bg-[#ff7a2d] text-white font-semibold shadow"><?= $cta ?></span>
                    </div>
                </div>
                <div class="p-3">
                    <h3 class="text-sm font-semibold line-clamp-2 min-h-[40px]"><?= $title ?></h3>
                </div>
            </a>

        <?php endforeach; ?>
    </div>
</div>