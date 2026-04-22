{{-- resources/views/filament/resources/audit-resource/detail.blade.php --}}
<div class="p-4 bg-white shadow rounded-xl">
    <h2 class="text-lg font-bold mb-4">Chi tiết phiếu kiểm kê #{{ $record->id }}</h2>

    <p><strong>Kho:</strong> {{ $record->warehouse->name ?? 'N/A' }}</p>
    <p><strong>Ngày kiểm kê:</strong> {{ $record->audit_date ? \Carbon\Carbon::parse($record->audit_date)->format('d/m/Y H:i') : 'N/A' }}</p>
    <p><strong>Ghi chú:</strong> {{ $record->note ?: '(Không có)' }}</p>

    <hr class="my-4">

    <h3 class="font-semibold mb-2">Danh sách sản phẩm kiểm kê:</h3>
    <table class="table-auto w-full text-sm border border-gray-300">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="border px-2 py-1">Sản phẩm</th>
                <th class="border px-2 py-1">Biến thể</th>
                <th class="border px-2 py-1 text-center">SL hệ thống</th>
                <th class="border px-2 py-1 text-center">SL thực tế</th>
                <th class="border px-2 py-1 text-center">Chênh lệch</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($record->reports as $report)
                <tr>
                    <td class="border px-2 py-1">
                        {{ $report->productVariant->product->name ?? '---' }}
                    </td>
                    <td class="border px-2 py-1">
                        {{ $report->productVariant->name ?? '---' }}
                    </td>
                    <td class="border px-2 py-1 text-center">{{ $report->recorded_quantity }}</td>
                    <td class="border px-2 py-1 text-center">{{ $report->actual_quantity }}</td>
                    <td class="border px-2 py-1 text-center">
                        {{ $report->actual_quantity - $report->recorded_quantity }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
