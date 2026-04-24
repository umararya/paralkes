<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penyewaan extends Model
{
    use HasFactory;

    protected $table = 'penyewaan';

    protected $fillable = [
        'nama',
        'nomor_hp',
        'produk_alat_kesehatan',
        'tanggal_mulai',
        'tanggal_selesai',
        'pengiriman',
        'biaya_ongkir',
        'alamat_penyewa',
        'metode_pembayaran',
        'bukti_pembayaran',
        'foto_ktp_sim',
        'status',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'produk_alat_kesehatan' => 'array',
            'tanggal_mulai'         => 'date',
            'tanggal_selesai'       => 'date',
            'biaya_ongkir'          => 'integer',
        ];
    }

    // ── Accessor: hitung durasi sewa dalam hari ──────────────────────
    public function getDurasiHariAttribute(): int
    {
        if (! $this->tanggal_mulai || ! $this->tanggal_selesai) {
            return 0;
        }

        return (int) $this->tanggal_mulai->diffInDays($this->tanggal_selesai);
    }

    // ── Helper: label human-readable untuk enum status ───────────────
    public function getLabelStatusAttribute(): string
    {
        return match ($this->status) {
            'berjalan'          => 'Berjalan',
            'segera_konfirmasi' => 'Segera Konfirmasi',
            'selesai'           => 'Selesai',
            default             => ucfirst($this->status),
        };
    }

    // ── Helper: label human-readable untuk enum pengiriman ───────────
    public function getLabelPengirimanAttribute(): string
    {
        return match ($this->pengiriman) {
            'mandiri'      => 'Mandiri',
            'gosend_grab'  => 'GoSend / Grab',
            'rental_mobil' => 'Rental Mobil',
            default        => ucfirst($this->pengiriman),
        };
    }
}