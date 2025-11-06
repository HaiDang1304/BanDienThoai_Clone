<?php
// app/views/partials/recommendations.php
$items = $items ?? [];
if (!function_exists('vnd')) {
    function vnd($n)
    {
        return number_format((int) $n, 0, ',', '.') . 'đ';
    }
}
?>
<div class="rounded-2xl bg-white border border-orange-200 p-4 shadow-sm">
    <div class="flex items-center gap-2">
        <img src="/public/assets/images/11-11.png" class="w-7 h-7" alt="">
        <h2 class="text-xl font-bold">Gợi ý cho bạn</h2>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mt-4">
        <?php foreach ($items as $p): ?>
            <a href="/product.php?id=<?= (int) $p['id'] ?>"
                class="relative group rounded-xl border border-gray-200 bg-white hover:shadow-lg transition p-3 flex flex-col">
                <?php if (!empty($p['installment'])): ?>
                    <div class="absolute left-2 top-2 z-10 text-[11px] bg-orange-100 text-orange-700 px-2 py-0.5 rounded">
                        Trả góp 0% trả trước 0đ
                    </div>
                <?php endif; ?>

                <!-- ẢNH: cố định chiều cao để các card đều nhau -->
                <?php
                $baseUrl = '/PHP/BanDienThoai_Clone'; // đường dẫn gốc dự án trên localhost
                $imagePath = !empty($p['image_url']) ? $p['image_url'] : '/public/assets/images/placeholder-240.png';
                $imageSrc = $baseUrl . $imagePath;
                ?>
                <div class="w-full h-44 md:h-52 bg-gray-50 rounded-lg overflow-hidden flex items-center justify-center">
                    <img src="<?= htmlspecialchars($imageSrc) ?>" alt="<?= htmlspecialchars($p['name']) ?>"
                        class="max-h-full max-w-full object-contain transition group-hover:scale-[1.02]">
                </div>


                <!-- chừa chỗ cho badge để không đẩy layout -->
                <div class="h-5"></div>

                <div class="mt-2 flex-1 flex flex-col">
                    <!-- Tên: khóa 2 dòng để đều chiều cao -->
                    <h3 class="text-sm font-semibold line-clamp-2 min-h-[44px]">
                        <?= htmlspecialchars($p['name']) ?>
                        <?php if (!empty($p['variant'])): ?>
                            <span class="font-normal"> <?= htmlspecialchars($p['variant']) ?></span>
                        <?php endif; ?>
                    </h3>

                    <!-- Thông số: giữ cao tối thiểu -->
                    <div class="mt-1 text-[11px] text-gray-500 flex items-center gap-1 min-h-6">
                        <?php if (!empty($p['screen'])): ?>
                            <span class="px-1.5 py-0.5 bg-gray-100 rounded"><?= htmlspecialchars($p['screen']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($p['size_inch'])): ?>
                            <span class="px-1.5 py-0.5 bg-gray-100 rounded">
                                <?= rtrim(rtrim(number_format((float) $p['size_inch'], 1, '.', ''), '0'), '.') ?>"</span>
                        <?php endif; ?>
                    </div>

                    <!-- Giá + quà/giảm giá: khóa chiều cao để card đều -->
                    <div class="mt-2 min-h-[56px]">
                        <div class="text-red-600 font-bold text-lg leading-tight"><?= vnd($p['price']) ?></div>
                        <?php if (!empty($p['price_old']) && (int) $p['price_old'] > (int) $p['price']): ?>
                            <?php $discount = 100 - ($p['price'] / $p['price_old'] * 100); ?>
                            <div class="text-[12px] text-gray-400 leading-tight">
                                <span class="line-through mr-1"><?= vnd($p['price_old']) ?></span>
                                <span>Online giá rẻ quá <span
                                        class="ml-1 text-[#e11d48]">-<?= number_format($discount, 0) ?>%</span></span>
                            </div>
                        <?php elseif ((int) $p['gift_value'] > 0): ?>
                            <div class="text-[12px] text-gray-600 mt-0.5 leading-tight">Quà <?= vnd($p['gift_value']) ?></div>
                        <?php else: ?>
                            <!-- giữ chỗ để chiều cao đồng đều -->
                            <div class="text-[12px] opacity-0 select-none">placeholder</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Badge nổi, nhưng có spacer ở trên để không nhảy layout -->
                <div class="mt-auto pt-2">
                    <div class="h-7 flex items-center justify-between">
                        <?php
                        $badgeClass = empty($p['badge'])
                            ? 'invisible'   // giữ chỗ để các card không bị lệch
                            : '';
                        ?>
                        <span class="<?= $badgeClass ?> text-[10px] bg-black text-amber-300 px-2 py-1 rounded">
                            <?= htmlspecialchars($p['badge'] ?: 'ĐẶC QUYỀN') ?>
                        </span>

                        <span class="text-[12px] text-gray-500">
                            ★ <?= number_format((float) $p['rating'], 1) ?> - Đã bán <?= (int) $p['sold_k'] ?>k
                        </span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>