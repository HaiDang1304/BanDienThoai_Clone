<!-- app/views/partials/auth-modal.php -->
<div id="authModal" class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-[9999] items-center justify-center">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 relative">

    <!-- Nút đóng -->
    <button id="closeModal" type="button" aria-label="Đóng"
            class="absolute top-3 right-3 text-gray-500 hover:text-gray-800 text-xl font-bold">✕</button>

    <!-- Tabs -->
    <div class="flex justify-center mb-5 border-b border-gray-200">
      <button id="loginTab" type="button"
              class="flex-1 py-2 font-semibold text-yellow-600 border-b-2 border-yellow-500">
        Đăng nhập
      </button>
      <button id="registerTab" type="button"
              class="flex-1 py-2 font-semibold text-gray-500 hover:text-yellow-600">
        Đăng ký
      </button>
    </div>

    <!-- Form Đăng nhập -->
    <form id="loginForm" class="space-y-4" novalidate>
      <div>
        <label class="block text-sm font-medium mb-1">Email</label>
        <input name="email" type="email" placeholder="Nhập vào Email"
               class="w-full border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-yellow-500" required>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Mật khẩu</label>
        <input name="password" type="password" placeholder="••••••••"
               class="w-full border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-yellow-500" required>
      </div>

      <p id="loginError" class="text-red-600 text-sm"></p>

      <button type="submit"
              class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2 rounded-lg transition">
        Đăng nhập
      </button>

      <a href="/BanDienThoai_Clone/public/index.php?url=Auth/google"
         class="flex items-center justify-center gap-2 w-full border py-2 rounded-lg hover:bg-gray-50 transition">
        <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" class="w-5 h-5" alt="">
        <span>Đăng nhập bằng Google</span>
      </a>

      <p class="text-center text-sm text-gray-500">
        Chưa có tài khoản?
        <a href="#" id="switchToRegister" class="text-yellow-600 font-medium hover:underline">Đăng ký ngay</a>
      </p>
    </form>

    <!-- Form Đăng ký -->
    <form id="registerForm" class="space-y-4 hidden" novalidate>
      <div>
        <label class="block text-sm font-medium mb-1">Họ và tên</label>
        <input name="name" type="text" placeholder="Nguyễn Văn A"
               class="w-full border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-yellow-500" required>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Email</label>
        <input name="email" type="email" placeholder="Nhập vào Email"
               class="w-full border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-yellow-500" required>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Mật khẩu</label>
        <!-- ràng buộc: ≥6 ký tự, có hoa, thường, số -->
        <input name="password" type="password"
               pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}"
               title="Ít nhất 6 ký tự và có chữ HOÁ, thường và số"
               placeholder="Ít nhất 6 ký tự, có HOÁ, thường & số"
               class="w-full border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-yellow-500" required>
      </div>

      <p id="registerError" class="text-red-600 text-sm"></p>
      <p id="registerSuccess" class="text-green-600 text-sm"></p>

      <button type="submit"
              class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2 rounded-lg transition">
        Đăng ký
      </button>

      <a href="/BanDienThoai_Clone/public/index.php?url=Auth/google"
         class="flex items-center justify-center gap-2 w-full border py-2 rounded-lg hover:bg-gray-50 transition">
        <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" class="w-5 h-5" alt="">
        <span>Đăng nhập bằng Google</span>
      </a>

      <p class="text-center text-sm text-gray-500">
        Đã có tài khoản?
        <a href="#" id="switchToLogin" class="text-yellow-600 font-medium hover:underline">Đăng nhập</a>
      </p>
    </form>
  </div>
</div>

<script>
(function(){
  const modal = document.getElementById('authModal');
  const closeModal = document.getElementById('closeModal');
  const loginForm = document.getElementById('loginForm');
  const registerForm = document.getElementById('registerForm');
  const loginTab = document.getElementById('loginTab');
  const registerTab = document.getElementById('registerTab');
  const switchToRegister = document.getElementById('switchToRegister');
  const switchToLogin = document.getElementById('switchToLogin');

  // Helpers
  const openModal = () => { modal.classList.remove('hidden'); modal.classList.add('flex'); };
  const hideModal = () => { modal.classList.add('hidden'); };

  // Mở modal (từ bất kỳ nút nào có data-open-auth)
  document.querySelectorAll('[data-open-auth]').forEach(btn => {
    btn.addEventListener('click', e => { e.preventDefault(); openModal(); });
  });

  // Đóng modal
  closeModal.addEventListener('click', hideModal);
  modal.addEventListener('click', e => { if (e.target === modal) hideModal(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') hideModal(); });

  // Chuyển tab
  const showLogin = () => {
    registerForm.classList.add('hidden');
    loginForm.classList.remove('hidden');
    loginTab.classList.add('text-yellow-600','border-b-2','border-yellow-500');
    registerTab.classList.remove('text-yellow-600','border-yellow-500');
  };
  const showRegister = () => {
    loginForm.classList.add('hidden');
    registerForm.classList.remove('hidden');
    registerTab.classList.add('text-yellow-600','border-b-2','border-yellow-500');
    loginTab.classList.remove('text-yellow-600','border-yellow-500');
  };
  loginTab.addEventListener('click', showLogin);
  registerTab.addEventListener('click', showRegister);
  if (switchToLogin) switchToLogin.addEventListener('click', (e)=>{e.preventDefault();showLogin();});
  if (switchToRegister) switchToRegister.addEventListener('click', (e)=>{e.preventDefault();showRegister();});

  // ===== Submit AJAX: LOGIN (redirect theo role) =====
  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = loginForm.querySelector('button[type="submit"]');
    const err = document.getElementById('loginError');
    if (err) err.textContent = '';
    btn.disabled = true;

    try {
      const res = await fetch('/BanDienThoai_Clone/public/index.php?url=Auth/login', {
        method: 'POST',
        body: new FormData(loginForm)
      });
      const data = await res.json();

      if (data.status === 'success') {
        // backend đã quyết định đích đến qua 'redirect'
        if (data.redirect) {
          window.location.href = data.redirect;
        } else {
          // fallback: reload nếu chưa có redirect
          window.location.reload();
        }
      } else {
        (err ? err.textContent = (data.message || 'Đăng nhập thất bại')
             : alert(data.message || 'Đăng nhập thất bại'));
      }
    } catch (e2) {
      (err ? err.textContent = 'Lỗi phản hồi máy chủ' : alert('Lỗi phản hồi máy chủ'));
    } finally {
      btn.disabled = false;
    }
  });

  // ===== Submit AJAX: REGISTER =====
  registerForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = registerForm.querySelector('button[type="submit"]');
    const err = document.getElementById('registerError');
    const ok  = document.getElementById('registerSuccess');
    if (err) err.textContent = '';
    if (ok)  ok.textContent  = '';
    btn.disabled = true;

    try {
      const res = await fetch('/BanDienThoai_Clone/public/index.php?url=Auth/register', {
        method: 'POST',
        body: new FormData(registerForm)
      });
      const data = await res.json();

      if (data.status === 'success') {
        if (ok) ok.textContent = data.message || 'Đăng ký thành công! Vui lòng kiểm tra email để xác minh.';
        // chuyển về tab đăng nhập để đăng nhập ngay
        showLogin();
      } else {
        (err ? err.textContent = (data.message || 'Đăng ký thất bại')
             : alert(data.message || 'Đăng ký thất bại'));
      }
    } catch (e2) {
      (err ? err.textContent = 'Lỗi phản hồi máy chủ' : alert('Lỗi phản hồi máy chủ'));
    } finally {
      btn.disabled = false;
    }
  });
})();
</script>
