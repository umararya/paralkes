<?php

namespace App\Console\Commands;

use App\Models\Penyewaan;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AutoSelesaikanPenyewaan extends Command
{
    /**
     * Jalankan otomatis setiap hari untuk menutup penyewaan
     * yang sudah melewati tanggal_selesai dengan status 'berjalan'.
     */
    protected $signature   = 'penyewaan:auto-selesai';
    protected $description = 'Otomatis mengubah status penyewaan menjadi selesai jika sudah melewati deadline.';

    public function handle(): int
    {
        $today = Carbon::today();

        $records = Penyewaan::query()
            ->where('status', 'berjalan')
            ->whereDate('tanggal_selesai', '<', $today)
            ->get();

        if ($records->isEmpty()) {
            $this->info('Tidak ada penyewaan yang perlu diselesaikan otomatis.');

            return self::SUCCESS;
        }

        $jumlah = $records->count();

        $records->each(fn (Penyewaan $p) => $p->update(['status' => 'selesai']));

        $this->info("✅ {$jumlah} penyewaan berhasil diselesaikan secara otomatis.");

        return self::SUCCESS;
    }
}