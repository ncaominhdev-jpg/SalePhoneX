<?php

namespace App\Base\Filament\Forms;

use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use App\Models\Inventory;
use Illuminate\Support\Collection;

class AuditForm
{
    public static function make(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2) // Đặt lưới chính là 2 cột
                    ->schema([
                        // Cột 1: Chứa Kho kiểm và Ghi chú
                        Grid::make(1) // Tạo một lưới con 1 cột để xếp Kho kiểm và Ghi chú dọc
                            ->schema([
                                Select::make('warehouse_id')
                                    ->relationship('warehouse', 'name')
                                    ->label('Kho kiểm')
                                    ->required()
                                    ->native(false)
                                    ->reactive()
                                    ->afterStateUpdated(fn(callable $set) => $set('reports', []))
                                    ->columnSpan(1), // Chiếm 1 cột của lưới con
                                DatePicker::make('created_at')
                                    ->label('Ngày tạo')
                                    ->default(now('Asia/Ho_Chi_Minh'))
                                    ->required()
                                    ->displayFormat('d/m/Y')
                                    ->native(false)
                                    ->minDate(now('Asia/Ho_Chi_Minh')->startOfDay())
                                    ->columnSpan(1), // Chiếm 1 cột của lưới chính

                            ])
                            ->columnSpan(1), // Lưới con này chiếm 1 cột của lưới chính

                        // Cột 2: Chỉ chứa Ngày tạo
                        Textarea::make('note')
                            ->label('Ghi chú')
                            ->maxLength(1000)
                            // Điều chỉnh rows để chiều cao của nó phù hợp với Select + DatePicker
                            // Thường Select và DatePicker mỗi cái chiếm khoảng 2-3 dòng,
                            // vậy tổng cộng khoảng 4-6 dòng cho Textarea là hợp lý.
                            ->rows(4) // Thử với 4-6 dòng, điều chỉnh cho phù hợp với giao diện thực tế
                            ->columnSpan(1), // Chiếm 1 cột của lưới con
                    ]),

                // Phần Repeater giữ nguyên như trước
                Section::make('Chi tiết kiểm kho')
                    ->schema([
                        Repeater::make('reports')
                            ->relationship('reports')
                            ->minItems(1)
                            ->disableLabel()
                            ->required()
                            ->visible(fn(callable $get) => !empty($get('warehouse_id')))
                            ->columns(7)
                            ->schema([
                                Select::make('product_variant_id')
                                    ->label('Sản phẩm')
                                    ->required()
                                    ->native(false)
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->reactive()
                                    ->live()
                                    ->options(function (callable $get) {
                                        $warehouseId = $get('../../warehouse_id');
                                        if (!$warehouseId) {
                                            return [];
                                        }

                                        return Inventory::where('warehouse_id', $warehouseId)
                                            ->with('productVariant')
                                            ->get()
                                            ->mapWithKeys(fn($inv) => [
                                                $inv->product_variant_id => optional($inv->productVariant)->name ?? '',
                                            ])
                                            ->filter(fn($name) => !empty($name))
                                            ->toArray();
                                    })
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $warehouseId = $get('../../warehouse_id');
                                        if (!$warehouseId || !$state) {
                                            $set('recorded_quantity', 0);
                                            $set('actual_quantity', 0);
                                            return;
                                        }
                                        $quantity = Inventory::where('warehouse_id', $warehouseId)
                                            ->where('product_variant_id', $state)
                                            ->value('quantity') ?? 0;
                                        $set('recorded_quantity', $quantity);
                                        $set('actual_quantity', $quantity);
                                    })
                                    ->columnSpan(5),

                                TextInput::make('recorded_quantity')
                                    ->label('Tồn kho')
                                    ->numeric()
                                    ->required()
                                    ->readonly()
                                    ->columnSpan(1),

                                TextInput::make('actual_quantity')
                                    ->label('Thực tế')
                                    ->numeric()
                                    ->required()
                                    ->columnSpan(1),
                            ]),
                    ]),
            ]);
    }
}