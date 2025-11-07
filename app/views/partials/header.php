<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<header class="bg-gradient-to-b from-yellow-400 to-yellow-300 text-gray-900 shadow-md select-none sticky top-0 z-50">
  <!-- Topbar -->
  <div class="max-w-7xl mx-auto flex items-center justify-between px-4 py-3">
    <!-- Logo -->
    <a href="/" class="flex items-center space-x-2">
      <!-- <img src="https://cdn.tgdd.vn/mwgcart/mwg-site/desktop/images/logo.png" alt="Logo" class="h-10 drop-shadow-md"> -->
      <span class="hidden md:inline font-bold text-lg tracking-tight">BanDienThoai.vn</span>
    </a>

    <!-- Search -->
    <div class="flex-1 mx-6 hidden md:block">
      <div class="relative">
        <input type="text" placeholder="🔍 Tìm kiếm sản phẩm, thương hiệu..."
          class="w-full py-2 pl-4 pr-12 rounded-full border border-yellow-500 focus:ring-2 focus:ring-yellow-600 outline-none shadow-sm placeholder-gray-500">
        <button
          class="absolute right-2 top-1.5 bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-full text-sm font-semibold transition">
          Tìm
        </button>
      </div>
    </div>

    <!-- Actions -->
    <?php
    session_start();
    ?>
    <div class="flex items-center space-x-3 text-sm font-medium">
      <?php if (!empty($_SESSION['auth'])): ?>
        <div class="flex items-center gap-2">
          <img src="<?= htmlspecialchars($_SESSION['auth']['avatar']) ?>" class="w-7 h-7 rounded-full" alt="">
          <span>Xin chào, <?= htmlspecialchars($_SESSION['auth']['name']) ?></span>
          <a href="/BanDienThoai_Clone/public/Auth/logout"
            class="ml-2 px-3 py-1 rounded-full border hover:bg-gray-100">Đăng xuất</a>
        </div>
      <?php else: ?>
        <a href="#" data-open-auth class="flex items-center gap-1 hover:text-red-600 transition">
          👤 <span class="hidden sm:inline">Đăng nhập</span>
        </a>
      <?php endif; ?>
    </div>


  </div>

  <!-- Search (mobile) -->
  <div class="px-4 pb-3 md:hidden">
    <div class="relative">
      <input type="text" placeholder="Tìm kiếm sản phẩm..."
        class="w-full py-2 pl-3 pr-10 rounded-full border border-yellow-500 focus:ring-2 focus:ring-yellow-600 outline-none placeholder-gray-500">
      <span class="absolute right-3 top-2.5 text-gray-600">🔍</span>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="bg-yellow-200/80 backdrop-blur-sm border-t border-yellow-300 shadow-inner">
    <ul
      class="max-w-7xl mx-auto flex flex-wrap items-center justify-center md:justify-start px-4 py-2 text-sm font-medium gap-3 md:gap-5">
      <li><a href="#" class="hover:text-red-600 flex items-center gap-1">📱 <span>Điện thoại</span></a></li>
      <li><a href="#" class="hover:text-red-600 flex items-center gap-1">💻 <span>Laptop</span></a></li>
      <li><a href="#" class="hover:text-red-600 flex items-center gap-1">🎧 <span>Phụ kiện</span></a></li>
      <li><a href="#" class="hover:text-red-600 flex items-center gap-1">⌚ <span>Smartwatch</span></a></li>
      <li><a href="#" class="hover:text-red-600 flex items-center gap-1">🕒 <span>Đồng hồ</span></a></li>
      <li><a href="#" class="hover:text-red-600 flex items-center gap-1">📟 <span>Tablet</span></a></li>
      <li><a href="#" class="hover:text-red-600 flex items-center gap-1">♻️ <span>Máy cũ - Thu cũ</span></a></li>
      <li><a href="#" class="hover:text-red-600 flex items-center gap-1">🖥 <span>Màn hình - Máy in</span></a></li>
      <li><a href="#" class="hover:text-red-600 flex items-center gap-1">💳 <span>Sim, Thẻ cào</span></a></li>
      <li><a href="#" class="hover:text-red-600 flex items-center gap-1">🧾 <span>Dịch vụ tiện ích</span></a></li>
    </ul>
  </nav>
</header>

<?php include __DIR__ . '/auth-modal.php'; ?>