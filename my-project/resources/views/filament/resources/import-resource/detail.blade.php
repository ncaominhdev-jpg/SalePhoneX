<x-filament::page>
    <div class="space-y-4">
        <h1 class="text-2xl font-bold">Chi tiết phiếu nhập #{{ $record->id }}</h1>

        <div>
            <strong>Kho:</strong> {{ $record->warehouse->name ?? 'N/A' }}<br>
            <strong>Người tạo:</strong> {{ $record->user->name ?? 'N/A' }}<br>
            <strong>Ghi chú:</strong> {{ $record->note }}<br>
            <strong>Ngày tạo:</strong> {{ $record->created_at->format('d/m/Y') }}<br>
            <strong>Trạng thái:</strong> {{ strtoupper($record->status) }}
        </div>

        <hr>

        <h2 class="text-lg font-semibold">Danh sách sản phẩm</h2>
        <table class="w-full text-sm text-left text-gray-700 border mt-4">
    <thead class="bg-gray-100 text-gray-900 font-semibold">
        <tr>
            <th class="border px-2 py-1">Tên sản phẩm</th>
            <th class="border px-2 py-1">Biến thể</th>
            <th class="border px-2 py-1">Số lượng</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($record->importDetails as $detail)
            <tr>
                <td class="border px-2 py-1">
                    {{ $detail->productVariant?->product?->name ?? 'N/A' }}
                </td>
                <td class="border px-2 py-1">
                    {{ $detail->productVariant?->name ?? 'N/A' }}
                </td>
                <td class="border px-2 py-1">
                    {{ $detail->quantity ?? '0' }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
    </div>
</x-filament::page>