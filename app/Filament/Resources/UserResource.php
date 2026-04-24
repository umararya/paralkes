<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Owner';

    protected static ?string $navigationLabel = 'Data Login';

    protected static ?string $modelLabel = 'Data Login';

    protected static ?int $navigationSort = 1;

    /**
     * Sembunyikan menu dari sidebar jika bukan owner.
     * Policy sudah memblokir akses URL, method ini merapikan tampilan sidebar.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->role === 'owner';
    }

    // =========================================================
    // FORM
    // =========================================================
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama')
                    ->label('Nama')
                    ->placeholder('Masukkan nama lengkap')
                    ->required()
                    ->maxLength(255),

                TextInput::make('username')
                    ->label('Username')
                    ->placeholder('Masukkan username')
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        table: 'users',
                        column: 'username',
                        ignoreRecord: true,
                    ),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->placeholder(
                        fn (string $operation) => $operation === 'create'
                            ? 'Masukkan password'
                            : 'Kosongkan jika tidak ingin mengubah password'
                    )
                    ->required(fn (string $operation) => $operation === 'create')
                    ->minLength(8)
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn (string $state) => bcrypt($state))
                    ->dehydrated(fn (?string $state) => filled($state)),

                Select::make('role')
                    ->label('Role')
                    ->options([
                        'owner' => 'Owner',
                        'admin' => 'Admin',
                    ])
                    ->required()
                    ->native(false),
            ]);
    }

    // =========================================================
    // TABLE
    // =========================================================
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('index')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width('60px'),

                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'owner'  => 'warning',
                        'admin'  => 'info',
                        default  => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),
            ])
            ->actions([
                EditAction::make()->label('Edit'),
                DeleteAction::make()->label('Hapus'),
            ])
            ->heading('Data Login')
            ->emptyStateHeading('Belum ada data login')
            ->emptyStateDescription('Klik tombol Input Data untuk menambahkan akun baru.');
    }

    // =========================================================
    // PAGES
    // =========================================================
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}