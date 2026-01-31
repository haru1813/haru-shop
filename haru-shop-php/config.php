<?php
/**
 * 공통 설정 (DB 등)
 * Docker: DB_HOST=mariadb, DB_NAME=harushop 등 환경변수 사용
 * 호스트에서 실행 시: config.local.php 에서 DB_HOST=127.0.0.1, DB_PORT=503 등 지정
 */
$dbHost = getenv('DB_HOST') ?: 'mariadb';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'harushop';
$dbUser = getenv('DB_USER') ?: 'harushop';
$dbPass = getenv('DB_PASSWORD') ?: 'harushop';

if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
$pdo = null;

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    if (php_sapi_name() === 'cli') {
        throw $e;
    }
    $debug = getenv('APP_DEBUG') === '1' || getenv('APP_DEBUG') === 'true';
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    $body = ['error' => 'DB connection failed'];
    if ($debug) {
        $body['detail'] = $e->getMessage();
        $body['hint'] = 'Docker: mariadb 컨테이너 실행 여부 확인. 호스트에서 PHP 실행 시 config.local.php 에 DB_HOST=127.0.0.1, DB_PORT=503 설정';
    } else {
        $body['hint'] = 'APP_DEBUG=1 로 실제 오류 확인. Docker: docker compose ps mariadb 확인. 호스트 실행 시 config.local.php 사용';
    }
    echo json_encode($body);
    exit;
}
