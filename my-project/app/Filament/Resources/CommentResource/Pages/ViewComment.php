<?php

namespace App\Filament\Resources\CommentResource\Pages;

use App\Filament\Resources\CommentResource;
use App\Models\Comment;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth; // Sử dụng Facade Auth để linter dễ nhận diện hơn
use App\Models\User;
class ViewComment extends ViewRecord
{
    protected static string $resource = CommentResource::class;

    /**
     * Tùy chỉnh các nút hành động ở góc trên bên phải
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reply')
                ->label('Trả lời bình luận')
                ->icon('heroicon-m-arrow-uturn-left')
                ->color('info')
                // Chỉ admin và manager mới thấy nút này
                // Sử dụng Auth::user() để linter nhận diện tốt hơn
                ->visible(fn () => optional(Auth::user())->hasRole(['admin', 'manager']))
                // Form trả lời trong modal
                ->form([
                    Forms\Components\Textarea::make('content')
                        ->label('Nội dung trả lời')
                        ->required()
                        ->rows(5),
                ])
                // Xử lý khi nhấn nút gửi trả lời
                ->action(function (array $data) {
                    // Tạo một bình luận mới
                    Comment::create([
                        'product_id' => $this->record->product_id,
                        // Sử dụng Auth::id() để linter nhận diện tốt hơn
                        'user_id' => Auth::id(),
                        'parent_id' => $this->record->id,
                        'content' => $data['content'],
                        'status' => true, // Tự động duyệt
                    ]);

                    Notification::make()
                        ->title('Đã gửi trả lời thành công')
                        ->success()
                        ->send();

                        return redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }
}