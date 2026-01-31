<?php
/**
 * 로그인 API
 * POST: { email, password } -> session 설정
 */
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true) ?: [];
$email = trim((string) ($body['email'] ?? ''));
$password = (string) ($body['password'] ?? '');

$adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@haru.local';
$adminPassword = getenv('ADMIN_PASSWORD') ?: 'admin123!';

if ($email === $adminEmail && $password === $adminPassword) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_email'] = $email;
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(401);
echo json_encode(['error' => '이메일 또는 비밀번호를 확인하세요.']);
