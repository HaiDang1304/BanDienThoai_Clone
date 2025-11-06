<?php
include __DIR__ . "/../config/database.php";
date_default_timezone_set('Asia/Ho_Chi_Minh');

/* ---------------------------
   1) Lấy khung giờ + xác định trạng thái
----------------------------*/
$sql_slots = "SELECT id, slot_time, end_time FROM flashsale_slots ORDER BY slot_time ASC";
$slots_rs = $conn->query($sql_slots);

$now = new DateTime('now');
$slots = [];
$slotIds = [];

while ($row = $slots_rs->fetch_assoc()) {
    $slotId = (int) $row['id'];
    $slotIds[] = $slotId;

    // Gắn ngày hôm nay vào giờ bắt đầu/kết thúc để so sánh chính xác theo ngày
    $start = DateTime::createFromFormat('H:i:s', $row['slot_time']) ?: new DateTime('00:00:00');
    $end = DateTime::createFromFormat('H:i:s', $row['end_time']) ?: new DateTime('00:00:00');

    // ép về cùng ngày hiện tại
    $start->setDate((int) $now->format('Y'), (int) $now->format('m'), (int) $now->format('d'));
    $end->setDate((int) $now->format('Y'), (int) $now->format('m'), (int) $now->format('d'));

    // Nếu end < start (qua ngày) thì cộng 1 ngày cho end
    if ($end <= $start) {
        $end->modify('+1 day');
    }

    if ($now < $start)
        $status = 'upcoming';
    elseif ($now > $end)
        $status = 'ended';
    else
        $status = 'active';

    $slots[$slotId] = [
        'id' => $slotId,
        'slot_time' => $row['slot_time'],
        'end_time' => $row['end_time'],
        'start_dt' => $start, // DateTime
        'end_dt' => $end,   // DateTime
        'status' => $status,
        'products' => []
    ];
}

/* ---------------------------
   2) Lấy toàn bộ sản phẩm theo tất cả slot một lần (tránh N+1)
----------------------------*/
if (!empty($slotIds)) {
    $in = implode(',', array_map('intval', $slotIds));
    $sql_products = "SELECT slot_id, name, original_price, sale_price, discount, image
                     FROM products_flashsale
                     WHERE slot_id IN ($in)
                     ORDER BY slot_id ASC, discount DESC, sale_price ASC";
    $prod_rs = $conn->query($sql_products);

    while ($p = $prod_rs->fetch_assoc()) {
        $sid = (int) $p['slot_id'];
        if (!isset($slots[$sid]))
            continue;
        $slots[$sid]['products'][] = [
            'name' => $p['name'],
            'original_price' => (int) $p['original_price'],
            'sale_price' => (int) $p['sale_price'],
            'discount' => $p['discount'], // ví dụ "15%" hoặc "₫200K"
            'image' => $p['image']
        ];
    }
}

/* ---------------------------
   3) Helper hiển thị
----------------------------*/
function vnd($n)
{
    if ($n === null || $n === '')
        return '';
    return number_format((int) $n, 0, ',', '.') . '₫';
}

function discountBadge($original, $sale, $discountStr)
{
    // Ưu tiên discount đã có sẵn; nếu trống thì tự tính %
    if (!empty($discountStr))
        return $discountStr;
    if ($original > 0 && $sale > 0 && $sale < $original) {
        $pct = round(100 - ($sale / $original) * 100);
        return '-' . $pct . '%';
    }
    return '';
}

