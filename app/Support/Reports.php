<?php

namespace App\Support;

/**
 * Registry jenis laporan + matriks hak akses.
 * Dipakai oleh menu sidebar dan ReportController.
 */
final class Reports
{
    /**
     * @return array<string, array{title: string, roles: list<string>, desc: string}>
     */
    public static function types(): array
    {
        return [
            'keuangan' => [
                'title' => 'Laporan Keuangan',
                'desc' => 'Transaksi pemasukan & pengeluaran per rentang tanggal',
                'roles' => ['admin', 'keuangan'],
            ],
            'pasien' => [
                'title' => 'Laporan Registrasi Pasien',
                'desc' => 'Pasien baru terdaftar per rentang tanggal',
                'roles' => ['admin', 'petugas', 'dokter'],
            ],
            'obat' => [
                'title' => 'Laporan Stok & Kedaluwarsa Obat',
                'desc' => 'Obat dengan kedaluwarsa jatuh pada rentang tanggal',
                'roles' => ['admin', 'petugas'],
            ],
            'survei' => [
                'title' => 'Laporan Survei Kepuasan',
                'desc' => 'Penilaian masyarakat per rentang tanggal',
                'roles' => ['admin', 'petugas'],
            ],
        ];
    }

    public static function allowedFor(string $role): array
    {
        return array_filter(self::types(), fn (array $t) => in_array($role, $t['roles'], true));
    }

    public static function canAccess(string $role, string $type): bool
    {
        $t = self::types()[$type] ?? null;

        return $t !== null && in_array($role, $t['roles'], true);
    }
}
