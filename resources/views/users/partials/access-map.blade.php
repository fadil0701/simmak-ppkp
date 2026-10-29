{{--
  Peta tiga lapisan akses. Disengaja hanya copy — tidak mengubah data.
--}}
<div class="mb-5 overflow-hidden rounded-lg border border-sim-border bg-sim-surface-alt">
    <div class="border-b border-sim-border-light px-4 py-2.5">
        <p class="text-sm font-semibold text-sim-ink">Tiga pengaturan yang tidak boleh tertukar</p>
        <p class="mt-0.5 text-xs text-sim-muted">Masing-masing punya sumber data sendiri. Mengisi salah satu tidak mengganti yang lain.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-sim-white text-xs uppercase tracking-wide text-sim-muted">
                <tr>
                    <th class="px-4 py-2 font-semibold">Di form ini</th>
                    <th class="px-4 py-2 font-semibold">Artinya</th>
                    <th class="px-4 py-2 font-semibold">Bukan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-sim-border-light bg-sim-white">
                <tr>
                    <td class="px-4 py-2.5 font-medium text-sim-ink">Peran jabatan</td>
                    <td class="px-4 py-2.5 text-sim-muted">Jabatan organisasi di SIMMAK (menu dan persetujuan).</td>
                    <td class="px-4 py-2.5 text-sim-muted">Bukan jabatan master pegawai, bukan peran di tim audit.</td>
                </tr>
                <tr>
                    <td class="px-4 py-2.5 font-medium text-sim-ink">Tugas di sistem</td>
                    <td class="px-4 py-2.5 text-sim-muted">Modul yang boleh dikerjakan (audit, mutu, auditor, auditi, …).</td>
                    <td class="px-4 py-2.5 text-sim-muted">Bukan daftar klinik, bukan anggota tim penugasan.</td>
                </tr>
                <tr>
                    <td class="px-4 py-2.5 font-medium text-sim-ink">Batas klinik</td>
                    <td class="px-4 py-2.5 text-sim-muted">Klinik yang datanya boleh dilihat akun ini.</td>
                    <td class="px-4 py-2.5 text-sim-muted">
                        Bila akun terhubung ke pegawai, daftar ini <strong class="font-medium text-sim-ink">mengikuti master pegawai</strong> dan tidak diisi ulang di sini.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
