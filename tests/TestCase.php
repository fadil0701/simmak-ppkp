<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Cegah RefreshDatabase menghapus database aplikasi (simmak_ppkp).
     */
    protected function beforeRefreshingDatabase()
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        $allowed = $database === ':memory:'
            || str_ends_with($database, '_test')
            || str_contains($database, 'testing')
            || str_contains($database, 'phpunit');

        if (! $allowed) {
            throw new RuntimeException(
                "PHPUnit menolak RefreshDatabase pada database aplikasi \"{$database}\". ".
                'Gunakan simmak_ppkp_test (lihat phpunit.xml) atau sqlite :memory:.'
            );
        }
    }
}
