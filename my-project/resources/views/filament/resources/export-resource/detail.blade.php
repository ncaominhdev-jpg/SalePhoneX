<x-filament::page>
<div class="p-4 bg-white shadow rounded-xl">
    <h2 class="text-lg font-bold mb-4">Chi tiết phiếu xuất kho {{ $record->code }}</h2>

    <p><strong>Kho xuất:</strong> {{ $record->fromWarehouse->name ?? 'N/A' }}</p>
    @if ($record->export_type === 'transfer')
        <p><strong>Kho nhận:</strong> {{ $record->toWarehouse->name ?? 'N/A' }}</p>
    @elseif ($record->export_type === 'order')
        <p><strong>Đơn hàng:</strong> #{{ $record->order_id }}</p>
    @endif
    <p><strong>Loại xuất:</strong> {{ ucfirst($record->export_type) }}</p>
    <p><strong>Ngày tạo:</strong> {{ $record->created_at->format('d/m/Y H:i') }}</p>
    <p><strong>Người tạo:</strong> {{ $record->user->name ?? 'N/A' }}</p>
    <p><strong>Ghi chú:</strong> {{ $record->note ?? '(Không có)' }}</p>

    <hr class="my-4">

    <h3 class="font-semibold mb-2">Danh sách sản phẩm xuất kho:</h3>
    <table class="table-auto w-full text-sm border border-gray-300">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="border px-2 py-1">Sản phẩm</th>
                <th class="border px-2 py-1">Số lượng</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($record->exportDetails as $detail)
                <tr>
                    <td class="border px-2 py-1">
                        {{ $detail->productVariant->product->name ?? '' }} -
                        {{ $detail->productVariant->name ?? '' }}
                    </td>
                    <td class="border px-2 py-1 text-center">{{ $detail->quantity }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
</x-filament::page>
