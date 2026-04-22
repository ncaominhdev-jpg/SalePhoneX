<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Phiếu nhập kho</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        h2 { text-align: center; }
    </style>
</head>
<body>
    <h2>PHIẾU NHẬP KHO</h2>

    <p><strong>Chi nhánh:</strong> {{ $import->warehouse->name }}</p>
    <p><strong>Ngày nhập:</strong> {{ $import->import_date }}</p>
    <p><strong>Người nhập:</strong> {{ $import->user->name }}</p>
    <p><strong>Ghi chú:</strong> {{ $import->note }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Tên sản phẩm</th>
                <th>Số lượng</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($import->importDetails as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->productVariant->name }}</td>
                    <td>{{ $detail->quantity }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
