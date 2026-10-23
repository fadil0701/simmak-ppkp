<?php

namespace App\Support;

class SpreadsheetCell
{
    public static function sanitize(mixed $value): string
    {
        $value = (string) $value;
        $trimmed = ltrim($value);

        if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@'], true)) {
            return "'".$value;
        }

        return $value;
    }
}
