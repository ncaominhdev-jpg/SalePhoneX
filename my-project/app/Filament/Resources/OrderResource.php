<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\SelectColumn;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Đơn hàng';

    protected static ?string $modelLabel = 'Đơn hàng';

    protected static ?string $pluralModelLabel = 'Đơn hàng';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Thông tin đơn hàng')
                    ->schema([
                        TextInput::make('id')
                            ->label('Mã đơn')
                            ->disabled(),

                        Placeholder::make('branch_display')
                            ->label('Chi nhánh')
                            ->content(fn($record) => $record?->branch?->name ?? '—'),


                        // Thay đổi cách hiển thị khách hàng
                        Placeholder::make('customer_info')
                            ->label('Khách hàng')
                            ->content(fn($record) => $record->user?->name ?? 'Khách vãng lai'),

                        TextInput::make('total_amount')
                            ->label('Tổng tiền')
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.') . 'VND'),

                        // Thay đổi cách hiển thị phương thức thanh toán
                        Placeholder::make('payment_method_display')
                            ->label('Phương thức thanh toán')
                            ->content(function ($record) {
                                $paymentMethods = [
                                    'cash' => 'Tiền mặt',
                                    'vnpay' => 'vnpay',
                                    'momo' => 'momo',
                                    'cod' => 'COD'
                                ];

                                return $paymentMethods[$record->payment_method] ?? 'Chưa chọn';
                            }),

                        Select::make('status')
                            ->label('Trạng thái')
                            ->options([
                                'pending' => 'Chờ xác nhận',
                                'confirmed' => 'Đã xác nhận',
                                'shipped' => 'Đang giao hàng',
                                'delivered' => 'Đã giao hàng',
                                'cancelled' => 'Đã hủy'
                            ])
                            ->disabled(),

                        Textarea::make('note')
                            ->label('Ghi chú')
                            ->disabled(),
                    ])
                    ->columns(2),

                Section::make('Chi tiết sản phẩm')
                    ->schema([
                        Placeholder::make('order_details')
                            ->label('')
                            ->content(function ($record) {
                                if (!$record || !$record->orderDetails) {
                                    return 'Không có sản phẩm nào';
                                }

                                $html = '<div class="space-y-3">';
                                $total = 0;

                                foreach ($record->orderDetails as $detail) {
                                    // Lấy thông tin sản phẩm từ productVariant hoặc product
                                    $productName = 'Sản phẩm đã xóa';
                                    $price = 0;

                                    if ($detail->productVariant) {
                                        $productName = $detail->productVariant->product->name ?? 'Sản phẩm đã xóa';
                                        $price = $detail->productVariant->price ?? 0;

                                        // Thêm thông tin variant nếu có
                                        if ($detail->productVariant->color || $detail->productVariant->size) {
                                            $productName .= ' (';
                                            if ($detail->productVariant->color) $productName .= $detail->productVariant->color;
                                            if ($detail->productVariant->color && $detail->productVariant->size) $productName .= ' - ';
                                            if ($detail->productVariant->size) $productName .= $detail->productVariant->size;
                                            $productName .= ')';
                                        }
                                    } elseif ($detail->product) {
                                        $productName = $detail->product->name ?? 'Sản phẩm đã xóa';
                                        $price = $detail->product->price ?? 0;
                                    }

                                    $subtotal = $detail->quantity * $price;
                                    $total += $subtotal;

                                    $html .= '<div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">';
                                    $html .= '<div class="flex-1">';
                                    $html .= '<div class="font-medium text-gray-900">' . $productName . '</div>';
                                    $html .= '</div>';
                                    $html .= '<div class="text-right">';
                                    $html .= '<div class="text-sm text-gray-600">SL: ' . $detail->quantity . '</div>';
                                    $html .= '<div class="text-sm text-gray-600">Giá: ' . number_format($price, 0, ',', '.') . 'VND</div>';
                                    $html .= '</div>';
                                    $html .= '</div>';
                                }

                                $html .= '<div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg border-t-2 border-blue-200">';
                                $html .= '<div class="font-bold text-lg">Tổng cộng:</div>';
                                $html .= '<div class="font-bold text-lg text-blue-600">' . number_format($total, 0, ',', '.') . 'VND</div>';
                                $html .= '</div>';
                                $html .= '</div>';

                                return new \Illuminate\Support\HtmlString($html);
                            }),
                    ]),

                Section::make('Thông tin người nhận')
                    ->schema([
                        TextInput::make('recipient_name')
                            ->label('Tên người nhận')
                            ->disabled(),

                        TextInput::make('phone')
                            ->label('SĐT')
                            ->disabled(),

                        Textarea::make('address')
                            ->label('Địa chỉ')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc') // hoặc 'id', 'desc'
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Chi nhánh')
                    ->toggleable()
                    ->searchable()
                    ->sortable(),


                Tables\Columns\TextColumn::make('user.name')
                    ->label('Khách hàng')
                    ->formatStateUsing(fn($record) => $record->user?->name ?? 'Khách vãng lai')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Trạng thái')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'confirmed',
                        'info' => 'shipped',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'confirmed',
                        'heroicon-o-truck' => 'shipped',
                        'heroicon-o-check' => 'delivered',
                        'heroicon-o-x-circle' => 'cancelled',
                    ]),

                Tables\Columns\BadgeColumn::make('inventory_decreased')
                    ->label('Trừ kho')
                    ->getStateUsing(fn($record) => $record->inventory_decreased ? 'Đã trừ' : 'Chưa trừ')
                    ->colors([
                        'success' => 'Đã trừ',
                        'danger' => 'Chưa trừ',
                    ])
                    ->icons([
                        'heroicon-o-check' => 'Đã trừ',
                        'heroicon-o-x-circle' => 'Chưa trừ',
                    ]),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Thanh toán')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Tổng tiền')
                    ->money('VND')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'pending' => 'Chờ xác nhận',
                        'confirmed' => 'Đã xác nhận',
                        'shipped' => 'Đang giao hàng',
                        'delivered' => 'Đã giao hàng',
                        'cancelled' => 'Đã hủy',
                    ]),

                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Chi nhánh')
                    ->relationship('branch', 'name'),



                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Phương thức thanh toán')
                    ->options([
                        'cash' => 'Tiền mặt',
                        'vnpay' => 'VNPAY',
                        'momo' => 'MOMO',
                        'cod' => 'COD',
                    ]),

                Tables\Filters\Filter::make('inventory_decreased')
                    ->label('Đơn chưa trừ kho')
                    ->query(fn($query) => $query->where('inventory_decreased', false))
                    ->indicator('Chưa trừ kho'),
            ])

            ->actions([
                Tables\Actions\ViewAction::make(),

                // Thêm nút in PDF
                Tables\Actions\Action::make('print')
                    ->label('In PDF')
                    ->icon('heroicon-o-printer')
                    ->url(fn($record) => route('api.orders.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('Xác nhận đơn')
                    ->visible(fn($record) => $record->status === 'pending' && $record->payment_method === 'cod')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        OrderResource::updateOrderStatus($record, 'confirmed');
                    }),

                // Action bắt đầu giao hàng
                Tables\Actions\Action::make('Bắt đầu giao')
                    ->visible(fn($record) => $record->status === 'confirmed')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        OrderResource::updateOrderStatus($record, 'shipped');
                    }),

                // Action xác nhận đã giao
                Tables\Actions\Action::make('Xác nhận giao hàng')
                    ->visible(fn($record) => $record->status === 'shipped')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        OrderResource::updateOrderStatus($record, 'delivered');
                    }),

                // Action hủy đơn
                Tables\Actions\Action::make('Hủy đơn')
                    ->visible(fn($record) => in_array($record->status, ['pending', 'confirmed']))
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(function ($record) {
                        OrderResource::updateOrderStatus($record, 'cancelled', true);
                    }),
            ])

            ->bulkActions([
                Tables\Actions\BulkAction::make('decreaseInventory')
                    ->label('Trừ tồn kho cho đơn COD chọn')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $success = 0;
                        $failed = 0;

                        foreach ($records as $record) {
                            // Chỉ xử lý đơn COD chưa trừ kho
                            if ($record->inventory_decreased || $record->payment_method !== 'cod') {
                                continue;
                            }

                            if (\App\Services\InventoryService::decreaseOrderInventory($record)) {
                                $record->updateQuietly(['inventory_decreased' => true]);
                                $success++;
                            } else {
                                $failed++;
                            }
                        }

                        if ($success > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('Hoàn tất')
                                ->success()
                                ->body("Đã trừ tồn kho cho {$success} đơn. Thất bại: {$failed}.")
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Kết quả')
                                ->warning()
                                ->body("Không có đơn nào được trừ kho.")
                                ->send();
                        }
                    }),
            ]);
    }
    public static function updateOrderStatus($record, $newStatus, $isCancellation = false)
    {
        try {
            DB::beginTransaction();

            $oldStatus = $record->status;
            $record->status = $newStatus;
            $record->save();

            if ($newStatus === 'confirmed' && $record->payment_method === 'cod' && !$record->inventory_decreased) {
                if (!\App\Services\InventoryService::decreaseOrderInventory($record)) {
                    throw new \Exception('Không đủ tồn kho');
                }
                $record->updateQuietly(['inventory_decreased' => true]);
            }

            if ($isCancellation && $record->inventory_decreased) {
                \App\Services\InventoryService::increaseOrderInventory($record);
                $record->updateQuietly(['inventory_decreased' => false]);
            }

            DB::commit();

            Notification::make()
                ->title("Đã chuyển trạng thái từ " . self::getStatusText($oldStatus) . " đến " . self::getStatusText($newStatus))
                ->success()
                ->send();
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()
                ->title('Lỗi cập nhật trạng thái')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function getStatusText($status)
    {
        $statuses = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'shipped' => 'Đang giao hàng',
            'delivered' => 'Đã giao hàng',
            'cancelled' => 'Đã hủy',
        ];
        return $statuses[$status] ?? $status;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    public function generatePdf($id)
    {
        $order = Order::with(['user', 'orderDetails.product'])->findOrFail($id);

        $pdf = PDF::loadView('orders.pdf', [
            'order' => $order,
            'title' => 'Đơn hàng #' . $order->id
        ]);

        return $pdf->download('order_' . $order->id . '.pdf');
    }

    // Xem PDF trực tiếp trên trình duyệt
    public function viewPdf($id)
    {
        $order = Order::with(['user', 'orderDetails.product'])->findOrFail($id);

        $pdf = PDF::loadView('orders.pdf', [
            'order' => $order,
            'title' => 'Đơn hàng #' . $order->id
        ]);

        return $pdf->stream('order_' . $order->id . '.pdf');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
