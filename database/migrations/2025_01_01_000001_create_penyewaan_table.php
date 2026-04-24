<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('penyewaan', function (Blueprint $table) {
            $table->id();

            // Data Penyewa
            $table->string('nama');
            $table->string('nomor_hp');

            // Produk (array pilihan alat kesehatan dari checkbox)
            $table->json('produk_alat_kesehatan');

            // Durasi Sewa
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');

            // Pengiriman
            $table->enum('pengiriman', ['mandiri', 'gosend_grab', 'rental_mobil']);
            $table->integer('biaya_ongkir')->default(0);
            $table->text('alamat_penyewa');

            // Pembayaran
            $table->string('metode_pembayaran');
            $table->string('bukti_pembayaran')->nullable(); // path file / URL foto
            $table->string('foto_ktp_sim');                // path file / URL foto

            // Status & Keterangan
            $table->enum('status', ['berjalan', 'segera_konfirmasi', 'selesai'])->default('berjalan');
            $table->text('keterangan')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penyewaan');
    }
};