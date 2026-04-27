<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix inkonsistensi enum status:
     *   - 'segera_konfirmasi' (underscore) → 'segera konfirmasi' (spasi)
     *
     * Di MySQL/MariaDB, mengubah enum harus pakai DB::statement karena
     * Blueprint::enum() tidak bisa modify kolom enum secara langsung
     * tanpa doctrine/dbal yang sudah ter-install.
     */
    public function up(): void
    {
        // 1. Ubah data lama yang memakai underscore terlebih dahulu
        //    agar tidak ada nilai yang "invalid" setelah enum diubah.
        DB::table('penyewaan')
            ->where('status', 'segera_konfirmasi')
            ->update(['status' => 'segera konfirmasi']);

        // 2. Ubah definisi enum kolom status
        DB::statement("
            ALTER TABLE penyewaan
            MODIFY COLUMN status
                ENUM('berjalan', 'segera konfirmasi', 'selesai')
                NOT NULL
                DEFAULT 'berjalan'
        ");
    }

    public function down(): void
    {
        // Kembalikan ke underscore jika rollback
        DB::table('penyewaan')
            ->where('status', 'segera konfirmasi')
            ->update(['status' => 'segera_konfirmasi']);

        DB::statement("
            ALTER TABLE penyewaan
            MODIFY COLUMN status
                ENUM('berjalan', 'segera_konfirmasi', 'selesai')
                NOT NULL
                DEFAULT 'berjalan'
        ");
    }
};