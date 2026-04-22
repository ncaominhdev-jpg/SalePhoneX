<x-filament::widget>
    <x-filament::card class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 gap-6">
            {{-- Người dùng mới --}}
            <div>
                <h3 class="text-lg font-bold mb-2">👥 Người dùng mới</h3>
                <table class="w-full text-sm text-left border border-gray-200 rounded-md overflow-hidden">
                    <thead class="bg-gray-100 font-semibold">
                        <tr>
                            <th class="px-4 py-2 border-b border-gray-300">Tên</th>
                            <th class="px-4 py-2 border-b border-gray-300">Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->getRecentUsers() as $user)
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $user->name }}</td>
                                <td class="px-4 py-2">{{ $user->email }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>
                <h3 class="text-lg font-bold mb-2">🏷 Phiếu nhập kho gần đây</h3>
                <table class="w-full text-sm text-left border border-gray-200 rounded-md overflow-hidden">
                    <thead class="bg-gray-100 font-semibold">
                        <tr>
                            <th class="px-4 py-2 border-b border-gray-300">Mã phiếu</th>
                            <th class="px-4 py-2 border-b border-gray-300">Chi nhánh</th>
                            <th class="px-4 py-2 border-b border-gray-300">Ngày nhập</th>
                            <th class="px-4 py-2 border-b border-gray-300">Người tạo</th>
                            <th class="px-4 py-2 border-b border-gray-300">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->getRecentImports() as $import)
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $import->id }}</td>
                                <td class="px-4 py-2">{{ $import->warehouse->name ?? '---' }}</td>
                                <td class="px-4 py-2">{{ $import->import_date->format('d/m/Y') }}</td>
                                <td class="px-4 py-2">{{ $import->user->name ?? '---' }}</td>
                                <td class="px-4 py-2">{{ Str::limit($import->note, 40) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-6">
            {{-- Đơn hàng mới --}}
            <div>
                <h3 class="text-lg font-bold mb-2">📦 Đơn hàng gần đây</h3>
                <table class="w-full text-sm text-left border border-gray-200 rounded-md overflow-hidden">
                    <thead class="bg-gray-100 font-semibold">
                        <tr>
                            <th class="px-4 py-2 border-b border-gray-300">ID</th>
                            <th class="px-4 py-2 border-b border-gray-300">Khách hàng</th>
                            <th class="px-4 py-2 border-b border-gray-300">Người nhận</th>
                            <th class="px-4 py-2 border-b border-gray-300">SĐT</th>
                            <th class="px-4 py-2 border-b border-gray-300">Tổng tiền</th>
                            <th class="px-4 py-2 border-b border-gray-300">Thanh toán</th>
                            <th class="px-4 py-2 border-b border-gray-300">Trạng thái</th>
                            <th class="px-4 py-2 border-b border-gray-300">Địa chỉ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->getRecentOrders() as $order)
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $order->id }}</td>
                                <td class="px-4 py-2">{{ $order->user->name ?? '---' }}</td>
                                <td class="px-4 py-2">{{ $order->recipient_name }}</td>
                                <td class="px-4 py-2">{{ $order->phone }}</td>
                                <td class="px-4 py-2 font-semibold">{{ number_format($order->total_amount, 0, ',', '.') }}đ</td>
                                <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $order->payment_method) }}</td>
                                <td class="px-4 py-2">
                                    @if ($order->status === 'pending')
                                        <span class="inline-block px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-200 rounded-full">Chờ xác nhận</span>
                                    @elseif ($order->status === 'confirmed')
                                        <span class="inline-block px-2 py-1 text-xs font-semibold text-green-800 bg-green-200 rounded-full">Đã xác nhận</span>
                                    @elseif ($order->status === 'shipped')
                                        <span class="inline-block px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-200 rounded-full">Đã giao vận</span>
                                    @elseif ($order->status === 'delivered')
                                        <span class="inline-block px-2 py-1 text-xs font-semibold text-green-900 bg-green-300 rounded-full">Đã giao hàng</span>
                                    @elseif ($order->status === 'cancelled')
                                        <span class="inline-block px-2 py-1 text-xs font-semibold text-red-800 bg-red-200 rounded-full">Đã hủy</span>
                                    @else
                                        <span class="inline-block px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-200 rounded-full">{{ ucfirst($order->status) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2">{{ $order->address }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>
