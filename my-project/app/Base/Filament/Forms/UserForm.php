<?php

namespace App\Base\Filament\Forms;

use App\Models\Branch;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Validator;
use Filament\Forms\Components\FileUpload; // Import FileUpload component
use Filament\Forms\Components\Textarea; // Import Textarea component
use Filament\Forms\Components\Grid; // Import Grid component

class UserForm
{
    public static function make(Form $form): Form
    {
        return $form
            ->reactive()
            ->schema([
                // Main section: User information (including avatar and personal/account info)
                Section::make('Thông tin người dùng')
                    ->description('Cung cấp ảnh đại diện, thông tin cơ bản, vai trò và nhận dạng của người dùng.')
                    ->columns(false) // Disable Section's column layout, will use Grid inside
                    ->compact() // Make this section more compact
                    ->schema([
                        Grid::make(4) // NEW: Create a main Grid with 4 columns
                            ->schema([
                                // Avatar - Located on the left, takes 1 column and will "push" other content to the right
                                FileUpload::make('avatar')
                                    ->label('Ảnh đại diện')
                                    ->directory('user-avatars')
                                    ->image()
                                    ->maxSize(2048)
                                    ->enableDownload()
                                    ->enableOpen()
                                    ->columnSpan(1)
                                    ->rules(['nullable', 'image', 'max:2048'])
                                    ->validationMessages([
                                        'image' => 'Tệp tải lên phải là ảnh.',
                                        'max' => 'Ảnh không được vượt quá :max KB.',
                                    ]), // Takes 1 column in this 4-column Grid

                                // Right Grid for the remaining 2 rows of 6 fields
                                Grid::make(3) // NEW: Create a nested Grid with 3 columns
                                    ->columnSpan(3) // NEW: This Grid takes the remaining 3 columns of the main Grid (4-1=3)
                                    ->schema([
                                        // Row 1 (right of avatar): Full Name, Email, Phone
                                        TextInput::make('name')
                                            ->label('Họ và tên')
                                            ->placeholder('Nhập họ và tên')
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->rules(fn($livewire, $record) => [
                                                'required',
                                                'string',
                                                'max:255',
                                                Rule::unique('users', 'name')->ignore($record),
                                            ])
                                            ->validationMessages([
                                                'required' => 'Vui lòng nhập họ tên.',
                                                'unique' => 'Tên này đã tồn tại.',
                                                'max' => 'Họ tên không được vượt quá :max ký tự.',
                                            ]),

                                        TextInput::make('email')
                                            ->label('Email')
                                            ->placeholder('Nhập địa chỉ email')
                                            ->email()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->rules(fn($livewire, $record) => [
                                                'required',
                                                'email',
                                                'max:255',
                                                Rule::unique('users', 'email')->ignore($record),
                                            ])
                                            ->validationMessages([
                                                'required' => 'Vui lòng nhập email.',
                                                'email' => 'Email không đúng định dạng.',
                                                'unique' => 'Email này đã tồn tại.',
                                                'max' => 'Email không được vượt quá :max ký tự.',
                                            ]),

                                        TextInput::make('phone')
                                            ->label('Số điện thoại')
                                            ->placeholder('VD: 09xxxxxxxx')
                                            ->tel()
                                            ->maxLength(50)
                                            ->live(onBlur: true)
                                            ->rules(fn($livewire, $record) => [
                                                'required',
                                                'regex:/^0[0-9]{9,10}$/',
                                                'max:50',
                                                Rule::unique('users', 'phone')->ignore($record),
                                            ])
                                            ->validationMessages([
                                                'required' => 'Vui lòng nhập số điện thoại.',
                                                'regex' => 'Số điện thoại không hợp lệ.',
                                                'unique' => 'Số điện thoại này đã được sử dụng.',
                                                'max' => 'Số điện thoại không được vượt quá :max ký tự.',
                                            ]),

                                        // Row 2 (right of avatar): Role, Citizen ID, Branch
                                        Select::make('role')
                                            ->label('Vai trò')
                                            ->placeholder('Chọn vai trò')
                                            ->options(fn() => match (Auth::user()?->role) {
                                                'admin' => [
                                                    'admin' => 'Quản trị hệ thống',
                                                    'manager' => 'Quản lý chi nhánh',
                                                    'staff' => 'Nhân viên chi nhánh',
                                                    'user' => 'Người dùng thường',
                                                ],
                                                'manager' => [
                                                    'staff' => 'Nhân viên chi nhánh',
                                                ],
                                                default => [],
                                            })
                                            ->default('staff')
                                            ->rules(['required', Rule::in(['admin', 'manager', 'staff', 'user'])])
                                            ->hidden(fn() => !in_array(Auth::user()?->role, ['admin', 'manager'])),

                                        TextInput::make('citizen_identity_card')
                                            ->label('Căn cước công dân (CCCD)')
                                            ->placeholder('Nhập số CCCD (9 hoặc 12 số)')
                                            ->maxLength(12)
                                            ->minLength(9)
                                            ->unique(ignoreRecord: true, table: 'users', column: 'citizen_identity_card')
                                            ->rules(['nullable', 'string', 'max:12', 'min:9', 'regex:/^[0-9]{9,12}$/'])
                                            ->validationMessages([
                                                'unique' => 'Số CCCD này đã tồn tại trong hệ thống.',
                                                'max' => 'Số CCCD không được vượt quá :max ký tự.',
                                                'min' => 'Số CCCD phải có ít nhất :min ký tự.',
                                                'regex' => 'Số CCCD chỉ được chứa các chữ số và có độ dài từ 9 đến 12.',
                                            ]),

                                        Select::make('branch_id')
                                            ->label('Chi nhánh')
                                            ->placeholder('Chọn chi nhánh')
                                            ->relationship(name: 'branch', titleAttribute: 'name')
                                            ->searchable()
                                            ->preload()
                                            ->rules(['required', 'exists:branches,id'])
                                            ->suffixAction(
                                                Action::make('createBranch')
                                                    ->icon('heroicon-m-plus')
                                                    ->tooltip('Thêm chi nhánh mới')
                                                    ->modalHeading('Thêm chi nhánh mới')
                                                    ->modalSubmitActionLabel('Lưu chi nhánh')
                                                    ->modalWidth('lg')
                                                    ->form([
                                                        Section::make('')
                                                            ->columns(2)
                                                            ->schema([
                                                                TextInput::make('name')
                                                                    ->label('Tên chi nhánh')
                                                                    ->rules(['required', 'string', 'max:255', Rule::unique('branches', 'name')]),

                                                                TextInput::make('email')
                                                                    ->label('Email')
                                                                    ->email()
                                                                    ->rules(['required', 'email', 'max:255', Rule::unique('branches', 'email')]),

                                                                TextInput::make('phone')
                                                                    ->label('Số điện thoại')
                                                                    ->tel()
                                                                    ->rules(['required', 'regex:/^0[0-9]{9,10}$/', 'max:50', Rule::unique('branches', 'phone')]),

                                                                Select::make('type')
                                                                    ->label('Loại chi nhánh')
                                                                    ->options([
                                                                        'tong' => 'Trụ sở chính',
                                                                        'chi_nhanh' => 'Chi nhánh',
                                                                    ])
                                                                    ->rules(['required', Rule::in(['tong', 'chi_nhanh'])])
                                                                    ->default('chi_nhanh'),

                                                                Select::make('province_code')
                                                                    ->label('Tỉnh / Thành phố')
                                                                    ->options(function () {
                                                                        try {
                                                                            $response = Http::withOptions(['verify' => false])->get('https://34tinhthanh.com/api/provinces');
                                                                            if ($response->successful()) {
                                                                                return collect($response->json())->pluck('name', 'province_code')->toArray();
                                                                            }
                                                                        } catch (\Exception $e) {
                                                                            Notification::make()
                                                                                ->title('Lỗi tải dữ liệu')
                                                                                ->body('Không thể tải danh sách tỉnh/thành phố cho chi nhánh. Vui lòng thử lại.')
                                                                                ->danger()
                                                                                ->send();
                                                                        }
                                                                        return [];
                                                                    })
                                                                    ->reactive()
                                                                    ->afterStateUpdated(fn(Set $set) => $set('ward_code', null))
                                                                    ->required(),

                                                                Select::make('ward_code')
                                                                    ->label('Phường / Xã')
                                                                    ->placeholder('Chọn phường / xã')
                                                                    ->options(function (Get $get) {
                                                                        $provinceCode = $get('province_code');
                                                                        if (!$provinceCode)
                                                                            return [];
                                                                        try {
                                                                            $response = Http::withOptions(['verify' => false])->get('https://34tinhthanh.com/api/wards?province_code=' . $provinceCode);
                                                                            if ($response->successful()) {
                                                                                return collect($response->json())->pluck('ward_name', 'ward_code')->toArray();
                                                                            }
                                                                        } catch (\Exception $e) {
                                                                            Notification::make()
                                                                                ->title('Lỗi tải dữ liệu')
                                                                                ->body('Không thể tải danh sách phường/xã cho chi nhánh. Vui lòng thử lại.')
                                                                                ->danger()
                                                                                ->send();
                                                                        }
                                                                        return [];
                                                                    })
                                                                    ->reactive()
                                                                    ->required(),

                                                                TextInput::make('address')
                                                                    ->label('Địa chỉ cụ thể')
                                                                    ->columnSpanFull()
                                                                    ->rules(['required', 'string', 'max:255'])
                                                                    ->rules(['nullable', 'string', 'max:255'])
                                                                    ->validationMessages([
                                                                        'string' => 'Địa chỉ phải là một chuỗi ký tự.',
                                                                        'max' => 'Địa chỉ không được vượt quá :max ký tự.',
                                                                    ]),
                                                            ]),
                                                    ])
                                                    ->action(function (array $data, Set $set) {
                                                        $validator = Validator::make($data, [
                                                            'name' => ['required', 'string', 'max:255', Rule::unique('branches', 'name')],
                                                            'email' => ['required', 'email', 'max:255', Rule::unique('branches', 'email')],
                                                            'phone' => ['required', 'regex:/^0[0-9]{9,10}$/', 'max:50', Rule::unique('branches', 'phone')],
                                                            'type' => ['required', Rule::in(['tong', 'chi_nhanh'])],
                                                            'province_code' => ['required', 'string'],
                                                            'ward_code' => ['required', 'string'],
                                                            'address' => ['required', 'string', 'max:255'],
                                                        ]);

                                                        if ($validator->fails()) {
                                                            throw ValidationException::withMessages($validator->errors()->toArray());
                                                        }

                                                        try {
                                                            $provinceName = Http::withoutVerifying()->get('https://34tinhthanh.com/api/provinces')->collect()
                                                                ->firstWhere('province_code', $data['province_code'])['name'] ?? '';

                                                            $wardResponse = Http::withoutVerifying()->get('https://34tinhthanh.com/api/wards?province_code=' . $data['province_code']);
                                                            $wardName = collect($wardResponse->json())->firstWhere('ward_code', $data['ward_code'])['ward_name'] ?? '';
                                                        } catch (\Exception $e) {
                                                            Notification::make()
                                                                ->title('Lỗi')
                                                                ->danger()
                                                                ->body('Không thể lấy thông tin địa chỉ cho chi nhánh. Vui lòng thử lại.')
                                                                ->send();
                                                            return;
                                                        }

                                                        $branch = Branch::create([
                                                            'name' => $data['name'],
                                                            'email' => $data['email'],
                                                            'phone' => $data['phone'],
                                                            'type' => $data['type'],
                                                            'status' => $data['status'] ?? true, // Default to true if not in form
                                                            'province_code' => $data['province_code'],
                                                            'province_name' => $provinceName,
                                                            'ward_code' => $data['ward_code'],
                                                            'ward_name' => $wardName,
                                                            'address' => $data['address'],
                                                        ]);

                                                        $set('branch_id', $branch->id);

                                                        Notification::make()
                                                            ->title('Thành công')
                                                            ->success()
                                                            ->body(new HtmlString("Chi nhánh <strong>{$branch->name}</strong> đã được tạo thành công."))
                                                            ->send();
                                                    })
                                            ),
                                    ]),
                            ]), // End of main Grid
                    ]), // End of main Section

                // NEW SECTION: Combined Address and Password Information
                Section::make('Địa chỉ & Mật khẩu') // Renamed section
                    ->description('Cập nhật địa chỉ và mật khẩu người dùng.')
                    ->columns(2) // Divide this section into 2 large columns: 1 for address, 1 for password
                    ->compact() // Make this section more compact
                    ->schema([
                        // Left Column: Address
                        Grid::make(2) // Nested Grid for address fields (Province/City & Ward/Commune on 1 row)
                            ->columnSpan(1) // Takes 1 column of the parent Section
                            ->schema([
                                Select::make('province_code')
                                    ->label('Tỉnh / Thành phố')
                                    ->placeholder('Chọn tỉnh / TP')
                                    ->options(function () {
                                        try {
                                            $response = Http::withOptions(['verify' => false])->get('https://34tinhthanh.com/api/provinces');
                                            if ($response->successful()) {
                                                return collect($response->json())->pluck('name', 'province_code')->toArray();
                                            }
                                        } catch (\Exception $e) {
                                            Notification::make()
                                                ->title('Lỗi tải dữ liệu')
                                                ->body('Không thể tải danh sách tỉnh/thành phố. Vui lòng kiểm tra kết nối mạng hoặc thử lại sau.')
                                                ->danger()
                                                ->send();
                                        }
                                        return [];
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(fn(Set $set) => $set('ward_code', null))
                                    ->searchable()
                                    ->rules(['nullable', 'string']),

                                Select::make('ward_code')
                                    ->label('Phường / Xã')
                                    ->placeholder('Chọn phường / xã')
                                    ->options(function (Get $get) {
                                        $provinceCode = $get('province_code');
                                        if (!$provinceCode) {
                                            return [];
                                        }
                                        try {
                                            $response = Http::withOptions(['verify' => false])->get('https://34tinhthanh.com/api/wards?province_code=' . $provinceCode);
                                            if ($response->successful()) {
                                                return collect($response->json())->pluck('ward_name', 'ward_code')->toArray();
                                            }
                                        } catch (\Exception $e) {
                                            Notification::make()
                                                ->title('Lỗi tải dữ liệu')
                                                ->body('Không thể tải danh sách phường/xã. Vui lòng kiểm tra kết nối mạng hoặc thử lại sau.')
                                                ->danger()
                                                ->send();
                                        }
                                        return [];
                                    })
                                    ->reactive()
                                    ->searchable()
                                    ->rules(['nullable', 'string']),

                                Textarea::make('address')
                                    ->label('Địa chỉ cụ thể')
                                    ->placeholder('Nhập số nhà, tên đường, khu phố, thôn/xóm...')
                                    ->maxLength(255)
                                    ->rows(3)
                                    ->columnSpanFull() // Takes full width of this address Grid
                                    ->rules(['nullable', 'string', 'max:255']),
                            ]),

                        // Right Column: Password
                        Grid::make(2) // Nested Grid for password fields
                            ->columnSpan(1) // Takes 1 column of the parent Section
                            ->schema([
                                TextInput::make('password')
                                    ->label('Mật khẩu')
                                    ->password()
                                    ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
                                    ->dehydrated(fn($state) => filled($state))
                                    ->rules(function ($livewire, $state) {
                                        if ($livewire instanceof \Filament\Resources\Pages\CreateRecord) {
                                            return ['required', 'string', 'min:8', 'confirmed'];
                                        }
                                        return ['nullable', 'string', 'min:8', 'confirmed'];
                                    })
                                    ->validationMessages([
                                        'required' => 'Vui lòng nhập mật khẩu.',
                                        'min' => 'Mật khẩu phải có ít nhất :min ký tự.',
                                        'confirmed' => 'Mật khẩu xác nhận không khớp.',
                                    ]),

                                TextInput::make('password_confirmation')
                                    ->label('Xác nhận mật khẩu')
                                    ->password()
                                    ->dehydrated(false)
                                    ->rules(function ($livewire, Get $get) {
                                        return ['required_with:password', 'string', 'min:8'];
                                    })
                                    ->validationMessages([
                                        'required_with' => 'Vui lòng xác nhận mật khẩu.',
                                        'min' => 'Mật khẩu xác nhận phải có ít nhất :min ký tự.',
                                    ]),
                            ]),
                    ]), // End of Địa chỉ & Mật khẩu Section
            ]);
    }
}
