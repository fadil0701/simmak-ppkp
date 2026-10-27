<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use Maatwebsite\Excel\ExcelServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    ExcelServiceProvider::class,
];
