<?php

namespace App\Filament\Resources\RequestResource\Pages;

use App\Filament\Resources\RequestResource;
use App\Models\Branch;
use App\Models\IssuanceRequest;
use App\Models\RequestDetail;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViewRequest extends ViewRecord
{
    protected static string $resource = RequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // --- NÚT DUYỆT & PHÂN PHỐI ĐÃ ĐƯỢC CHUYỂN VÀO ĐÂY ---
            Actions\Action::make('approve_and_dispatch')
                ->label('Duyệt & Phân phối')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool =>
                    Auth::user()?->role === 'admin' && $this->getRecord()->status === 'pending'
                )
                ->form(function () {
                    return [
                        Forms\Components\Repeater::make('dispatch_details')
                            ->label('Phân phối sản phẩm từ các kho')
                            ->schema([
                                Forms\Components\Placeholder::make('product_info')
                                    ->label('Sản phẩm & SL Yêu cầu')
                                    ->content(function ($get) {
                                        $detailId = $get('request_detail_id');
                                        $detail = RequestDetail::find($detailId);
                                        return $detail ? "{$detail->productVariant->name} (Y/C: {$detail->quantity})" : 'N/A';
                                    }),
                                Forms\Components\TextInput::make('approved_quantity')
                                    ->label('Số Lượng Duyệt')
                                    ->numeric()->required()
                                    ->default(fn ($get) => RequestDetail::find($get('request_detail_id'))?->quantity ?? 0),
                                Forms\Components\Select::make('from_branch_id')
                                    ->label('Chọn Kho Cung Cấp')
                                    ->searchable()->required()->native(false)
                                    ->options(function (callable $get) {
                                        $requestDetailId = $get('request_detail_id');
                                        if (!$requestDetailId) { return []; }
                                        $productVariantId = RequestDetail::find($requestDetailId)?->product_variant_id;
                                        if (!$productVariantId) { return []; }
                                        return Branch::whereHas('inventories', function (Builder $query) use ($productVariantId) {
                                            $query->where('product_variant_id', $productVariantId)->where('quantity', '>', 0);
                                        })->pluck('name', 'id');
                                    }),
                                Forms\Components\Hidden::make('request_detail_id')
                            ])
                            ->default(fn () => $this->getRecord()->requestDetails->map(fn ($detail) => [
                                'request_detail_id' => $detail->id,
                            ])->toArray())
                            ->minItems(1)->addable(false)->deletable(false)->columns(3),
                    ];
                })
                ->action(function (array $data) {
                    $record = $this->getRecord();
                    DB::transaction(function () use ($data, $record) {
                        $groupedByBranch = collect($data['dispatch_details'])->groupBy('from_branch_id');
                        foreach ($groupedByBranch as $branchId => $details) {
                            $issuanceRequest = IssuanceRequest::create([
                                'parent_request_id' => $record->id, 'from_branch_id' => $branchId,
                                'to_branch_id' => $record->creator->branch_id, 'created_by' => Auth::id(),
                                'status' => 'pending',
                            ]);
                            foreach ($details as $detailData) {
                                $originalDetail = RequestDetail::find($detailData['request_detail_id']);
                                if ($originalDetail && $detailData['approved_quantity'] > 0) {
                                    $issuanceRequest->details()->create([
                                        'product_variant_id' => $originalDetail->product_variant_id,
                                        'quantity' => $detailData['approved_quantity'],
                                    ]);
                                    $originalDetail->update(['approved_quantity' => $detailData['approved_quantity']]);
                                }
                            }
                        }
                        $record->update(['status' => 'approved']);
                        Notification::make()->title('Duyệt thành công!')->body('Đã tạo các phiếu xuất kho điều chuyển.')->success()->send();
                    });
                    return redirect(RequestResource::getUrl('index'));
                }),
        ];
    }
}