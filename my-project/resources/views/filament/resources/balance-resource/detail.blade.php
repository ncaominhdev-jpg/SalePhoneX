                                                                                                                                                                        {{-- resources/views/filament/resources/balance-resource/detail.blade.php --}}
<div class="p-4 bg-white shadow rounded-xl">
    <h2 class="text-lg font-bold mb-4">Chi tiết phiếu điều chỉnh #{{ $record->code }}</h2>

    <p><strong>Kho:</strong> {{ $record->audit->warehouse->name ?? 'N/A' }}</p>
    <p><strong>Phiếu kiểm kê:</strong> {{ $record->audit->code ?? 'N/A' }}</p>
    <p><strong>Ngày tạo:</strong> {{ $record->created_at->format('d/m/Y H:i') }}</p>

    <hr class="my-4">

    <h3 class="font-semibold mb-2">Danh sách sản phẩm điều chỉnh:</h3>
    <table class="table-auto w-full text-sm border border-gray-300">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="border px-2 py-1">Sản phẩm</th>
                <th class="border px-2 py-1">Biến thể</th>
                <th class="border px-2 py-1 text-center">SL hệ thống</th>
                <th class="border px-2 py-1 text-center">SL thực tế</th>
                <th class="border px-2 py-1 text-center">SL điều chỉnh</th>
                <th class="border px-2 py-1">Lý do</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($record->details as $detail)
                <tr>
                    <td class="border px-2 py-1">
                        {{ $detail->productVariant->product->name ?? '---' }}
                    </td>
                    <td class="border px-2 py-1">
                        {{ $detail->productVariant->variant_name ?? $detail->productVariant->name ?? '(Không có)' }}
                    </td>
                    <td class="border px-2 py-1 text-center">{{ $detail->recorded_quantity }}</td>
                    <td class="border px-2 py-1 text-center">{{ $detail->actual_quantity }}</td>
                    <td class="border px-2 py-1 text-center">{{ $detail->adjusted_quantity }}</td>
                    <td class="border px-2 py-1">{{ $detail->reason ?: '(Không có)' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
