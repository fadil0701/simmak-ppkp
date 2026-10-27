<?php

/**
 * Smoke test HTTP nyata: login sebagai admin lalu buka halaman kunci
 * (users create/edit/show, employees, penugasan audit).
 *
 * Jalankan: php scripts/smoke_access_model.php
 */
$base = getenv('SMOKE_BASE') ?: 'http://127.0.0.1:8124';

function request(string $url, array $opts = []): array
{
    $ch = curl_init($url);

    $config = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_COOKIEJAR => $opts['jar'] ?? null,
        CURLOPT_COOKIEFILE => $opts['jar'] ?? null,
        CURLOPT_HTTPHEADER => $opts['headers'] ?? [],
    ];

    // CURLOPT_POSTFIELDS saja sudah memaksa metode POST, jadi hanya dipasang
    // ketika memang ada body.
    if (array_key_exists('post', $opts) && $opts['post'] !== null) {
        $config[CURLOPT_POST] = true;
        $config[CURLOPT_POSTFIELDS] = $opts['post'];
    }

    curl_setopt_array($ch, $config);

    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    return [
        'status' => $status,
        'body' => $raw === false ? '' : substr($raw, $headerSize),
        'headers' => $raw === false ? '' : substr($raw, 0, $headerSize),
    ];
}

$jar = tempnam(sys_get_temp_dir(), 'smoke').'.txt';

$loginPage = request($base.'/login', ['jar' => $jar]);
preg_match('/name="_token" value="([^"]+)"/', $loginPage['body'], $m);
$token = $m[1] ?? '';

echo "GET /login -> {$loginPage['status']} token=".($token !== '' ? 'ok' : 'MISSING').PHP_EOL;

$login = request($base.'/login', [
    'jar' => $jar,
    'post' => http_build_query([
        '_token' => $token,
        'email' => getenv('SMOKE_EMAIL') ?: 'admin@simmak.ppkp.local',
        'password' => getenv('SMOKE_PASSWORD') ?: 'password',
    ]),
]);

echo "POST /login -> {$login['status']}".PHP_EOL;

$pages = ['/users/create', '/users', '/employees', '/audit/assignments', '/audit/assignments/create'];

// Halaman detail bersifat dinamis; kirim id lewat env bila ingin ikut diuji.
$userId = getenv('SMOKE_USER_ID') ?: '';
$employeeId = getenv('SMOKE_EMPLOYEE_ID') ?: '';

if ($userId !== '') {
    $pages[] = '/users/'.$userId;
    $pages[] = '/users/'.$userId.'/edit';
}

if ($employeeId !== '') {
    $pages[] = '/employees/'.$employeeId.'/edit';
}

$failed = 0;

foreach ($pages as $path) {
    $res = request($base.$path, ['jar' => $jar]);
    $ok = $res['status'] === 200;
    $failed += $ok ? 0 : 1;
    $needle = '';

    if ($path === '/users/create') {
        $needle = str_contains($res['body'], 'scope_names[]') ? ' scopes=ok' : ' scopes=MISSING';
    } elseif (str_ends_with($path, '/edit') && str_starts_with($path, '/users/')) {
        $needle = str_contains($res['body'], 'scope_names[]') ? ' scopes=ok' : ' scopes=MISSING';
    } elseif (str_starts_with($path, '/users/') && ! str_ends_with($path, '/edit')) {
        $needle = str_contains($res['body'], 'Scope Sistem') ? ' scopes=ok' : ' scopes=MISSING';
    } elseif (str_starts_with($path, '/employees/')) {
        $needle = str_contains($res['body'], 'primary_clinic_id') ? ' clinics=ok' : ' clinics=MISSING';
    } elseif ($path === '/employees') {
        $needle = str_contains($res['body'], 'Klinik') ? ' clinic=ok' : ' clinic=MISSING';
    } elseif ($path === '/audit/assignments') {
        $needle = str_contains($res['body'], 'Petugas Auditor') ? ' columns=ok' : ' columns=MISSING';
    } elseif ($path === '/audit/assignments/create') {
        $needle = str_contains($res['body'], 'instrument_version_id') ? ' instrument=ok' : ' instrument=MISSING';
    }

    printf("%-28s -> %d len=%d%s%s\n", $path, $res['status'], strlen($res['body']), $needle, $ok ? '' : '  <-- FAIL');
}

@unlink($jar);

echo $failed === 0 ? 'SMOKE PASS'.PHP_EOL : "SMOKE FAIL ({$failed} halaman)".PHP_EOL;

exit($failed === 0 ? 0 : 1);
