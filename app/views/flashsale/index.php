<!-- app/views/flashsale/index.php -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flash Sale</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-yellow-400">
    <div class="container mx-auto px-2 py-4">
        <!-- Time Slots Header -->
        <div class="bg-white rounded-2xl shadow-lg p-4 mb-4">
            <div class="flex items-center justify-between gap-2 overflow-x-auto">
                <!-- Flash Sale Icon -->
                <div class="flex-shrink-0 bg-white border-2 border-yellow-400 rounded-xl px-4 py-2">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-bolt text-yellow-400 text-xl"></i>
                        <span class="font-bold text-gray-800">FLASH SALE</span>
                    </div>
                </div>

                <!-- Time Slot Buttons -->
                <div class="flex gap-2 flex-1 justify-end">
                    <?php foreach($data['timeSlots'] as $slot): ?>
                        <button 
                            class="time-slot-btn flex-shrink-0 px-6 py-3 rounded-xl transition-all duration-300 hover:shadow-md"
                            data-slot-id="<?= $slot['id'] ?>"
                            data-start-time="<?= $slot['start_time'] ?>"
                            data-end-time="<?= $slot['end_time'] ?>">
                            <div class="text-xs text-gray-600 mb-1">Ngày mai</div>
                            <div class="font-bold text-gray-800"><?= $slot['display_time'] ?></div>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div id="products-container" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <!-- Products will be loaded here -->
        </div>

        <!-- Load More Button -->
        <div class="text-center mt-6">
            <button class="bg-white text-gray-700 px-8 py-3 rounded-xl font-medium hover:bg-gray-100 transition-colors">
                Xem thêm sản phẩm <i class="fas fa-chevron-down ml-2"></i>
            </button>
        </div>
    </div>

    <script>
        let currentSlotId = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.time-slot-btn');
            if (buttons.length > 0) {
                checkCurrentTimeSlot();
                loadProducts(buttons[0].dataset.slotId);
            }

            // Add click events
            buttons.forEach(btn => {
                btn.addEventListener('click', function() {
                    buttons.forEach(b => {
                        b.classList.remove('bg-yellow-400', 'text-white');
                        b.classList.add('bg-gray-100');
                    });
                    this.classList.add('bg-yellow-400', 'text-white');
                    this.classList.remove('bg-gray-100');
                    
                    loadProducts(this.dataset.slotId);
                });
            });

            // Auto refresh every minute
            setInterval(checkCurrentTimeSlot, 60000);
        });

        function checkCurrentTimeSlot() {
            const now = new Date();
            const currentTime = now.getHours().toString().padStart(2, '0') + ':' + 
                              now.getMinutes().toString().padStart(2, '0') + ':00';
            
            const buttons = document.querySelectorAll('.time-slot-btn');
            buttons.forEach(btn => {
                const startTime = btn.dataset.startTime;
                const endTime = btn.dataset.endTime;
                
                if (currentTime >= startTime && currentTime <= endTime) {
                    btn.click();
                }
            });
        }

        async function loadProducts(slotId) {
            currentSlotId = slotId;
            const container = document.getElementById('products-container');
            container.innerHTML = '<div class="col-span-full text-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-white"></i></div>';

            try {
                const response = await fetch(`/flashsale/getProducts?slot_id=${slotId}`);
                const data = await response.json();

                if (data.status === 'upcoming') {
                    container.innerHTML = `
                        <div class="col-span-full bg-white rounded-2xl p-12 text-center">
                            <i class="fas fa-clock text-6xl text-yellow-400 mb-4"></i>
                            <h3 class="text-2xl font-bold text-gray-800 mb-2">Sắp Mở Bán</h3>
                            <p class="text-gray-600">Chương trình sẽ bắt đầu lúc ${data.slot.display_time}</p>
                        </div>
                    `;
                } else if (data.status === 'ended') {
                    container.innerHTML = `
                        <div class="col-span-full bg-white rounded-2xl p-12 text-center">
                            <i class="fas fa-check-circle text-6xl text-gray-400 mb-4"></i>
                            <h3 class="text-2xl font-bold text-gray-800 mb-2">Đã Kết Thúc</h3>
                            <p class="text-gray-600">Khung giờ này đã kết thúc</p>
                        </div>
                    `;
                } else if (data.products && data.products.length > 0) {
                    container.innerHTML = data.products.map(product => createProductCard(product)).join('');
                } else {
                    container.innerHTML = `
                        <div class="col-span-full bg-white rounded-2xl p-12 text-center">
                            <i class="fas fa-box-open text-6xl text-gray-400 mb-4"></i>
                            <h3 class="text-2xl font-bold text-gray-800">Không có sản phẩm</h3>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                container.innerHTML = `
                    <div class="col-span-full bg-white rounded-2xl p-12 text-center">
                        <i class="fas fa-exclamation-triangle text-6xl text-red-400 mb-4"></i>
                        <h3 class="text-2xl font-bold text-gray-800">Có lỗi xảy ra</h3>
                    </div>
                `;
            }
        }

        function createProductCard(product) {
            const hasAI = product.has_ai_feature ? '<span class="absolute top-2 left-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white text-xs px-2 py-1 rounded-md">AI</span>' : '';
            
            return `
                <div class="bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-xl transition-shadow">
                    <div class="relative">
                        <img src="${product.image_url}" alt="${product.name}" class="w-full h-48 object-cover">
                        ${hasAI}
                        ${product.badge ? `<span class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full">${product.badge}</span>` : ''}
                    </div>
                    <div class="p-3">
                        <h3 class="text-sm font-medium text-gray-800 mb-2 line-clamp-2 h-10">${product.name}</h3>
                        
                        <div class="flex items-baseline gap-2 mb-2">
                            <span class="text-xl font-bold text-red-500">${formatPrice(product.flash_price)}</span>
                        </div>
                        
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-sm text-gray-400 line-through">${formatPrice(product.original_price)}</span>
                            <span class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded">${product.discount_percent}</span>
                        </div>

                        <div class="bg-yellow-400 text-white text-center py-2 rounded-lg font-medium text-sm mb-2">
                            <i class="fas fa-bolt mr-1"></i> ${product.stock_text}
                        </div>

                        <button class="w-full bg-yellow-400 hover:bg-yellow-500 text-white font-bold py-2 rounded-lg transition-colors">
                            Mua ngay
                        </button>
                    </div>
                </div>
            `;
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(price);
        }
    </script>
</body>
</html>