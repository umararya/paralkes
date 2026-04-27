{{--
    resources/views/filament/monitoring.blade.php
    Konten modal Monitoring — dipanggil via Action::modalContent()
--}}

<div class="space-y-5 py-1">

    {{-- ── Summary Badges ──────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center gap-3">

        {{-- Total aktif --}}
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-zinc-800 text-sm text-gray-700 dark:text-gray-300 font-medium">
            <x-heroicon-s-queue-list class="w-4 h-4 text-gray-500 dark:text-gray-400" />
            Total Aktif: <span class="font-bold text-gray-900 dark:text-white">{{ $records->count() }}</span>
        </div>

        {{-- Perlu konfirmasi --}}
        @if($totalUrgent > 0)
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-sm text-amber-700 dark:text-amber-300 font-medium">
                <x-heroicon-s-exclamation-triangle class="w-4 h-4" />
                Perlu Konfirmasi: <span class="font-bold">{{ $totalUrgent }}</span>
            </div>
        @endif

        {{-- Berjalan normal --}}
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800 text-sm text-sky-700 dark:text-sky-300 font-medium">
            <x-heroicon-s-arrow-trending-up class="w-4 h-4" />
            Berjalan Normal: <span class="font-bold">{{ $totalNormal }}</span>
        </div>

    </div>

    {{-- ── Tabel ────────────────────────────────────────────────────── --}}
    @if($records->isEmpty())

        {{-- Empty State --}}
        <div class="flex flex-col items-center justify-center py-12 text-center">
            <div class="w-16 h-16 rounded-full bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center mb-4">
                <x-heroicon-o-check-badge class="w-8 h-8 text-emerald-500" />
            </div>
            <p class="text-base font-semibold text-gray-800 dark:text-gray-200">Tidak Ada Penyewaan Aktif</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Semua penyewaan sudah selesai atau belum ada data.
            </p>
        </div>

    @else

        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-zinc-700 shadow-sm">
            <table class="w-full text-sm">

                {{-- Head --}}
                <thead>
                    <tr class="bg-gray-50 dark:bg-zinc-800 border-b border-gray-200 dark:border-zinc-700">
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 w-10">
                            No
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Nama
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            No HP
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Barang Disewa
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 max-w-[180px]">
                            Alamat
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Sisa Durasi
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Status
                        </th>
                    </tr>
                </thead>

                {{-- Body --}}
                <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/60">

                    @foreach($records as $index => $record)
                        @php
                            $sisaHari       = $record->sisa_hari;
                            $statusOtomatis = $record->status_otomatis;
                            $isUrgent       = $statusOtomatis === 'segera konfirmasi';
                            $isOverdue      = $sisaHari < 0;
                        @endphp

                        <tr class="
                            group transition-colors
                            {{ $isOverdue  ? 'bg-rose-50/60 dark:bg-rose-950/20 hover:bg-rose-50 dark:hover:bg-rose-950/30' : '' }}
                            {{ $isUrgent && ! $isOverdue ? 'bg-amber-50/50 dark:bg-amber-950/15 hover:bg-amber-50/80 dark:hover:bg-amber-950/25' : '' }}
                            {{ ! $isUrgent && ! $isOverdue ? 'hover:bg-gray-50/80 dark:hover:bg-zinc-800/40' : '' }}
                        ">

                            {{-- No --}}
                            <td class="px-4 py-3.5 text-center text-gray-400 dark:text-gray-500 font-mono text-xs">
                                {{ $index + 1 }}
                            </td>

                            {{-- Nama --}}
                            <td class="px-4 py-3.5">
                                <span class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $record->nama }}
                                </span>
                            </td>

                            {{-- No HP --}}
                            <td class="px-4 py-3.5 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                {{ $record->nomor_hp }}
                            </td>

                            {{-- Barang --}}
                            <td class="px-4 py-3.5">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($record->produk_alat_kesehatan ?? [] as $produk)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                            bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-300
                                            border border-sky-200/60 dark:border-sky-800/60">
                                            {{ $produk }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            {{-- Alamat --}}
                            <td class="px-4 py-3.5 max-w-[180px]">
                                @if($record->alamat_penyewa)
                                    <span class="text-gray-600 dark:text-gray-300 truncate block" title="{{ $record->alamat_penyewa }}">
                                        {{ $record->alamat_penyewa }}
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 italic text-xs">Mandiri / tidak ada</span>
                                @endif
                            </td>

                            {{-- Sisa Durasi (hitung mundur) --}}
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">

                                @if($isOverdue)
                                    {{-- Sudah lewat batas --}}
                                    <div class="flex flex-col items-center gap-0.5">
                                        <span class="flex items-center gap-1 text-xs font-bold text-rose-600 dark:text-rose-400">
                                            <x-heroicon-s-exclamation-triangle class="w-3.5 h-3.5" />
                                            Terlambat
                                        </span>
                                        <span class="text-xs text-rose-500 dark:text-rose-500">
                                            {{ abs($sisaHari) }} hari lalu
                                        </span>
                                    </div>

                                @elseif($sisaHari === 0)
                                    {{-- Berakhir hari ini --}}
                                    <div class="flex flex-col items-center gap-0.5">
                                        <span class="flex items-center gap-1 text-xs font-bold text-amber-600 dark:text-amber-400">
                                            <x-heroicon-s-clock class="w-3.5 h-3.5 animate-pulse" />
                                            Hari Ini!
                                        </span>
                                        <span class="text-xs text-amber-500 dark:text-amber-500">
                                            {{ $record->tanggal_selesai->format('d/m/Y') }}
                                        </span>
                                    </div>

                                @elseif($sisaHari <= 3)
                                    {{-- 1–3 hari tersisa --}}
                                    <div class="flex flex-col items-center gap-0.5">
                                        <span class="text-sm font-bold text-amber-600 dark:text-amber-400">
                                            {{ $sisaHari }} hari
                                        </span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500">
                                            s/d {{ $record->tanggal_selesai->format('d/m/Y') }}
                                        </span>
                                    </div>

                                @else
                                    {{-- Masih aman --}}
                                    <div class="flex flex-col items-center gap-0.5">
                                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                            {{ $sisaHari }} hari
                                        </span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500">
                                            s/d {{ $record->tanggal_selesai->format('d/m/Y') }}
                                        </span>
                                    </div>

                                @endif
                            </td>

                            {{-- Status Badge --}}
                            <td class="px-4 py-3.5 text-center">
                                @if($isOverdue)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                                        bg-rose-100 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300
                                        border border-rose-200 dark:border-rose-800">
                                        🚨 Terlambat
                                    </span>
                                @elseif($isUrgent)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                                        bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300
                                        border border-amber-200 dark:border-amber-800">
                                        ⚠️ Perlu Konfirmasi!
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                                        bg-sky-100 dark:bg-sky-950/50 text-sky-700 dark:text-sky-300
                                        border border-sky-200 dark:border-sky-800">
                                        ✅ Berjalan
                                    </span>
                                @endif
                            </td>

                        </tr>
                    @endforeach

                </tbody>
            </table>
        </div>

        {{-- Footer info --}}
        <div class="flex items-center justify-between text-xs text-gray-400 dark:text-gray-500 px-1">
            <span>
                Data diperbarui secara real-time saat modal dibuka.
            </span>
            <span>
                Total: {{ $records->count() }} penyewaan aktif
            </span>
        </div>

    @endif

</div>