function displayProductCard($p, $slotStatus = 'active')
{
    $baseUrl = 'http://localhost/PHP/BanDienThoai_Clone/public/';
    $badge = discountBadge($p['original_price'], $p['sale_price'], $p['discount'] ?? '');

    echo '<div class="group bg-white rounded-2xl shadow-sm border hover:shadow-lg transition-all duration-200 p-4 flex flex-col">';
    echo '<div class="relative aspect-[1/1] mb-3 grid place-items-center">';
    if ($badge) {
        echo '<span class="absolute left-2 top-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow">' . $badge . '</span>';
    }
    echo '<img loading="lazy" src="' . htmlspecialchars($baseUrl . $p['image']) . '" alt="' . htmlspecialchars($p['name']) . '" class="max-h-48 object-contain transition-transform duration-200 group-hover:scale-105">';
    echo '</div>';

    echo '<h3 class="text-sm font-semibold line-clamp-2 min-h-[40px]">' . htmlspecialchars($p['name']) . '</h3>';

    echo '<div class="mt-2">';
    echo '<div class="flex items-end gap-2">';
    echo '<span class="text-xl font-bold text-orange-600">' . vnd($p['sale_price']) . '</span>';
    if (!empty($p['original_price']) && $p['original_price'] > $p['sale_price']) {
        echo '<span class="text-gray-400 line-through text-sm">' . vnd($p['original_price']) . '</span>';
    }
    echo '</div>';
    if (!empty($p['original_price']) && $p['original_price'] > $p['sale_price']) {
        $save = (int) $p['original_price'] - (int) $p['sale_price'];
        echo '<div class="text-xs text-green-600 font-medium mt-1">Tiết kiệm ' . vnd($save) . '</div>';
    }
    echo '</div>';

    if ($slotStatus === 'active') {
        echo '<button class="mt-4 w-full bg-gradient-to-r from-orange-500 to-red-500 text-white py-2.5 rounded-xl font-semibold hover:opacity-95 active:scale-[.99] transition-all">Mua ngay</button>';
    } elseif ($slotStatus === 'upcoming') {
        echo '<button class="mt-4 w-full bg-yellow-300 text-gray-700 py-2.5 rounded-xl font-semibold cursor-not-allowed opacity-90">Sắp mở bán</button>';
    } else { // ended
        echo '<button class="mt-4 w-full bg-gray-300 text-gray-500 py-2.5 rounded-xl font-semibold cursor-not-allowed">Đã kết thúc</button>';
    }

    echo '</div>';
}

function chunkProducts($arr, $size = 5)
{
    return array_chunk($arr, $size);
}

/* ---------------------------
   4) Chuẩn bị dữ liệu cho JS: slots + timestamp
----------------------------*/
$slotsForJs = [];
$firstActiveId = null;
$firstUpcomingId = null;

foreach ($slots as $s) {
    $slotsForJs[] = [
        'id' => $s['id'],
        'status' => $s['status'],
        // timestamp (ms) cho JS
        'start_ts' => $s['start_dt']->getTimestamp() * 1000,
        'end_ts' => $s['end_dt']->getTimestamp() * 1000,
        'label' => substr($s['slot_time'], 0, 5)
    ];
    if ($s['status'] === 'active' && $firstActiveId === null)
        $firstActiveId = $s['id'];
    if ($s['status'] === 'upcoming' && $firstUpcomingId === null)
        $firstUpcomingId = $s['id'];
}

// Slot mặc định hiển thị: Active đầu tiên, nếu không có thì Upcoming đầu tiên, nếu cũng không có thì slot đầu.
$defaultSlotId = $firstActiveId ?? $firstUpcomingId ?? (array_key_first($slots) ?: null);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Trang Chủ</title>
</head>

