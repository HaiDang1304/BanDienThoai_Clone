<!-- Banner Quảng Cáo (Swiper Slider) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

<div class="swiper mySwiper mb-8 rounded-lg overflow-hidden">
    <div class="swiper-wrapper">
        <!-- Slide 1 -->
        <div class="swiper-slide">
            <img src="http://localhost/PHP/BanDienThoai_Clone/public/assets/images/banner_qc/banner1.png" alt="Banner 1"
                class="w-full h-[600px] md:h-[180px] object-cover">
        </div>
        <!-- Slide 2 -->
        <div class="swiper-slide">
            <img src="http://localhost/PHP/BanDienThoai_Clone/public/assets/images/banner_qc/banner2.png" alt="Banner 2"
                class="w-full h-[600px] md:h-[180px] object-cover">
        </div>
        <!-- Slide 3 -->
        <div class="swiper-slide">
            <img src="http://localhost/PHP/BanDienThoai_Clone/public/assets/images/banner_qc/banner3.png" alt="Banner 3"
                class="w-full h-[600px] md:h-[180px] object-cover">
        </div>
        <div class="swiper-slide">
            <img src="http://localhost/PHP/BanDienThoai_Clone/public/assets/images/banner_qc/banner4.png" alt="Banner 4"
                class="w-full h-[600px] md:h-[180px] object-cover">
        </div>
        <div class="swiper-slide">
            <img src="http://localhost/PHP/BanDienThoai_Clone/public/assets/images/banner_qc/banner5.png" alt="Banner 5"
                class="w-full h-[600px] md:h-[180px] object-cover">
        </div>
    </div>

    <!-- Nút điều hướng -->
    <div class="swiper-button-next"></div>
    <div class="swiper-button-prev"></div>
    <div class="swiper-pagination"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
    const swiper = new Swiper(".mySwiper", {
        slidesPerView: 2,
        spaceBetween: 20,
        loop: true,
        autoplay: {
            delay: 4000,
            disableOnInteraction: false,
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true,
        },
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
        breakpoints: {
            0: { slidesPerView: 1 },
            768: { slidesPerView: 2 },
        },
    });
</script>