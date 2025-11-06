<footer class="bg-gray-400 text-white mt-10 pt-10 pb-6 w-full">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Lưới 4 cột -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
      <!-- Cột 1: Logo & mô tả -->
      <div>
        <h3 class="text-2xl font-bold mb-3">BanDienThoai.vn</h3>
        <p class="text-sm opacity-90 mb-4">
          Hệ thống mua sắm điện thoại, phụ kiện và thiết bị công nghệ hàng đầu Việt Nam.
          Cam kết sản phẩm chính hãng, giá ưu đãi mỗi ngày.
        </p>
        <div class="flex gap-3">
          <a href="#" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 grid place-items-center">
            <img src="<?= htmlspecialchars($asset('/public/assets/icons/facebook.svg')) ?>" alt="Facebook" class="w-4 h-4">
          </a>
          <a href="#" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 grid place-items-center">
            <img src="<?= htmlspecialchars($asset('/public/assets/icons/instagram.svg')) ?>" alt="Instagram" class="w-4 h-4">
          </a>
          <a href="#" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 grid place-items-center">
            <img src="<?= htmlspecialchars($asset('/public/assets/icons/youtube.svg')) ?>" alt="YouTube" class="w-4 h-4">
          </a>
        </div>
      </div>

      <!-- Cột 2: Liên kết nhanh -->
      <div>
        <h4 class="font-semibold mb-3 text-lg">Liên kết nhanh</h4>
        <ul class="space-y-2 text-sm">
          <li><a href="#" class="hover:underline">Trang chủ</a></li>
          <li><a href="#" class="hover:underline">Sản phẩm</a></li>
          <li><a href="#" class="hover:underline">Ưu đãi</a></li>
          <li><a href="#" class="hover:underline">Tin tức</a></li>
          <li><a href="#" class="hover:underline">Liên hệ</a></li>
        </ul>
      </div>

      <!-- Cột 3: Hỗ trợ khách hàng -->
      <div>
        <h4 class="font-semibold mb-3 text-lg">Hỗ trợ khách hàng</h4>
        <ul class="space-y-2 text-sm">
          <li><a href="#" class="hover:underline">Chính sách bảo hành</a></li>
          <li><a href="#" class="hover:underline">Chính sách đổi trả</a></li>
          <li><a href="#" class="hover:underline">Chính sách vận chuyển</a></li>
          <li><a href="#" class="hover:underline">Hướng dẫn thanh toán</a></li>
          <li><a href="#" class="hover:underline">Câu hỏi thường gặp</a></li>
        </ul>
      </div>

      <!-- Cột 4: Liên hệ -->
      <div>
        <h4 class="font-semibold mb-3 text-lg">Liên hệ với chúng tôi</h4>
        <ul class="space-y-2 text-sm">
          <li>📍 123 Nguyễn Huệ, Q.1, TP. Hồ Chí Minh</li>
          <li>📞 Hotline: <a href="tel:18001234" class="font-semibold hover:underline">1800 1234</a></li>
          <li>✉️ Email: <a href="mailto:support@bandienthoai.vn" class="font-semibold hover:underline">support@bandienthoai.vn</a></li>
          <li>🕒 Giờ làm việc: 8h00 - 21h00 (T2 - CN)</li>
        </ul>
      </div>
    </div>

    <!-- Dòng bản quyền -->
    <div class="border-t border-white/20 mt-10 pt-4 text-center text-sm opacity-80">
      © <?= date('Y') ?> BanDienThoai.vn — Thiết kế & phát triển bởi <span class="font-semibold">Cao Trọng Phúc</span>.
    </div>
  </div>
</footer>