<body class="bg-gray-50">
    <?php include __DIR__ . '/partials/header.php'; ?>

    <main class="max-w-7xl mx-auto px-4 py-8">

        <!-- FLASH SALE Banner -->
        <section
            class="rounded-2xl mb-6 p-4 border bg-gradient-to-r from-yellow-100 via-amber-100 to-orange-100 border-yellow-300">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-3">
                    <div class="text-3xl">⚡</div>
                    <div>
                        <h2 class="text-xl font-extrabold tracking-tight">FLASH SALE | ONLINE ONLY</h2>
                        <p class="text-sm text-gray-700">Giảm đến 50% – Số lượng có hạn</p>
                    </div>
                </div>
                <p class="text-sm text-gray-700">Điện thoại • Apple • Laptop • Phụ kiện • Đồng hồ • PC/Màn hình</p>
            </div>
        </section>

        <!-- Thanh chọn khung giờ (sticky) -->
        <div
            class="sticky top-0 z-20 bg-white/80 backdrop-blur supports-[backdrop-filter]:bg-white/60 rounded-2xl p-3 border mb-4">
            <div class="flex flex-wrap gap-2" id="slot-buttons">
                <?php foreach ($slots as $s):
                    $status = $s['status'];
                    $baseBtn = 'time-btn px-4 py-2 rounded-full font-bold border transition focus:outline-none';
                    $cls = match ($status) {
                        'active' => $baseBtn . ' bg-white text-orange-600 border-orange-500 shadow',
                        'upcoming' => $baseBtn . ' bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200',
                        'ended' => $baseBtn . ' bg-gray-100 text-gray-400 border-gray-200 line-through',
                        default => $baseBtn . ' bg-gray-100 text-gray-700 border-gray-200',
                    };
                    ?>
                    <button class="<?= $cls ?>" data-slot="<?= $s['id'] ?>">
                        <?= htmlspecialchars(substr($s['slot_time'], 0, 5)) ?>
                        <?php if ($status === 'active'): ?>
                            <span class="ml-1 text-[10px] text-orange-600 align-middle">LIVE</span>
                        <?php elseif ($status === 'upcoming'): ?>
                            <span class="ml-1 text-[10px] text-gray-500 align-middle">Sắp diễn ra</span>
                        <?php else: ?>
                            <span class="ml-1 text-[10px] text-gray-400 align-middle">Đã kết thúc</span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Countdown động cho slot đang chọn -->
            <div class="mt-3 flex items-center justify-between gap-3">
                <div class="text-sm font-medium">
                    <span id="slot-label" class="text-gray-700">–:–</span>
                    <span id="slot-status"
                        class="ml-2 px-2 py-0.5 rounded-full text-[11px] font-bold bg-gray-100 text-gray-600">...</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-700">Còn lại:</span>
                    <span id="countdown" class="text-2xl font-extrabold text-red-600 tracking-wider">00:00:00</span>
                </div>
            </div>
        </div>

        <!-- Danh sách sản phẩm theo slot -->
        <?php foreach ($slots as $s):
            $visible = ($s['id'] === $defaultSlotId) ? '' : 'hidden';
            ?>
            <section id="slot-<?= $s['id'] ?>" class="slot-products <?= $visible ?> mb-10">
                <h3 class="text-lg font-bold mb-3">
                    <?= substr($s['slot_time'], 0, 5) ?> – <?= substr($s['end_time'], 0, 5) ?>
                    <span
                        class="ml-2 text-sm font-medium <?= $s['status'] === 'active' ? 'text-orange-600' : ($s['status'] === 'upcoming' ? 'text-gray-600' : 'text-gray-400') ?>">
                        (<?= $s['status'] === 'active' ? 'Đang diễn ra' : ($s['status'] === 'upcoming' ? 'Sắp diễn ra' : 'Đã kết thúc') ?>)
                    </span>
                </h3>

                <?php if (empty($s['products'])): ?>
                    <div class="text-gray-500 text-sm italic">Chưa có sản phẩm trong khung giờ này.</div>
                <?php else: ?>
                    <?php foreach (chunkProducts($s['products'], 5) as $row): ?>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-5">
                            <?php foreach ($row as $p)
                                displayProductCard($p, $s['status']); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>

        <!-- Giữ nguyên các block khác của bạn -->
        <?php include __DIR__ . '/partials/banner-slider.php'; ?>

        <?php
        $items = $data['recommended'] ?? [];
        include __DIR__ . '/partials/recommendations.php';
        ?>

        <?php
        $items = $data['exclusive'] ?? [];
        include __DIR__ . '/partials/exclusive.php';
        ?>

        <?php include __DIR__ . '/partials/promo-banners.php'; ?>

    </main>

    <?php include __DIR__ . '/partials/footer.php'; ?>

    <script>
        // Data slots cho JS
        const SLOTS = <?= json_encode($slotsForJs, JSON_UNESCAPED_UNICODE); ?>;
        let currentSlotId = <?= json_encode($defaultSlotId); ?>;

        const $countdown = document.getElementById('countdown');
        const $slotLabel = document.getElementById('slot-label');
        const $slotStatus = document.getElementById('slot-status');

        function formatTime(msLeft) {
            if (msLeft < 0) msLeft = 0;
            const totalSeconds = Math.floor(msLeft / 1000);
            const h = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
            const m = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
            const s = String(totalSeconds % 60).padStart(2, '0');
            return `${h}:${m}:${s}`;
        }

        function getSlotById(id) {
            return SLOTS.find(s => s.id === id);
        }

        function updateVisibleSlot(id) {
            document.querySelectorAll('.slot-products').forEach(el => el.classList.add('hidden'));
            const sec = document.getElementById('slot-' + id);
            if (sec) sec.classList.remove('hidden');

            document.querySelectorAll('.time-btn').forEach(btn => {
                const sid = Number(btn.getAttribute('data-slot'));
                btn.classList.remove('bg-white', 'text-orange-600', 'border-orange-500', 'shadow');
                // giữ style mặc định theo status
                const slot = getSlotById(sid);
                if (!slot) return;
                if (sid === id) {
                    btn.classList.add('bg-white', 'text-orange-600', 'border-orange-500', 'shadow');
                }
            });
        }

        function setMetaForSlot(slot) {
            if (!slot) return;
            $slotLabel.textContent = slot.label;
            const now = Date.now();
            let label = '...';
            let target = now;

            if (now < slot.start_ts) {
                label = 'Sắp diễn ra';
                target = slot.start_ts;
                $slotStatus.className = 'ml-2 px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700';
            } else if (now >= slot.start_ts && now < slot.end_ts) {
                label = 'Đang diễn ra';
                target = slot.end_ts;
                $slotStatus.className = 'ml-2 px-2 py-0.5 rounded-full text-[11px] font-bold bg-orange-50 text-orange-700';
            } else {
                label = 'Đã kết thúc';
                // tìm slot tiếp theo nếu có
                const upcoming = SLOTS.find(s => s.start_ts > now) || SLOTS[0];
                target = upcoming ? (upcoming.start_ts > now ? upcoming.start_ts : upcoming.end_ts) : now;
                $slotStatus.className = 'ml-2 px-2 py-0.5 rounded-full text-[11px] font-bold bg-gray-100 text-gray-500';
            }
            $slotStatus.textContent = label;

            return target;
        }

        // Countdown loop cho slot hiện tại, tự nhảy slot khi hết giờ
        let timer = null;
        function startCountdownFor(slotId) {
            currentSlotId = slotId;
            updateVisibleSlot(slotId);

            const slot = getSlotById(slotId);
            if (!slot) return;

            let target = setMetaForSlot(slot);
            if (timer) clearInterval(timer);

            timer = setInterval(() => {
                const now = Date.now();
                const msLeft = target - now;
                $countdown.textContent = formatTime(msLeft);

                if (msLeft <= 0) {
                    clearInterval(timer);
                    // sau khi hết: nếu đang chờ bắt đầu -> chuyển sang LIVE; nếu vừa kết thúc -> chuyển khung tiếp theo
                    const freshNow = Date.now();
                    if (freshNow >= slot.start_ts && freshNow < slot.end_ts) {
                        // vừa chuyển sang LIVE (đang diễn ra)
                        startCountdownFor(slotId);
                    } else {
                        // tìm khung tiếp theo
                        const next = SLOTS.find(s => s.start_ts > freshNow) || SLOTS[0];
                        startCountdownFor(next.id);
                    }
                }
            }, 1000);
        }

        // Event click nút giờ
        document.querySelectorAll('.time-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = Number(btn.getAttribute('data-slot'));
                startCountdownFor(id);
            });
        });

        // Khởi tạo
        if (currentSlotId) {
            startCountdownFor(currentSlotId);
        } else if (SLOTS.length) {
            startCountdownFor(SLOTS[0].id);
        }
    </script>
</body>

</html>