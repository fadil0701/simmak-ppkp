<?php

namespace App\Services;

use App\Models\AuditAssignment;
use App\Models\AuditExecution;
use App\Models\AuditFinding;
use App\Models\AuditProgram;
use App\Models\CapaCase;
use App\Models\ClinicalAudit;
use App\Models\DocumentNumberSequence;
use App\Models\RiskItem;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Penomoran dokumen bisnis: atomik per organisasi + seri (jenis-tahun).
 * Nomor lama tidak diubah; urutan baru mengikuti nilai tertinggi yang sudah ada.
 */
class DocumentNumberService
{
    public const CLINICAL_AUDIT = 'AKL';

    public const ASSIGNMENT = 'TUGAS';

    public const PROGRAM = 'PROG';

    public const EXECUTION = 'EXEC';

    public const FINDING = 'TMN';

    public const CAPA = 'TL';

    public const RISK = 'RSK';

    /** @var array<string, int> */
    public const PADS = [
        self::CLINICAL_AUDIT => 4,
        self::ASSIGNMENT => 3,
        self::PROGRAM => 4,
        self::EXECUTION => 4,
        self::FINDING => 4,
        self::CAPA => 4,
        self::RISK => 4,
    ];

    /** @var array<string, array{0: class-string, 1: string}> */
    private const SOURCES = [
        self::CLINICAL_AUDIT => [ClinicalAudit::class, 'audit_number'],
        self::ASSIGNMENT => [AuditAssignment::class, 'assignment_code'],
        self::PROGRAM => [AuditProgram::class, 'program_code'],
        self::EXECUTION => [AuditExecution::class, 'execution_code'],
        self::FINDING => [AuditFinding::class, 'finding_code'],
        self::CAPA => [CapaCase::class, 'case_number'],
        self::RISK => [RiskItem::class, 'risk_code'],
    ];

    public function peek(string $organizationId, string $kind): string
    {
        $year = now()->format('Y');
        $series = $this->series($kind, $year);
        $pad = $this->padFor($kind);

        $last = DocumentNumberSequence::query()
            ->where('organization_id', $organizationId)
            ->where('series', $series)
            ->value('last_value');

        if ($last === null) {
            $last = $this->maxExisting($organizationId, $kind, $year);
        }

        return $this->format($kind, $year, (int) $last + 1, $pad);
    }

    public function allocate(string $organizationId, string $kind): string
    {
        $year = now()->format('Y');

        return $this->allocateSeries(
            $organizationId,
            $this->series($kind, $year),
            $kind,
            $year,
            $this->padFor($kind),
            $this->sourceFor($kind),
        );
    }

    /**
     * Seri dinamis (laporan, dokumen DMS) dengan prefix jenis dokumen.
     *
     * @param  class-string  $modelClass
     */
    public function allocatePrefixed(string $organizationId, string $prefix, string $modelClass, string $column, int $pad = 4): string
    {
        $kind = strtoupper($prefix);
        $year = now()->format('Y');

        return $this->allocateSeries(
            $organizationId,
            $this->series($kind, $year),
            $kind,
            $year,
            $pad,
            [$modelClass, $column],
        );
    }

    public function peekPrefixed(string $organizationId, string $prefix, string $modelClass, string $column, int $pad = 4): string
    {
        $kind = strtoupper($prefix);
        $year = now()->format('Y');
        $series = $this->series($kind, $year);

        $last = DocumentNumberSequence::query()
            ->where('organization_id', $organizationId)
            ->where('series', $series)
            ->value('last_value');

        if ($last === null) {
            $last = $this->maxFromModel($organizationId, $modelClass, $column, $kind.'-'.$year.'-');
        }

        return $this->format($kind, $year, (int) $last + 1, $pad);
    }

    /**
     * @param  callable(string): mixed  $insert
     */
    public function insertWithAllocatedNumber(string $organizationId, string $kind, callable $insert): mixed
    {
        $attempts = 0;

        while ($attempts < 8) {
            $attempts++;

            try {
                $number = $this->allocate($organizationId, $kind);

                return $insert($number);
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempts >= 8) {
                    throw $exception;
                }
            }
        }

        throw ValidationException::withMessages([
            'number' => 'Nomor dokumen gagal dibuat. Silakan simpan ulang.',
        ]);
    }

    /**
     * @param  array{0: class-string, 1: string}|null  $source
     */
    private function allocateSeries(
        string $organizationId,
        string $series,
        string $kind,
        string $year,
        int $pad,
        ?array $source,
    ): string {
        return DB::transaction(function () use ($organizationId, $series, $kind, $year, $pad, $source) {
            $row = DocumentNumberSequence::query()
                ->where('organization_id', $organizationId)
                ->where('series', $series)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                $seed = $source
                    ? $this->maxFromModel($organizationId, $source[0], $source[1], $kind.'-'.$year.'-')
                    : 0;

                try {
                    DocumentNumberSequence::query()->create([
                        'organization_id' => $organizationId,
                        'series' => $series,
                        'last_value' => $seed,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    // Seri baru saja dibuat proses lain.
                }

                $row = DocumentNumberSequence::query()
                    ->where('organization_id', $organizationId)
                    ->where('series', $series)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $row->last_value = (int) $row->last_value + 1;
            $row->save();

            return $this->format($kind, $year, (int) $row->last_value, $pad);
        });
    }

    private function maxExisting(string $organizationId, string $kind, string $year): int
    {
        $source = $this->sourceFor($kind);
        if ($source === null) {
            return 0;
        }

        return $this->maxFromModel($organizationId, $source[0], $source[1], $kind.'-'.$year.'-');
    }

    /**
     * @param  class-string  $modelClass
     */
    private function maxFromModel(string $organizationId, string $modelClass, string $column, string $prefix): int
    {
        $query = $modelClass::query()
            ->where('organization_id', $organizationId)
            ->where($column, 'like', $prefix.'%');

        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withTrashed();
        }

        $last = $query->orderByDesc($column)->value($column);

        if (! is_string($last) || ! preg_match('/(\d+)$/', $last, $matches)) {
            return 0;
        }

        return (int) $matches[1];
    }

    /**
     * @return array{0: class-string, 1: string}|null
     */
    private function sourceFor(string $kind): ?array
    {
        return self::SOURCES[$kind] ?? null;
    }

    private function padFor(string $kind): int
    {
        return self::PADS[$kind] ?? 4;
    }

    private function series(string $kind, string $year): string
    {
        return $kind.'-'.$year;
    }

    private function format(string $kind, string $year, int $seq, int $pad): string
    {
        return $kind.'-'.$year.'-'.str_pad((string) $seq, $pad, '0', STR_PAD_LEFT);
    }
}
