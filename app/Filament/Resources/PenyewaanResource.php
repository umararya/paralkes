<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenyewaanResource\Pages;
use App\Models\Penyewaan;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class PenyewaanResource extends Resource
{
    protected static ?string $model = Penyewaan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Admin';

    protected static ?string $navigationLabel = 'Penyewaan';

    protected static ?string $modelLabel = 'Penyewaan';

    protected static ?int $navigationSort = 1;

    // =========================================================
    // HELPER — hitung durasi dari dua tanggal
    // =========================================================
    private static function hitungDurasi(?string $mulai, ?string $selesai): string
    {
        if (! $mulai || ! $selesai) {
            return '';
        }

        $start = Carbon::parse($mulai);
        $end   = Carbon::parse($selesai);

        if ($end->lt($start)) {
            return '';
        }

        $hari = $start->diffInDays($end);

        return $hari . ' hari';
    }

    // =========================================================
    // FORM
    // =========================================================
    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                // ── SECTION 1: Identitas Penyewa ─────────────────────────
                Section::make('Identitas Penyewa')
                    ->icon('heroicon-o-user')
                    ->schema([

                        TextInput::make('nama')
                            ->label('Nama Lengkap')
                            ->placeholder('Masukkan nama lengkap penyewa')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('nomor_hp')
                            ->label('Nomor Telepon / HP')
                            ->placeholder('Contoh: 08123456789')
                            ->tel()
                            ->required()
                            ->maxLength(20),

                        FileUpload::make('foto_ktp_sim')
                            ->label('Foto KTP / SIM')
                            ->helperText('Upload foto KTP atau SIM penyewa. Maks. 2 MB.')
                            ->image()
                            ->imageEditor()
                            ->directory('foto-ktp-sim')
                            ->disk('public')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),

                    ])
                    ->columns(2),

                // ── SECTION 2: Detail Produk & Durasi ────────────────────
                Section::make('Produk & Durasi Sewa')
                    ->icon('heroicon-o-cube')
                    ->schema([

                        CheckboxList::make('produk_alat_kesehatan')
                            ->label('Produk Alat Kesehatan')
                            ->helperText('Pilih satu atau lebih produk yang disewa.')
                            ->options([
                                'Bed Pasien'          => 'Bed Pasien',
                                'Tabung Oksigen'      => 'Tabung Oksigen',
                                'Kursi Roda'          => 'Kursi Roda',
                                'Nebulizer'           => 'Nebulizer',
                                'Kursi Roda Travel'   => 'Kursi Roda Travel',
                            ])
                            ->columns(3)
                            ->required()
                            ->columnSpanFull(),

                        DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection()
                            ->live()                          // reactive: trigger re-render
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $durasi = self::hitungDurasi(
                                    $get('tanggal_mulai'),
                                    $get('tanggal_selesai'),
                                );
                                $set('durasi_preview', $durasi);
                            }),

                        DatePicker::make('tanggal_selesai')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection()
                            ->minDate(fn (Get $get) => $get('tanggal_mulai') ?: now())
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $durasi = self::hitungDurasi(
                                    $get('tanggal_mulai'),
                                    $get('tanggal_selesai'),
                                );
                                $set('durasi_preview', $durasi);
                            }),

                        // Field virtual hanya untuk preview durasi — tidak disimpan ke DB
                        TextInput::make('durasi_preview')
                            ->label('Total Durasi')
                            ->placeholder('Otomatis terhitung setelah tanggal dipilih')
                            ->disabled()
                            ->dehydrated(false)   // tidak ikut disimpan ke database
                            ->suffixIcon('heroicon-o-clock'),

                    ])
                    ->columns(2),

                // ── SECTION 3: Pengiriman & Ongkir ───────────────────────
                Section::make('Pengiriman & Ongkos Kirim')
                    ->icon('heroicon-o-truck')
                    ->schema([

                        Select::make('pengiriman')
                            ->label('Metode Pengiriman')
                            ->options([
                                'mandiri'      => 'Ambil dan Antar kembali sendiri oleh Penyewa',
                                'gosend_grab'  => 'via Gosend / GrabExpress (barang dibawah 2 Kg, ex. Nebulizer / Kursi Roda Travel / sejenisnya)',
                                'rental_mobil' => 'via Rental Mobil Paralkes (disarankan untuk Bed Pasien / Tabung Oksigen Besar / sejenisnya)',
                            ])
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                // Jika mandiri, otomatis set ongkir jadi 0
                                if ($get('pengiriman') === 'mandiri') {
                                    $set('biaya_ongkir', 0);
                                }
                            })
                            ->columnSpanFull(),

                        TextInput::make('biaya_ongkir')
                            ->label('Biaya Ongkir')
                            ->helperText('Isi 0 jika ambil sendiri.')
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0)
                            ->default(0)
                            ->disabled(fn (Get $get): bool => $get('pengiriman') === 'mandiri')
                            ->dehydrated()   // tetap disimpan walau disabled
                            ->columnSpanFull(),

                        Textarea::make('alamat_penyewa')
                            ->label('Alamat Penyewa')
                            ->helperText('Wajib diisi jika menggunakan pengiriman.')
                            ->placeholder('Masukkan alamat lengkap penyewa')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),

                    ])
                    ->columns(2),

                // ── SECTION 4: Pembayaran ─────────────────────────────────
                Section::make('Pembayaran')
                    ->icon('heroicon-o-banknotes')
                    ->schema([

                        Select::make('metode_pembayaran')
                            ->label('Metode Pembayaran')
                            ->options([
                                'tunai'    => 'Tunai / Cash',
                                'transfer' => 'Transfer via Bank BCA 8030910754 a.n. SURYA DAYYANA',
                            ])
                            ->required()
                            ->native(false),

                        FileUpload::make('bukti_pembayaran')
                            ->label('Bukti Pembayaran')
                            ->helperText('Upload screenshot / foto bukti transfer. Maks. 2 MB. Kosongkan jika bayar tunai.')
                            ->image()
                            ->imageEditor()
                            ->directory('bukti-pembayaran')
                            ->disk('public')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),

                    ])
                    ->columns(2),

                // ── SECTION 5: Status & Keterangan ───────────────────────
                Section::make('Status & Keterangan')
                    ->icon('heroicon-o-information-circle')
                    ->schema([

                        Select::make('status')
                            ->label('Status Penyewaan')
                            ->options([
                                'berjalan'           => 'Berjalan',
                                'segera konfirmasi'  => 'Segera Konfirmasi',
                                'selesai'            => 'Selesai',
                            ])
                            ->required()
                            ->native(false)
                            ->default('berjalan'),

                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('Catatan tambahan (opsional)')
                            ->rows(3)
                            ->maxLength(1000),

                    ])
                    ->columns(2),

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
                    ->sortable()
                    ->wrap(),

                TextColumn::make('nomor_hp')
                    ->label('Nomor HP')
                    ->searchable(),

                TextColumn::make('produk_alat_kesehatan')
                    ->label('Produk')
                    ->formatStateUsing(function ($state): string {
                        if (is_array($state)) {
                            return implode(', ', $state);
                        }
                        $decoded = json_decode((string) $state, true);
                        return is_array($decoded)
                            ? implode(', ', $decoded)
                            : (string) $state;
                    })
                    ->wrap()
                    ->limit(60)
                    ->tooltip(function ($record): ?string {
                        $products = is_array($record->produk_alat_kesehatan)
                            ? $record->produk_alat_kesehatan
                            : (json_decode((string) $record->produk_alat_kesehatan, true) ?? []);
                        return implode(', ', $products);
                    }),

                TextColumn::make('durasi')
                    ->label('Durasi')
                    ->alignCenter()
                    ->getStateUsing(function (Penyewaan $record): string {
                        return self::hitungDurasi(
                            $record->tanggal_mulai,
                            $record->tanggal_selesai,
                        ) ?: '-';
                    })
                    ->sortable(query: fn ($query, string $direction) => $query
                        ->orderByRaw('DATEDIFF(tanggal_selesai, tanggal_mulai) ' . $direction)
                    ),

                TextColumn::make('pengiriman')
                    ->label('Pengiriman')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'mandiri'      => 'Mandiri',
                        'gosend_grab'  => 'Gosend / GrabExpress',
                        'rental_mobil' => 'Rental Mobil Paralkes',
                        default        => ucfirst($state),
                    })
                    ->wrap(),

                TextColumn::make('biaya_ongkir')
                    ->label('Biaya Ongkir')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                TextColumn::make('alamat_penyewa')
                    ->label('Alamat')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->alamat_penyewa)
                    ->wrap(),

                TextColumn::make('metode_pembayaran')
                    ->label('Metode Bayar')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'tunai'    => 'Tunai',
                        'transfer' => 'Transfer BCA',
                        default    => ucfirst((string) $state),
                    }),

                ImageColumn::make('bukti_pembayaran')
                    ->label('Bukti Bayar')
                    ->square()
                    ->size(48),

                ImageColumn::make('foto_ktp_sim')
                    ->label('KTP / SIM')
                    ->square()
                    ->size(48),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'berjalan'           => 'info',
                        'segera konfirmasi'  => 'warning',
                        'selesai'            => 'success',
                        default              => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'berjalan'           => 'Berjalan',
                        'segera konfirmasi'  => 'Segera Konfirmasi',
                        'selesai'            => 'Selesai',
                        default              => ucfirst($state),
                    })
                    ->sortable(),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->keterangan)
                    ->wrap(),
            ])
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50, 100])
            ->actions([
                EditAction::make()->label('Edit'),
                DeleteAction::make()->label('Hapus'),
            ])
            ->heading('Data Penyewaan')
            ->emptyStateHeading('Belum ada data penyewaan')
            ->emptyStateDescription('Klik tombol Input Data untuk menambahkan penyewaan baru.');
    }

    // =========================================================
    // PAGES
    // =========================================================
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPenyewaans::route('/'),
            'create' => Pages\CreatePenyewaan::route('/create'),
            'edit'   => Pages\EditPenyewaan::route('/{record}/edit'),
        ];
    }
}