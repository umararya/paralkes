<?php

namespace App\Filament\Resources\PenyewaanResource\Pages;

use App\Filament\Resources\PenyewaanResource;
use App\Models\Penyewaan;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Table;
use Illuminate\View\View;

class ListPenyewaans extends ListRecords
{
    protected static string $resource = PenyewaanResource::class;

    // =========================================================
    // PAGE HEADER
    // Tombol "Input Data" tetap di header halaman (atas tabel).
    // =========================================================
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Input Data'),
        ];
    }

    // =========================================================
    // TABLE OVERRIDE
    // Menambahkan tombol "Monitoring" ke toolbar TABEL (bukan
    // header halaman), sehingga posisinya tepat di sebelah kanan
    // search bar bawaan Filament.
    //
    // Layout otomatis:
    //   [ 🔍 Search ─────────────── 📡 Monitoring ]
    // =========================================================
    public function table(Table $table): Table
    {
        return parent::table($table)
            ->headerActions([
                TableAction::make('monitoring')
                    ->label('Monitoring')
                    ->icon('heroicon-o-signal')
                    ->color('warning')

                    // ── Modal Config ──────────────────────────────────────
                    ->modalHeading('📡 Monitoring Penyewaan Aktif')
                    ->modalDescription(
                        'Menampilkan semua penyewaan yang masih berjalan. ' .
                        'Baris berwarna kuning menandakan perlu segera dikonfirmasi (≤ 3 hari).'
                    )
                    ->modalWidth('6xl')
                    ->modalSubmitAction(false)               // sembunyikan tombol "Submit"
                    ->modalCancelActionLabel('Tutup')

                    // ── Content: ambil data & render blade view ───────────
                    ->modalContent(function (): View {
                        /*
                         * Ambil semua penyewaan yang belum berstatus 'selesai'.
                         * Sorting: sisa hari terkecil (paling mendesak) tampil duluan.
                         * Filter akhir: gunakan accessor status_otomatis untuk exclude
                         * record yang accessor-nya sudah 'selesai' meski kolom DB-nya
                         * belum diupdate manual.
                         */
                        $records = Penyewaan::query()
                            ->where('status', '!=', 'selesai')
                            ->orderBy('tanggal_selesai', 'asc')   // paling dekat selesai = paling atas
                            ->get()
                            ->filter(
                                fn (Penyewaan $r): bool => $r->status_otomatis !== 'selesai'
                            );

                        // Pisahkan ke dua kelompok agar urgent tampil teratas
                        $urgent  = $records->filter(fn ($r) => $r->status_otomatis === 'segera konfirmasi');
                        $normal  = $records->filter(fn ($r) => $r->status_otomatis === 'berjalan');

                        // Gabung: urgent duluan
                        $sorted = $urgent->merge($normal);

                        return view('filament.monitoring', [
                            'records' => $sorted,
                            'totalUrgent' => $urgent->count(),
                            'totalNormal' => $normal->count(),
                        ]);
                    }),
            ]);
    }
}