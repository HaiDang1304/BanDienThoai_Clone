<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<header
  class="sticky top-0 z-50 bg-gradient-to-b from-yellow-400 to-yellow-300 text-gray-900 border-b border-yellow-300/70 shadow-[0_2px_12px_rgba(0,0,0,0.08)] select-none">
  <?php if (session_status() !== PHP_SESSION_ACTIVE)
    session_start(); ?>

  <?php
  // Tính số lượng trong giỏ
  $cartCount = 0;
  if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $it)
      $cartCount += (int) $it['qty'];
  }
  ?>

  <!-- Topbar -->
  <div class="max-w-7xl mx-auto px-4">
    <div class="flex items-center justify-between py-3 gap-3">

      <!-- Logo -->
      <a href="/BanDienThoai_Clone/public/index.php" class="flex items-center gap-2 group">
        <div
          class="h-9 w-9 rounded-xl bg-white/70 border border-yellow-300/70 flex items-center justify-center shadow-sm group-hover:shadow transition">
          <!-- <span class="text-xl">📱</span> -->
        </div>
        <span class="hidden sm:inline font-extrabold tracking-tight text-lg group-hover:text-gray-800 transition">
          BanDienThoai.vn
        </span>
      </a>

      <!-- Search (desktop) -->
      <div class="hidden md:block flex-1">
        <!-- thêm form -->
        <form action="/BanDienThoai_Clone/public/search.php" method="get" class="relative">
          <input type="text" name="q" placeholder="Tìm kiếm sản phẩm, thương hiệu…"
            class="w-full pl-4 pr-12 py-2 rounded-full border border-yellow-500 bg-white/90 focus:bg-white outline-none focus:ring-2 focus:ring-yellow-600 shadow-sm placeholder-gray-500"
            value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
          <button type="submit"
            class="absolute right-1.5 top-1.5 px-4 py-1.5 rounded-full bg-yellow-500 hover:bg-yellow-600 text-white font-semibold transition">
            Tìm
          </button>
        </form>
      </div>


      <!-- Actions -->
      <div class="flex items-center gap-2 sm:gap-3 text-sm font-medium">

        <!-- Giỏ hàng -->
        <a href="/BanDienThoai_Clone/public/cart.php"
          class="relative inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-yellow-500/60 bg-white/70 hover:bg-white shadow-sm transition">
          <span></span><span class="hidden sm:inline">Giỏ hàng</span>
          <span
            class="absolute -right-2 -top-2 min-w-[22px] h-[22px] rounded-full bg-red-500 text-white text-xs font-bold flex items-center justify-center px-1 shadow">
            <?= (int) $cartCount ?>
          </span>
        </a>

        <!-- Lịch sử -->
        <?php if (!empty($_SESSION['auth'])): ?>
          <a href="/BanDienThoai_Clone/public/orders.php"
            class="px-3 py-1.5 rounded-full border bg-white/70 hover:bg-white shadow-sm transition">
             <span class="hidden sm:inline">Lịch sử</span>
          </a>
        <?php endif; ?>

        <!-- User -->
        <?php if (!empty($_SESSION['auth'])): ?>
          <div class="relative group">
            <button
              class="flex items-center gap-2 pl-1 pr-2 py-1 rounded-full border bg-white/70 hover:bg-white shadow-sm transition">
              <img src="<?= htmlspecialchars($_SESSION['auth']['avatar']) ?>" class="w-8 h-8 rounded-full object-cover"
                alt="">
              <span class="hidden md:inline"><?= htmlspecialchars($_SESSION['auth']['name']) ?></span>
              <svg class="w-4 h-4 opacity-70" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                  d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                  clip-rule="evenodd" />
              </svg>
            </button>
            <!-- Dropdown -->
            <div class="invisible opacity-0 group-hover:visible group-hover:opacity-100 transition
                        absolute right-0 w-48 bg-white rounded-xl shadow-lg border p-2">
              <a href="/BanDienThoai_Clone/public/orders.php" class="block px-3 py-2 rounded-lg hover:bg-gray-50">Đơn mua
                của tôi</a>
              <a href="/BanDienThoai_Clone/public/logout.php"
                class="block px-3 py-2 rounded-lg hover:bg-gray-50 text-red-600">Đăng xuất</a>
            </div>
          </div>
        <?php else: ?>
          <a href="#" data-open-auth
            class="px-3 py-1.5 rounded-full border bg-white/70 hover:bg-white shadow-sm transition">
             <span class="hidden sm:inline">Đăng nhập</span>
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Search (mobile) -->
    <div class="md:hidden pb-3">
      <div class="relative">
        <input type="text" placeholder="Tìm kiếm sản phẩm…"
          class="w-full pl-4 pr-10 py-2 rounded-full border border-yellow-500 bg-white/95 focus:bg-white outline-none focus:ring-2 focus:ring-yellow-600 placeholder-gray-500">
        <span class="absolute right-3 top-2.5"></span>
      </div>
    </div>
  </div>
</header>

<?php include __DIR__ . '/auth-modal.php'; ?>