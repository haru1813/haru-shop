<?php
/**
 * 사용자 API
 * GET: 목록 | GET ?id=X: 단건 | POST: 생성 | PUT ?id=X: 수정 | DELETE ?id=X: 삭제
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

try {
    if ($method === 'GET') {
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT id, email, name, picture, provider, role, created_at, updated_at FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['error' => '사용자를 찾을 수 없습니다.']);
                exit;
            }
            echo json_encode($row);
            exit;
        }
        $stmt = $pdo->query("SELECT id, email, name, provider, role, created_at FROM users ORDER BY created_at DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['results' => $rows]);
        exit;
    }

    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $email = trim((string) ($body['email'] ?? ''));
        $name = trim((string) ($body['name'] ?? ''));
        $picture = trim((string) ($body['picture'] ?? ''));
        $provider = trim((string) ($body['provider'] ?? 'google'));
        $role = trim((string) ($body['role'] ?? 'user'));

        if ($email === '') {
            http_response_code(400);
            echo json_encode(['error' => '이메일을 입력하세요.']);
            exit;
        }
        if (!in_array($role, ['user', 'seller', 'admin'], true)) {
            $role = 'user';
        }

        $stmt = $pdo->prepare('INSERT INTO users (email, name, picture, provider, role) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$email, $name ?: null, $picture ?: null, $provider, $role]);
        echo json_encode(['id' => (int) $pdo->lastInsertId(), 'ok' => true]);
        exit;
    }

    if ($method === 'PUT' && $id > 0) {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $name = trim((string) ($body['name'] ?? ''));
        $picture = trim((string) ($body['picture'] ?? ''));
        $role = trim((string) ($body['role'] ?? 'user'));

        if (!in_array($role, ['user', 'seller', 'admin'], true)) {
            $role = 'user';
        }

        $stmt = $pdo->prepare('UPDATE users SET name=?, picture=?, role=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$name ?: null, $picture ?: null, $role, $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($method === 'DELETE' && $id > 0) {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id=?');
        $stmt->execute([$id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
}
