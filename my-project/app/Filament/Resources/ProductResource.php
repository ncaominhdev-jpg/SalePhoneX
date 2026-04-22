<?php

namespace App\Filament\Resources;

use App\Base\Filament\Forms\ProductForm;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\{Section, Grid, TextInput};
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Html;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Sản phẩm';
    protected static ?string $pluralModelLabel = 'Danh sách sản phẩm';
    protected static ?string $navigationGroup = 'Sản phẩm';

    public static function form(Form $form): Form
    {
        return $form->schema(ProductForm::make());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail_url')
                    ->label('Ảnh')->square()->height(70)
                    ->getStateUsing(function (Product $record) {
                        if (!empty($record->thumbnail_url)) return $record->thumbnail_url;
                        $thumb = $record->media()->where('is_thumbnail', true)->value('url');
                        if ($thumb) return $thumb;
                        return $record->media()->value('url');
                    }),

                TextColumn::make('name')->label('Tên sản phẩm')
                    ->limit(30)->weight('bold')->searchable()->sortable()->wrap(),

                TextColumn::make('category.name')->label('Danh mục')
                    ->badge()->color('info')->wrap()->sortable(),

                TextColumn::make('brand.name')->label('Thương hiệu')
                    ->badge()->color('warning')->wrap(),

                TextColumn::make('price')->label('Giá')->money('vnd')->sortable(),

                // ✅ Giữ cột trạng thái
                ToggleColumn::make('status')->label('Hiển thị'),

                // ✅ (Tuỳ chọn) Hiển thị số biến thể
                TextColumn::make('variants_count')->label('Biến thể')->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Danh mục')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('brands_id')
                    ->label('Thương hiệu')
                    ->relationship('brand', 'name'),

                TernaryFilter::make('status')->label('Trạng thái hiển thị'),
            ])
            ->actions([
                ViewAction::make()->label('Xem')->icon('heroicon-o-eye')->color('info'),
                EditAction::make()->label('Sửa'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfoSection::make('Thông tin chung')->schema([
                TextEntry::make('name')->label('Tên sản phẩm')->weight('bold')->size('lg'),
                TextEntry::make('price')->label('Giá')->money('vnd'),
                TextEntry::make('category.name')->label('Danh mục')->badge(),
                TextEntry::make('brand.name')->label('Thương hiệu')->badge(),
                TextEntry::make('status')->label('Trạng thái')->badge()
                    ->color(fn($state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn(bool $state): string => $state ? 'Hiển thị' : 'Ẩn'),
            ])->columns(2),

            // ===== Thư viện ảnh (đẹp & gọn) =====
            InfoSection::make('Thư viện ảnh')
                ->schema([
                    TextEntry::make('media_gallery')
                        ->label(false)
                        ->state(function ($record) {
                            $media = $record->media ?? collect();
                            if ($media->isEmpty()) {
                                return '<div class="text-sm text-gray-500">Chưa có ảnh.</div>';
                            }

                            // Chuẩn hoá URL tuyệt đối
                            $toAbsolute = function ($raw) {
                                $raw = (string) $raw;

                                if (\Illuminate\Support\Str::startsWith($raw, ['http://', 'https://', '//'])) {
                                    return $raw;
                                }
                                if (\Illuminate\Support\Str::startsWith($raw, ['/'])) {
                                    return url($raw);
                                }
                                if (\Illuminate\Support\Str::startsWith($raw, [
                                    'storage/',
                                    'uploads/',
                                    'images/',
                                    'img/',
                                    'media/',
                                    'photos/',
                                    'files/',
                                ])) {
                                    return asset($raw);
                                }
                                try {
                                    return \Illuminate\Support\Facades\Storage::disk('public')->url($raw);
                                } catch (\Throwable $e) {
                                    return url('/' . ltrim($raw, '/'));
                                }
                            };

                            // Grid ảnh
                            $html = '<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-4">';
                            foreach ($media as $m) {
                                $raw = $m->url ?? $m->path ?? $m['url'] ?? $m['path'] ?? '';
                                if (!$raw) continue;

                                $url = e($toAbsolute($raw));
                                $alt = e($m->alt ?? $record->name ?? 'Ảnh sản phẩm');

                                $html .= '
                    <a href="' . $url . '" target="_blank" rel="noopener"
                       class="group block rounded-xl overflow-hidden border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-950 shadow-sm hover:shadow-md transition-all">
                        <figure class="w-full overflow-hidden bg-gray-50 dark:bg-gray-900/40" style="aspect-ratio:4/3">
                            <img src="' . $url . '" alt="' . $alt . '" loading="lazy" referrerpolicy="no-referrer"
                                 class="block w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                 onerror="this.style.display=`none`; this.parentElement.classList.add(`bg-gray-200`);" />
                        </figure>
                        <figcaption class="px-3 py-2 border-t border-gray-100 dark:border-gray-800">
                            <span class="text-xs text-gray-500 line-clamp-1">' . ($m->alt ?? 'Ảnh sản phẩm') . '</span>
                        </figcaption>
                    </a>';
                            }
                            $html .= '</div>';

                            return $html;
                        })
                        ->html(),
                ])
                ->collapsible()
                ->visible(fn($record) => $record->media && $record->media->isNotEmpty()),





            // ===== Thông số kỹ thuật =====
            // === Thông số & Biến thể (đẹp hơn) ===
            InfoSection::make('Thông số & Biến thể')
                ->schema([
                    // CỘT TRÁI: Thông số kỹ thuật
                    TextEntry::make('specs_table')
                        ->label('Thông số kỹ thuật')
                        ->state(function ($record) {
                            $html = '<div class="rounded-lg border bg-white shadow-sm overflow-hidden">';
                            $html .= '<table class="w-full border-collapse">';
                            foreach ($record->attributes as $attr) {
                                $html .= '
                        <tr class="odd:bg-white even:bg-gray-50">
                            <td class="border border-gray-200 px-3 py-2 font-medium text-gray-700 w-48">'
                                    . e($attr->name) . '</td>
                            <td class="border border-gray-200 px-3 py-2 text-gray-800">'
                                    . e($attr->pivot->value) . '</td>
                        </tr>';
                            }
                            $html .= '</table></div>';
                            return $html;
                        })
                        ->html()
                        ->columnSpan(5),

                    // CỘT PHẢI: Danh sách biến thể
                    RepeatableEntry::make('variants')
                        ->label('Biến thể sản phẩm')
                        ->extraAttributes(['class' => 'space-y-4'])
                        ->schema([
                            \Filament\Infolists\Components\Section::make()
                                ->schema([
                                    \Filament\Infolists\Components\Grid::make([
                                        'default' => 1,
                                        'sm' => 12,
                                    ])->schema([
                                        // Ảnh
                                        ImageEntry::make('image_url')
                                            ->label(false)
                                            ->height(120)
                                            ->extraAttributes([
                                                'class' =>
                                                'rounded-lg bg-gray-50 dark:bg-gray-900/40 p-2 border ' .
                                                    'border-gray-100 dark:border-gray-800 mx-auto'
                                            ])
                                            ->columnSpan([
                                                'sm' => 3,
                                            ])
                                            ->visible(fn($state) => filled($state)),

                                        // Nội dung
                                        \Filament\Infolists\Components\Group::make([
                                            // Hàng tiêu đề: Tên + Trạng thái
                                            \Filament\Infolists\Components\Grid::make(12)->schema([
                                                TextEntry::make('display_name')
                                                    ->label(false)
                                                    ->weight('medium')
                                                    ->size('lg')
                                                    ->extraAttributes(['class' => 'text-gray-900 dark:text-gray-100'])
                                                    ->columnSpan(8),

                                                TextEntry::make('status')
                                                    ->label(false)
                                                    ->badge()
                                                    ->alignRight()
                                                    ->color(fn(bool $state) => $state ? 'success' : 'danger')
                                                    ->formatStateUsing(fn(bool $state): string => $state ? 'Đang bán' : 'Ngừng bán')
                                                    ->columnSpan(4),
                                            ]),

                                            // Thuộc tính
                                            TextEntry::make('options_string')
                                                ->label('Thuộc tính')
                                                ->placeholder('—')
                                                ->extraAttributes(['class' => 'text-gray-600 dark:text-gray-400 mt-1 mb-3']),

                                            // ====== GIÁ (đã làm gọn & thẳng hàng) ======
                                            \Filament\Infolists\Components\Grid::make([
                                                'default' => 1,
                                                'md' => 12,
                                            ])
                                                ->extraAttributes(['class' => 'mt-3 pt-3 border-t border-dashed'])
                                                ->schema([
                                                    // Giá
                                                    \Filament\Infolists\Components\Group::make([
                                                        TextEntry::make('price_label')
                                                            ->label(false)
                                                            ->state('Giá')
                                                            ->extraAttributes(['class' => 'text-xs uppercase tracking-wide text-gray-500']),
                                                        TextEntry::make('price')
                                                            ->label(false)
                                                            ->money('vnd')
                                                            ->weight('medium')
                                                            ->extraAttributes(['class' => 'tabular-nums text-gray-900 dark:text-gray-100']),
                                                    ])
                                                        ->extraAttributes(['class' => 'space-y-1 text-center'])
                                                        ->columnSpan(['md' => 4]),

                                                    // Giảm
                                                    \Filament\Infolists\Components\Group::make([
                                                        TextEntry::make('discount_label')
                                                            ->label(false)
                                                            ->state('Giảm')
                                                            ->extraAttributes(['class' => 'text-xs uppercase tracking-wide text-gray-500']),
                                                        TextEntry::make('discount')
                                                            ->label(false)
                                                            ->money('vnd')
                                                            ->placeholder('—')
                                                            ->extraAttributes(['class' => 'tabular-nums text-gray-900 dark:text-gray-100']),
                                                    ])
                                                        ->extraAttributes(['class' => 'space-y-1 text-center md:border-l md:border-gray-100 md:dark:border-gray-800'])
                                                        ->columnSpan(['md' => 4]),

                                                    // Giá sau giảm
                                                    \Filament\Infolists\Components\Group::make([
                                                        TextEntry::make('price_final_label')
                                                            ->label(false)
                                                            ->state('Giá sau giảm')
                                                            ->extraAttributes(['class' => 'text-xs uppercase tracking-wide text-gray-500']),
                                                        TextEntry::make('price_final')
                                                            ->label(false)
                                                            ->money('vnd')
                                                            ->weight('semibold')
                                                            ->extraAttributes(['class' => 'tabular-nums text-green-600 dark:text-green-400']),
                                                    ])
                                                        ->extraAttributes(['class' => 'space-y-1 text-center md:border-l md:border-gray-100 md:dark:border-gray-800'])
                                                        ->columnSpan(['md' => 4]),
                                                ]),
                                            // ====== /GIÁ ======
                                        ])->columnSpan([
                                            'sm' => 9,
                                        ]),
                                    ]),
                                ])
                                ->extraAttributes([
                                    'class' =>
                                    'rounded-xl border border-gray-100 dark:border-gray-800 ' .
                                        'bg-white dark:bg-gray-950 p-4 sm:p-5 shadow-sm ' .
                                        'hover:shadow-md transition-shadow',
                                ])
                                ->columnSpanFull(),
                        ])

                        ->columns(1) // mỗi item là 1 card full width
                        ->columnSpan(7),
                ])
                ->columns(12)
                ->visible(
                    fn($record) => ($record->attributes && $record->attributes->isNotEmpty())
                        || ($record->variants && $record->variants->isNotEmpty())
                )
                ->collapsible(),
        ]);
    }


    // Eager-load để giảm N+1
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'category',
                'brand',
                'media',
                'attributes',
                'variants', // ✅ GIỮ
            ])
            ->withCount('variants'); // sản phẩm mới nhất lên đầu + badge đếm biến thể
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'view' => Pages\ViewProduct::route('/{record}'),
        ];
    }
}
