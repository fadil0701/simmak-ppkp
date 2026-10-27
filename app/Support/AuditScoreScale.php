<?php

namespace App\Support;

/**
 * Skala nilai kesesuaian EP (elemen penilaian) pada form pelaksanaan audit.
 *
 * Satu-satunya sumber kebenaran untuk angka 0/5/10 yang dipakai di tiga lapis:
 * view (`audit.executions.checklist`), validasi controller, dan perhitungan
 * `AuditExecutionService`. Nilai teks lama (compliant/partial/non_compliant)
 * tetap diterima agar data historis dan API lama tidak rusak.
 */
class AuditScoreScale
{
    /** Sesuai penuh — memenuhi seluruh elemen penilaian. */
    public const FULL = 10;

    /** Sesuai sebagian — terpenuhi sebagian, wajib rekomendasi. */
    public const PARTIAL = 5;

    /** Tidak sesuai — tidak terpenuhi, wajib rekomendasi + temuan. */
    public const NONE = 0;

    /** Pilihan nilai yang boleh dikirim dari form tabel. */
    public const NOT_APPLICABLE = 'not_applicable';

    /**
     * Label tiap angka pada skala.
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return [
            self::FULL => 'Sesuai',
            self::PARTIAL => 'Sesuai sebagian',
            self::NONE => 'Tidak sesuai',
        ];
    }

    /**
     * @return array<int, int>
     */
    public static function values(): array
    {
        return array_keys(self::options());
    }

    /**
     * Nilai yang diizinkan pada `rows.*.response_value` form tabel:
     * tiga angka skala + opsi tidak berlaku + nilai teks lama (kompatibilitas).
     *
     * @return array<int, string>
     */
    public static function allowedResponseValues(): array
    {
        return array_merge(
            array_map('strval', self::values()),
            [
                self::NOT_APPLICABLE,
                'compliant',
                'partial',
                'non_compliant',
                'not_assessable',
            ],
        );
    }

    /**
     * Ubah nilai mentah form menjadi angka skala, atau null bila bukan angka skala.
     */
    public static function toScore(mixed $value): ?int
    {
        if ($value === null || $value === '' || is_bool($value)) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            $score = (int) $value;

            return in_array($score, self::values(), true) ? $score : null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            // Nilai teks lama dipetakan ke angka skala yang setara.
            $legacy = [
                'compliant' => self::FULL,
                'partial' => self::PARTIAL,
                'non_compliant' => self::NONE,
            ];

            if (isset($legacy[$trimmed])) {
                return $legacy[$trimmed];
            }

            if (! is_numeric($trimmed)) {
                return null;
            }

            $score = (int) $trimmed;

            return in_array($score, self::values(), true) ? $score : null;
        }

        return null;
    }

    /**
     * Terjemahkan angka skala ke `response_value` yang disimpan di database.
     */
    public static function responseValueFor(?int $score): string
    {
        return match (true) {
            $score === null => 'not_assessable',
            $score >= self::FULL => 'compliant',
            $score > self::NONE => 'partial',
            default => 'non_compliant',
        };
    }

    /**
     * Label tampilan untuk sebuah angka skala.
     */
    public static function labelFor(?int $score): string
    {
        return self::options()[$score] ?? 'Belum dinilai';
    }

    /**
     * Label lengkap: "10 — Sesuai".
     */
    public static function displayFor(?int $score): string
    {
        if ($score === null) {
            return 'Belum dinilai';
        }

        return $score.' — '.self::labelFor($score);
    }

    /**
     * Kelas badge Tailwind untuk angka skala.
     */
    public static function badgeClassFor(?int $score): string
    {
        return match (true) {
            $score === null => 'border-sim-border bg-sim-surface text-sim-muted',
            $score >= self::FULL => 'border-sim-success bg-sim-success-bg text-sim-success',
            $score > self::NONE => 'border-sim-warning bg-sim-warning-bg text-sim-warning',
            default => 'border-sim-danger bg-sim-danger-bg text-sim-danger',
        };
    }

    /**
     * Apakah nilai ini menuntut rekomendasi dan berpotensi menjadi temuan?
     * (sesuai sebagian dan tidak sesuai)
     */
    public static function requiresRecommendation(?int $score): bool
    {
        return $score !== null && $score < self::FULL;
    }

    /**
     * Apakah nilai ini wajib menghasilkan temuan audit?
     */
    public static function triggersFinding(?int $score): bool
    {
        return $score !== null && $score < self::FULL;
    }

    /**
     * Kunci distribusi untuk counter progres pengisian.
     */
    public static function distributionKeyFor(mixed $rawValue): string
    {
        if ($rawValue === self::NOT_APPLICABLE) {
            return 'not_applicable';
        }

        $score = self::toScore($rawValue);

        if ($score === null) {
            return 'unassessed';
        }

        return match (true) {
            $score >= self::FULL => 'compliant',
            $score > self::NONE => 'partial',
            default => 'non_compliant',
        };
    }
}
