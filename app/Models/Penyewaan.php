<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Penyewaan extends Model
{
    use HasFactory;

    protected $table = 'penyewaan';

    protected $fillable = [
        'nama',
        'nomor_hp',
        'produk_alat_kesehatan',
        'tanggal_mulai',
        'tanggal_selesai',   // fillable agar fitur Extend bisa update kolom ini
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

    // =========================================================
    // ACCESSOR: Sisa Hari
    // =========================================================

    /**
     * Hitung sisa hari dari hari ini sampai tanggal_selesai.
     *
     * - Positif  : sewa masih berjalan, angka = sisa hari
     * - 0        : hari ini adalah hari terakhir
     * - Negatif  : sudah melewati tanggal selesai (sewa terlambat)
     *
     * @return int
     */
    public function getSisaHariAttribute(): int
    {
        if (! $this->tanggal_selesai) {
            return 0;
        }

        // diffInDays() selalu positif; kita perlu cek arahnya manual
        $today = Carbon::today();
        $selesai = Carbon::parse($this->tanggal_selesai)->startOfDay();

        return $today->diffInDays($selesai, false); // false = signed (bisa negatif)
    }

    /**
     * Hitung total durasi sewa dalam hari (mulai → selesai).
     *
     * @return int
     */
    public function getDurasiHariAttribute(): int
    {
        if (! $this->tanggal_mulai || ! $this->tanggal_selesai) {
            return 0;
        }

        return (int) Carbon::parse($this->tanggal_mulai)
            ->diffInDays(Carbon::parse($this->tanggal_selesai));
    }

    // =========================================================
    // ACCESSOR: Status Otomatis (berbasis sisa hari)
    // =========================================================

    /**
     * Tentukan status otomatis berdasarkan sisa hari sewa.
     *
     * Aturan:
     *   - Status manual 'selesai'  → tetap 'selesai' (tidak di-override)
     *   - Sisa hari > 3            → 'berjalan'
     *   - Sisa hari 1–3 (inklusif) → 'segera konfirmasi'
     *   - Sisa hari <= 0           → 'segera konfirmasi'
     *     (sudah lewat tapi belum dikonfirmasi selesai)
     *
     * @return string
     */
    public function getStatusOtomatisAttribute(): string
    {
        // Jika admin sudah manual set 'selesai', hormati pilihan itu
        if ($this->status === 'selesai') {
            return 'selesai';
        }

        $sisaHari = $this->sisa_hari;

        if ($sisaHari > 3) {
            return 'berjalan';
        }

        return 'segera konfirmasi';
    }

    /**
     * Label status otomatis yang tampil di UI (dengan tanda seru).
     *
     * @return string
     */
    public function getLabelStatusOtomatisAttribute(): string
    {
        return match ($this->status_otomatis) {
            'berjalan'          => 'Berjalan',
            'segera konfirmasi' => 'Perlu Konfirmasi!',
            'selesai'           => 'Selesai',
            default             => ucfirst($this->status_otomatis),
        };
    }

    // =========================================================
    // ACCESSOR: Label Helper (manual status dari DB)
    // =========================================================

    /**
     * Label human-readable untuk nilai kolom `status` di database.
     */
    public function getLabelStatusAttribute(): string
    {
        return match ($this->status) {
            'berjalan'          => 'Berjalan',
            'segera konfirmasi' => 'Segera Konfirmasi',
            'selesai'           => 'Selesai',
            default             => ucfirst($this->status),
        };
    }

    /**
     * Label human-readable untuk enum `pengiriman`.
     */
    public function getLabelPengirimanAttribute(): string
    {
        return match ($this->pengiriman) {
            'mandiri'      => 'Mandiri',
            'gosend_grab'  => 'GoSend / GrabExpress',
            'rental_mobil' => 'Rental Mobil Paralkes',
            default        => ucfirst($this->pengiriman),
        };
    }
}