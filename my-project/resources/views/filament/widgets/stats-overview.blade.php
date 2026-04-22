<div class="flex space-x-6 justify-center">
    <div class="bg-gray-800 rounded-lg p-6 w-36 text-center shadow-lg">
        <div class="text-gray-400 mb-2">
            <svg class="mx-auto h-6 w-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M7 21v-2a4 4 0 0 1 3-3.87"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
        </div>
        <div class="text-gray-300 font-semibold text-lg">Người dùng</div>
        <div class="text-white text-3xl font-bold mt-1">{{ $usersCount }}</div>
        <div class="text-yellow-400 mt-2 text-sm">Tăng 5% so với tháng trước</div>
    </div>
    <div class="bg-gray-800 rounded-lg p-6 w-36 text-center shadow-lg">
        <div class="text-gray-400 mb-2">
            <svg class="mx-auto h-6 w-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 3h18v18H3z"></path>
                <path d="M3 9h18"></path>
                <path d="M9 21V9"></path>
            </svg>
        </div>
        <div class="text-gray-300 font-semibold text-lg">Đơn hàng</div>
        <div class="text-white text-3xl font-bold mt-1">{{ $ordersCount }}</div>
        <div class="text-green-400 mt-2 text-sm">Ổn định so với tháng trước</div>
    </div>
    <div class="bg-gray-800 rounded-lg p-6 w-36 text-center shadow-lg">
        <div class="text-gray-400 mb-2">
            <svg class="mx-auto h-6 w-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12" y2="16"></line>
            </svg>
        </div>
        <div class="text-gray-300 font-semibold text-lg">Doanh thu</div>
        <div class="text-white text-3xl font-bold mt-1">{{ number_format($revenue) }} VND</div>
        <div class="text-yellow-400 mt-2 text-sm">Tăng 12% so với tháng trước</div>
    </div>
</div>
