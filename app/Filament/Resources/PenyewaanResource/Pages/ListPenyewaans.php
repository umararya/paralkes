<?php

namespace App\Filament\Resources\PenyewaanResource\Pages;

use App\Filament\Resources\PenyewaanResource;
use App\Models\Penyewaan;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\On;

class ListPenyewaans extends ListRecords
{
    protected static string $resource = PenyewaanResource::class;

    // ─── State untuk konfirmasi yang dipilih dari modal Monitoring ───
    public ?int   $konfirmasiRecordId = null;
    public string $konfirmasiStep     = 'pilih'; // 'pilih' | 'extend'

    // =========================================================
    // PAGE HEADER — tombol Input Data
    // =========================================================
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Input Data'),
        ];
    }

    // =========================================================
    // LIVEWIRE EVENT — dipanggil dari tombol di blade monitoring
    // ketika user klik "Konfirmasi" pada salah satu row
    // =========================================================
    #[On('buka-konfirmasi')]
    public function bukaKonfirmasi(int $id): void
    {
        $this->konfirmasiRecordId = $id;
        $this->dispatch('open-modal', id: 'modal-konfirmasi');
    }

    // =========================================================
    // TABLE OVERRIDE — tambah Header Action "Monitoring"
    // =========================================================
    public function table(Table $table): Table
    {
        return parent::table($table)
            ->headerActions([
                // ── Tombol Monitoring ─────────────────────────────────
                TableAction::make('monitoring')
                    ->label('Monitoring')
                    ->icon('heroicon-o-signal')
                    ->color('warning')
                    ->modalHeading('📡 Monitoring Penyewaan Aktif')
                    ->modalDescription(
                        'Semua penyewaan yang masih berjalan. '
                        . 'Baris kuning = sisa ≤ 3 hari / sudah lewat deadline.'
                    )
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function (): View {
                        $records = Penyewaan::query()
                            ->where('status', '!=', 'selesai')
                            ->orderBy('tanggal_selesai', 'asc')
                            ->get()
                            ->filter(
                                fn (Penyewaan $r): bool => $r->status_otomatis !== 'selesai'
                            );

                        $urgent = $records->filter(fn ($r) => $r->status_otomatis === 'segera konfirmasi');
                        $normal = $records->filter(fn ($r) => $r->status_otomatis === 'berjalan');
                        $sorted = $urgent->merge($normal);

                        return view('filament.monitoring', [
                            'records'      => $sorted,
                            'totalUrgent'  => $urgent->count(),
                            'totalNormal'  => $normal->count(),
                        ]);
                    }),

                // ── Action tersembunyi — dipanggil via Livewire event ──
                // Ini adalah modal Konfirmasi yang muncul setelah user
                // klik tombol "Konfirmasi" pada row di tabel Monitoring.
                TableAction::make('konfirmasi_penyewaan')
                    ->label('')
                    ->icon('heroicon-o-check-circle')
                    ->hidden()        // tidak tampil di toolbar
                    ->modalHeading(function (): string {
                        if (! $this->konfirmasiRecordId) {
                            return 'Konfirmasi Penyewaan';
                        }
                        $record   = Penyewaan::find($this->konfirmasiRecordId);
                        $sisaHari = $record ? PenyewaanResource::hitungSisaHari($record) : 0;

                        return $sisaHari > 3
                            ? 'Selesaikan Penyewaan'
                            : 'Konfirmasi & Tindakan Penyewaan';
                    })
                    ->modalWidth('lg')
                    ->modalSubmitActionLabel(function (): string {
                        if (! $this->konfirmasiRecordId) {
                            return 'Simpan';
                        }
                        $record   = Penyewaan::find($this->konfirmasiRecordId);
                        $sisaHari = $record ? PenyewaanResource::hitungSisaHari($record) : 0;

                        return $sisaHari > 3 ? 'Selesaikan' : 'Simpan Tindakan';
                    })
                    ->form(function (): array {
                        if (! $this->konfirmasiRecordId) {
                            return [];
                        }

                        $record   = Penyewaan::find($this->konfirmasiRecordId);
                        if (! $record) {
                            return [];
                        }

                        $sisaHari    = PenyewaanResource::hitungSisaHari($record);
                        $deadlineFmt = Carbon::parse($record->tanggal_selesai)->translatedFormat('d F Y');
                        $minExtend   = Carbon::parse($record->tanggal_selesai)->addDay();

                        // ── Kondisi 1: Sisa > 3 hari ──────────────────────
                        if ($sisaHari > 3) {
                            return [
                                Placeholder::make('info')
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
                                        . ' Apakah Anda ingin menyelesaikan penyewaan ini sekarang?'
                                        . '</span>'
                                        . '</div>'
                                    )),
                            ];
                        }

                        // ── Kondisi 2: Sisa ≤ 3 hari atau sudah lewat ─────
                        $sisaLabel = $sisaHari === -1
                            ? 'Penyewaan ini sudah <strong>melewati tanggal deadline</strong>'
                            : 'Sisa durasi penyewaan hanya <strong>' . $sisaHari . ' hari</strong> lagi';

                        return [
                            Wizard::make([
                                Wizard\Step::make('Konfirmasi Customer')
                                    ->icon('heroicon-o-phone')
                                    ->description('Pastikan sudah menghubungi customer')
                                    ->schema([
                                        Placeholder::make('peringatan')
                                            ->label('')
                                            ->content(new HtmlString(
                                                '<div class="space-y-3">'
                                                . '<div class="flex items-start gap-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 '
                                                . 'border border-amber-200 dark:border-amber-700 px-4 py-4">'
                                                . '<svg class="w-6 h-6 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">'
                                                . '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 '
                                                . '1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 '
                                                . '1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>'
                                                . '<div>'
                                                . '<p class="font-semibold text-amber-800 dark:text-amber-300 text-sm">'
                                                . 'Lakukan konfirmasi ke customer terlebih dahulu!</p>'
                                                . '<p class="text-sm text-amber-700 dark:text-amber-400 mt-1">'
                                                . $sisaLabel . '. Hubungi customer untuk menentukan kelanjutan.</p>'
                                                . '</div></div>'
                                                . '<div class="rounded-xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 '
                                                . 'dark:border-zinc-700 px-4 py-3">'
                                                . '<p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Penyewa</p>'
                                                . '<p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">' . e($record->nama) . '</p>'
                                                . '<p class="text-sm text-zinc-600 dark:text-zinc-400">' . e($record->nomor_hp) . '</p>'
                                                . '<p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Deadline: <strong>' . $deadlineFmt . '</strong></p>'
                                                . '</div></div>'
                                            )),
                                    ]),

                                Wizard\Step::make('Pilih Tindakan')
                                    ->icon('heroicon-o-cursor-arrow-rays')
                                    ->description('Tentukan tindakan setelah konfirmasi')
                                    ->schema([
                                        Radio::make('tindakan')
                                            ->label('Pilih tindakan:')
                                            ->options([
                                                'selesai'         => 'Selesai Sekarang',
                                                'sesuai_deadline' => 'Sesuai Deadline (' . $deadlineFmt . ')',
                                                'extend'          => 'Perpanjang (Extend)',
                                            ])
                                            ->descriptions([
                                                'selesai'         => 'Tandai penyewaan sebagai selesai saat ini juga.',
                                                'sesuai_deadline' => 'Biarkan berjalan; sistem menutup otomatis saat deadline.',
                                                'extend'          => 'Tambah durasi sewa dengan tanggal selesai baru.',
                                            ])
                                            ->required()
                                            ->live(),

                                        DatePicker::make('tanggal_extend')
                                            ->label('Tanggal Selesai Baru')
                                            ->helperText('Minimal: ' . $minExtend->translatedFormat('d F Y') . '.')
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
                    ->action(function (array $data): void {
                        if (! $this->konfirmasiRecordId) {
                            return;
                        }

                        $record   = Penyewaan::find($this->konfirmasiRecordId);
                        if (! $record) {
                            return;
                        }

                        $sisaHari = PenyewaanResource::hitungSisaHari($record);

                        // ── Sisa > 3 hari → selesai langsung ──────────────
                        if ($sisaHari > 3) {
                            $record->update(['status' => 'selesai']);
                            Notification::make()
                                ->title('Penyewaan Diselesaikan')
                                ->body('Status penyewaan ' . $record->nama . ' diubah menjadi Selesai.')
                                ->success()
                                ->send();
                            $this->konfirmasiRecordId = null;

                            return;
                        }

                        // ── Sisa ≤ 3 hari → proses wizard ─────────────────
                        $tindakan = $data['tindakan'] ?? null;

                        match ($tindakan) {
                            'selesai' => (function () use ($record): void {
                                $record->update(['status' => 'selesai']);
                                Notification::make()
                                    ->title('Penyewaan Diselesaikan')
                                    ->body('Status penyewaan ' . $record->nama . ' diubah menjadi Selesai.')
                                    ->success()
                                    ->send();
                            })(),

                            'sesuai_deadline' => (function () use ($record): void {
                                $keterangan = trim(($record->keterangan ?? '') . ' [Konfirmasi: sesuai deadline]');
                                $record->update(['status' => 'berjalan', 'keterangan' => $keterangan]);
                                Notification::make()
                                    ->title('Penyewaan Berjalan Hingga Deadline')
                                    ->body(
                                        'Status tetap Berjalan. Sistem menutup otomatis pada '
                                        . Carbon::parse($record->tanggal_selesai)->translatedFormat('d F Y') . '.'
                                    )
                                    ->info()
                                    ->send();
                            })(),

                            'extend' => (function () use ($record, $data): void {
                                $record->update([
                                    'tanggal_selesai' => $data['tanggal_extend'],
                                    'status'          => 'berjalan',
                                ]);
                                Notification::make()
                                    ->title('Durasi Sewa Diperpanjang')
                                    ->body(
                                        'Tanggal selesai ' . $record->nama . ' diperpanjang hingga '
                                        . Carbon::parse($data['tanggal_extend'])->translatedFormat('d F Y') . '.'
                                    )
                                    ->success()
                                    ->send();
                            })(),

                            default => Notification::make()
                                ->title('Tidak ada tindakan dipilih.')
                                ->warning()
                                ->send(),
                        };

                        $this->konfirmasiRecordId = null;
                    }),
            ]);
    }
}