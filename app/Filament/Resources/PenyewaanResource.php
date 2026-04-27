<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenyewaanResource\Pages;
use App\Models\Penyewaan;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class PenyewaanResource extends Resource
{
    protected static ?string $model = Penyewaan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Admin';

    protected static ?string $navigationLabel = 'Penyewaan';

    protected static ?string $modelLabel = 'Penyewaan';

    protected static ?int $navigationSort = 1;

    // =========================================================
    // HELPER — hitung durasi dari dua tanggal (untuk form preview)
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
    // HELPER — hitung SISA hari dari sekarang ke tanggal selesai
    // Mengembalikan:
    //   >= 0  : sisa hari (0 = hari ini adalah deadline)
    //   -1    : sudah melewati deadline
    // =========================================================
    private static function hitungSisaHari(Penyewaan $record): int
    {
        if (! $record->tanggal_selesai) {
            return 0;
        }

        $today   = Carbon::today();
        $selesai = Carbon::parse($record->tanggal_selesai)->startOfDay();

        if ($selesai->lt($today)) {
            return -1; // sudah melewati deadline
        }

        return (int) $today->diffInDays($selesai);
    }

    // =========================================================
    // FORM
    // =========================================================
    public static function form(Form $form): Form
    {
        return $form
            ->schema([

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

                Section::make('Produk & Durasi Sewa')
                    ->icon('heroicon-o-cube')
                    ->schema([

                        CheckboxList::make('produk_alat_kesehatan')
                            ->label('Produk Alat Kesehatan')
                            ->helperText('Pilih satu atau lebih produk yang disewa.')
                            ->options([
                                'Bed Pasien'        => 'Bed Pasien',
                                'Tabung Oksigen'    => 'Tabung Oksigen',
                                'Kursi Roda'        => 'Kursi Roda',
                                'Nebulizer'         => 'Nebulizer',
                                'Kursi Roda Travel' => 'Kursi Roda Travel',
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
                            ->live()
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

                        TextInput::make('durasi_preview')
                            ->label('Total Durasi')
                            ->placeholder('Otomatis terhitung setelah tanggal dipilih')
                            ->disabled()
                            ->dehydrated(false)
                            ->suffixIcon('heroicon-o-clock'),

                    ])
                    ->columns(2),

                Section::make('Pengiriman & Ongkos Kirim')
                    ->icon('heroicon-o-truck')
                    ->schema([

                        Select::make('pengiriman')
                            ->label('Metode Pengiriman')
                            ->options([
                                'mandiri'      => 'Ambil dan Antar kembali sendiri oleh Penyewa',
                                'gosend_grab'  => 'via Gosend / GrabExpress',
                                'rental_mobil' => 'via Rental Mobil Paralkes',
                            ])
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
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
                            ->dehydrated()
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
                            ->helperText('Upload screenshot / foto bukti transfer. Maks. 2 MB.')
                            ->image()
                            ->imageEditor()
                            ->directory('bukti-pembayaran')
                            ->disk('public')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),

                    ])
                    ->columns(2),

                Section::make('Status & Keterangan')
                    ->icon('heroicon-o-information-circle')
                    ->schema([

                        Select::make('status')
                            ->label('Status Penyewaan')
                            ->options([
                                'berjalan'          => 'Berjalan',
                                'segera konfirmasi' => 'Segera Konfirmasi',
                                'selesai'           => 'Selesai',
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

                // ── STATUS COLUMN — dinamis berdasarkan sisa hari ─────────
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(function (Penyewaan $record): string {
                        $sisaHari = self::hitungSisaHari($record);

                        // Sudah lewat deadline tapi belum diselesaikan
                        if ($sisaHari === -1 && $record->status === 'berjalan') {
                            return 'danger';
                        }

                        // Sisa ≤ 3 hari dan masih berjalan → perlu perhatian
                        if ($sisaHari <= 3 && $sisaHari >= 0 && $record->status === 'berjalan') {
                            return 'warning';
                        }

                        return match ($record->status) {
                            'berjalan'          => 'info',
                            'segera konfirmasi' => 'warning',
                            'selesai'           => 'success',
                            default             => 'gray',
                        };
                    })
                    ->formatStateUsing(function (Penyewaan $record): string {
                        $sisaHari = self::hitungSisaHari($record);

                        // Sudah melewati deadline
                        if ($sisaHari === -1 && $record->status === 'berjalan') {
                            return '⚠ Melewati Deadline!';
                        }

                        // Sisa ≤ 3 hari → tampilkan peringatan
                        if ($sisaHari <= 3 && $sisaHari >= 0 && $record->status === 'berjalan') {
                            return '🔔 Perlu Konfirmasi!';
                        }

                        return match ($record->status) {
                            'berjalan'          => 'Berjalan',
                            'segera konfirmasi' => 'Segera Konfirmasi',
                            'selesai'           => 'Selesai',
                            default             => ucfirst($record->status),
                        };
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
                // ─────────────────────────────────────────────────────────
                // ACTION MONITORING — logika bercabang berdasarkan sisa hari
                // ─────────────────────────────────────────────────────────
                Action::make('monitoring')
                    ->label('Action')
                    ->button()
                    ->size('sm')

                    // ── Warna tombol dinamis ───────────────────────────────
                    ->color(function (Penyewaan $record): string {
                        if ($record->status === 'selesai') {
                            return 'gray';
                        }

                        $sisaHari = self::hitungSisaHari($record);

                        return ($sisaHari <= 3) ? 'warning' : 'primary';
                    })

                    // ── Sembunyikan jika sudah selesai ────────────────────
                    ->visible(fn (Penyewaan $record): bool => $record->status !== 'selesai')

                    // ── Judul modal dinamis ────────────────────────────────
                    ->modalHeading(function (Penyewaan $record): string {
                        $sisaHari = self::hitungSisaHari($record);

                        return $sisaHari > 3
                            ? 'Selesaikan Penyewaan'
                            : 'Konfirmasi & Tindakan Penyewaan';
                    })

                    // ── Lebar modal ────────────────────────────────────────
                    ->modalWidth('lg')

                    // ── Label tombol submit modal ──────────────────────────
                    ->modalSubmitActionLabel(function (Penyewaan $record): string {
                        $sisaHari = self::hitungSisaHari($record);

                        return $sisaHari > 3 ? 'Selesaikan Penyewaan' : 'Simpan Tindakan';
                    })

                    // ── FORM MODAL — bercabang kondisi ────────────────────
                    ->form(function (Penyewaan $record): array {
                        $sisaHari    = self::hitungSisaHari($record);
                        $deadlineFmt = Carbon::parse($record->tanggal_selesai)->translatedFormat('d F Y');
                        $minExtend   = Carbon::parse($record->tanggal_selesai)->addDay();

                        // ══════════════════════════════════════════════════
                        // KONDISI 1: Sisa > 3 hari
                        // Cukup tampilkan info, konfirmasi via requiresConfirmation
                        // ══════════════════════════════════════════════════
                        if ($sisaHari > 3) {
                            return [
                                Placeholder::make('durasi_info')
                                    ->label('')
                                    ->content(new HtmlString(
                                        '<div class="flex items-center gap-3 rounded-xl bg-blue-50 dark:bg-blue-900/20 '
                                        . 'border border-blue-200 dark:border-blue-700 px-4 py-3">'
                                        . '<svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">'
                                        . '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 '
                                        . '0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" '
                                        . 'clip-rule="evenodd"/></svg>'
                                        . '<span class="text-sm text-blue-700 dark:text-blue-300">'
                                        . 'Durasi penyewaan masih <strong>' . $sisaHari . ' hari</strong> lagi'
                                        . ' (deadline: <strong>' . $deadlineFmt . '</strong>).'
                                        . '</span>'
                                        . '</div>'
                                    )),
                            ];
                        }

                        // ══════════════════════════════════════════════════
                        // KONDISI 2: Sisa ≤ 3 hari (atau sudah lewat)
                        // Gunakan Wizard 2 step
                        // ══════════════════════════════════════════════════
                        $sisaLabel = $sisaHari === -1
                            ? 'Penyewaan ini sudah <strong>melewati tanggal deadline</strong>'
                            : 'Sisa durasi penyewaan hanya <strong>' . $sisaHari . ' hari</strong> lagi';

                        return [
                            Wizard::make([

                                // ── STEP 1: Peringatan konfirmasi ke customer ──
                                Wizard\Step::make('Konfirmasi Customer')
                                    ->icon('heroicon-o-phone')
                                    ->description('Pastikan sudah menghubungi customer')
                                    ->schema([
                                        Placeholder::make('peringatan')
                                            ->label('')
                                            ->content(new HtmlString(
                                                '<div class="space-y-3">'

                                                // Banner utama
                                                . '<div class="flex items-start gap-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 '
                                                . 'border border-amber-200 dark:border-amber-700 px-4 py-4">'
                                                . '<svg class="w-6 h-6 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">'
                                                . '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 '
                                                . '1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 '
                                                . '1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>'
                                                . '<div>'
                                                . '<p class="font-semibold text-amber-800 dark:text-amber-300 text-sm">'
                                                . 'Diharap melakukan konfirmasi ke customer terlebih dahulu!'
                                                . '</p>'
                                                . '<p class="text-sm text-amber-700 dark:text-amber-400 mt-1">'
                                                . $sisaLabel . '. Hubungi customer untuk menentukan kelanjutan penyewaan.'
                                                . '</p>'
                                                . '</div>'
                                                . '</div>'

                                                // Info customer
                                                . '<div class="rounded-xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 '
                                                . 'dark:border-zinc-700 px-4 py-3">'
                                                . '<p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">'
                                                . 'Informasi Penyewa</p>'
                                                . '<p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">'
                                                . e($record->nama) . '</p>'
                                                . '<p class="text-sm text-zinc-600 dark:text-zinc-400">'
                                                . e($record->nomor_hp) . '</p>'
                                                . '<p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">'
                                                . 'Deadline: <strong>' . $deadlineFmt . '</strong>'
                                                . '</p>'
                                                . '</div>'

                                                . '</div>'
                                            )),
                                    ]),

                                // ── STEP 2: Pilih tindakan ─────────────────────
                                Wizard\Step::make('Pilih Tindakan')
                                    ->icon('heroicon-o-cursor-arrow-rays')
                                    ->description('Tentukan tindakan setelah konfirmasi')
                                    ->schema([
                                        Radio::make('tindakan')
                                            ->label('Pilih tindakan yang akan dilakukan:')
                                            ->options([
                                                'selesai'         => 'Selesai Sekarang',
                                                'sesuai_deadline' => 'Sesuai Deadline (' . $deadlineFmt . ')',
                                                'extend'          => 'Perpanjang (Extend)',
                                            ])
                                            ->descriptions([
                                                'selesai'         => 'Tandai penyewaan sebagai selesai saat ini juga.',
                                                'sesuai_deadline' => 'Biarkan status tetap berjalan; sistem otomatis menutup saat deadline tercapai.',
                                                'extend'          => 'Tambah durasi sewa dengan memilih tanggal selesai baru.',
                                            ])
                                            ->required()
                                            ->live(),

                                        DatePicker::make('tanggal_extend')
                                            ->label('Tanggal Selesai Baru')
                                            ->helperText(
                                                'Pilih tanggal perpanjangan. '
                                                . 'Minimal: ' . $minExtend->translatedFormat('d F Y') . '.'
                                            )
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->closeOnDateSelection()
                                            ->minDate($minExtend)
                                            ->required(fn (Get $get): bool => $get('tindakan') === 'extend')
                                            ->visible(fn (Get $get): bool => $get('tindakan') === 'extend'),
                                    ]),

                            ])
                            ->skippable(false)
                            ->contained(false),
                        ];
                    })

                    // ── KONFIRMASI tambahan untuk Kondisi 1 ───────────────
                    ->requiresConfirmation(function (Penyewaan $record): bool {
                        return self::hitungSisaHari($record) > 3;
                    })
                    ->modalDescription(function (Penyewaan $record): ?string {
                        $sisaHari = self::hitungSisaHari($record);

                        return $sisaHari > 3
                            ? 'Apakah anda ingin menyelesaikan penyewaan ini?'
                            : null;
                    })
                    ->modalIcon(function (Penyewaan $record): ?string {
                        $sisaHari = self::hitungSisaHari($record);

                        return $sisaHari > 3 ? 'heroicon-o-check-circle' : null;
                    })
                    ->modalIconColor(function (Penyewaan $record): ?string {
                        $sisaHari = self::hitungSisaHari($record);

                        return $sisaHari > 3 ? 'success' : null;
                    })

                    // ── EKSEKUSI action ────────────────────────────────────
                    ->action(function (array $data, Penyewaan $record): void {
                        $sisaHari = self::hitungSisaHari($record);

                        // ── KONDISI 1: Sisa > 3 hari → langsung selesai ───
                        if ($sisaHari > 3) {
                            $record->update(['status' => 'selesai']);

                            Notification::make()
                                ->title('Penyewaan Diselesaikan')
                                ->body('Status penyewaan atas nama ' . $record->nama . ' telah diubah menjadi Selesai.')
                                ->success()
                                ->send();

                            return;
                        }

                        // ── KONDISI 2: Sisa ≤ 3 hari → proses wizard ──────
                        $tindakan = $data['tindakan'] ?? null;

                        match ($tindakan) {

                            // Opsi A: Selesai sekarang
                            'selesai' => (function () use ($record): void {
                                $record->update(['status' => 'selesai']);

                                Notification::make()
                                    ->title('Penyewaan Diselesaikan')
                                    ->body('Status penyewaan atas nama ' . $record->nama . ' telah diubah menjadi Selesai.')
                                    ->success()
                                    ->send();
                            })(),

                            // Opsi B: Sesuai deadline — status tetap berjalan,
                            // auto-close akan dihandle oleh scheduled command
                            'sesuai_deadline' => (function () use ($record): void {
                                // Tandai sudah dikonfirmasi agar tidak muncul peringatan ulang
                                // (opsional: simpan flag konfirmasi di kolom keterangan atau kolom baru)
                                $keterangan = trim(($record->keterangan ?? '') . ' [Konfirmasi: sesuai deadline]');
                                $record->update([
                                    'status'     => 'berjalan',
                                    'keterangan' => $keterangan,
                                ]);

                                Notification::make()
                                    ->title('Penyewaan Berjalan Hingga Deadline')
                                    ->body(
                                        'Status tetap Berjalan. Sistem akan otomatis menutup pada '
                                        . Carbon::parse($record->tanggal_selesai)->translatedFormat('d F Y') . '.'
                                    )
                                    ->info()
                                    ->send();
                            })(),

                            // Opsi C: Extend — update tanggal selesai
                            'extend' => (function () use ($record, $data): void {
                                $tanggalBaru = $data['tanggal_extend'];

                                $record->update([
                                    'tanggal_selesai' => $tanggalBaru,
                                    'status'          => 'berjalan',
                                ]);

                                Notification::make()
                                    ->title('Durasi Sewa Diperpanjang')
                                    ->body(
                                        'Tanggal selesai penyewaan atas nama ' . $record->nama
                                        . ' diperpanjang hingga '
                                        . Carbon::parse($tanggalBaru)->translatedFormat('d F Y') . '.'
                                    )
                                    ->success()
                                    ->send();
                            })(),

                            default => Notification::make()
                                ->title('Tidak ada tindakan dipilih.')
                                ->warning()
                                ->send(),
                        };
                    }),

                // ─────────────────────────────────────────────────────────
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