<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class FullTextSearch
{
    /**
     * Pencarian teks pada kolom yang diberikan. MySQL memakai MATCH … AGAINST
     * (plus LIKE untuk kode pendek); PostgreSQL memakai to_tsvector; driver
     * lain jatuh ke LIKE. Input pengguna tidak pernah disambung ke SQL mentah.
     *
     * @param  list<string>  $columns
     */
    public static function apply(Builder $query, array $columns, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '' || $columns === []) {
            return $query;
        }

        $driver = $query->getConnection()->getDriverName();
        $like = '%'.addcslashes($term, '%_\\').'%';
        $boolean = self::booleanQuery($term);

        return $query->where(function (Builder $outer) use ($columns, $driver, $like, $boolean, $term) {
            if ($driver === 'mysql' && $boolean !== '') {
                $quoted = implode(', ', array_map(fn (string $column) => '`'.str_replace('`', '', $column).'`', $columns));
                $outer->orWhereRaw("MATCH ({$quoted}) AGAINST (? IN BOOLEAN MODE)", [$boolean]);
            }

            if ($driver === 'pgsql') {
                $concat = implode(" || ' ' || ", array_map(
                    fn (string $column) => "coalesce({$column}, '')",
                    $columns
                ));
                $outer->orWhereRaw("to_tsvector('simple', {$concat}) @@ plainto_tsquery('simple', ?)", [$term]);
            }

            foreach ($columns as $column) {
                $outer->orWhere($column, 'like', $like);
            }
        });
    }

    private static function booleanQuery(string $term): string
    {
        $tokens = preg_split('/\s+/u', $term) ?: [];
        $parts = [];

        foreach ($tokens as $token) {
            $clean = preg_replace('/[^\p{L}\p{N}_.-]+/u', '', $token) ?? '';
            if ($clean === '' || mb_strlen($clean) < 2) {
                continue;
            }
            $parts[] = '+'.$clean.'*';
        }

        return implode(' ', $parts);
    }
}
