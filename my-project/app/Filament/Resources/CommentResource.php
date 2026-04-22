<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentResource\Pages;
use App\Filament\Resources\CommentResource\RelationManagers\RepliesRelationManager;
use App\Models\Comment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';
    protected static ?string $navigationLabel = 'Bình luận';
    protected static ?string $pluralModelLabel = 'Danh sách bình luận';
    protected static ?string $modelLabel = 'Bình luận';
    protected static ?string $navigationGroup = 'Tương tác và Marketing';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', true)
            // ✨ Thêm điều kiện: chỉ lấy comment của user có role là 'user'
            ->whereHas('user', function (Builder $query) {
                $query->where('role', 'user');
            })
            ->count();
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()->schema([
                    Forms\Components\Select::make('user_id')->relationship('user', 'name')->searchable()->preload()->required(),
                    Forms\Components\Select::make('product_id')->relationship('product', 'name')->searchable()->preload()->required(),
                    Forms\Components\Select::make('parent_id')->label('Trả lời cho bình luận')->relationship('parent', 'content')->searchable()->preload(),
                    Forms\Components\Textarea::make('content')->label('Nội dung')->required()->columnSpanFull(),
                    Forms\Components\Toggle::make('status')->label('Đã duyệt')->default(true),
                ])->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Group::make()->schema([
                    Infolists\Components\Section::make('Thông tin người gửi')->schema([
                        Infolists\Components\TextEntry::make('user.name')->label('Tên người dùng')->weight('bold'),
                        Infolists\Components\TextEntry::make('user.email')->label('Email'),
                    ])->columns(2),
                    Infolists\Components\Section::make('Nội dung bình luận')->schema([
                        Infolists\Components\TextEntry::make('content')->label(false)->markdown(),
                    ]),
                ])->columnSpan(['lg' => 2]),

                Infolists\Components\Group::make()->schema([
                    Infolists\Components\Section::make('Thông tin chung')->schema([
                        Infolists\Components\TextEntry::make('product.name')->label('Bình luận cho sản phẩm'),
                        Infolists\Components\TextEntry::make('parent.content')->label('Trả lời cho bình luận')->visible(fn ($record) => $record->parent_id),
                        Infolists\Components\TextEntry::make('created_at')->label('Ngày gửi')->dateTime('H:i:s d/m/Y'),
                    ]),
                ])->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

public static function table(Table $table): Table
{
    return $table
        ->defaultSort('created_at', 'desc')
        ->columns([
            Tables\Columns\TextColumn::make('user.name')
                ->label('Người dùng')
                ->searchable()
                ->sortable()
                ->width('15%'), // Đặt chiều rộng 20%

            Tables\Columns\TextColumn::make('product.name')
                ->label('Sản phẩm')
                ->limit(25)
                ->searchable()
                ->width('15%'), // Đặt chiều rộng 20%

            Tables\Columns\TextColumn::make('content')
                ->label('Nội dung')
                ->limit(40)
                ->wrap()
                ->tooltip(fn(Comment $record) => $record->content),
                // Cột này sẽ tự động lấp đầy không gian còn lại

            Tables\Columns\IconColumn::make('parent_id')
                ->label('Trả lời')
                ->boolean()
                ->trueIcon('heroicon-o-arrow-uturn-left')
                ->width('60px')->width('15%'), // Đặt chiều rộng cố định cho icon

            Tables\Columns\TextColumn::make('created_at')
                ->label('Ngày gửi')
                ->dateTime('d/m/Y')
                ->sortable()
                ->width('15%'), // Đặt chiều rộng 20%
        ])
        ->filters([
            Tables\Filters\Filter::make('is_toplevel')
                ->label('Chỉ bình luận gốc')
                ->query(fn (Builder $query): Builder => $query->whereNull('parent_id')),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
        ]);
}
    public static function getRelations(): array
    {
        return [
            RepliesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComments::route('/'),
            'create' => Pages\CreateComment::route('/create'),
            'view' => Pages\ViewComment::route('/{record}'),
            'edit' => Pages\EditComment::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'manager']);
    }

    public static function canEdit(Model $record): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'manager']);
    }

    public static function canDelete(Model $record): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'manager']);
    }
    
    public static function canDeleteAny(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'manager']);
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('user', function (Builder $query) {
            $query->where('role', 'user');
        });
    }
}