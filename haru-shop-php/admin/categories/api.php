<?php
/**
 * 카테고리 API
 * GET: 목록 | POST: 생성 | PUT: 수정 (?id=) | DELETE: 삭제 (?id=)
 * DB 테이블: categories (Django shop.Category와 동일)
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

try {
    if ($method === 'GET') {
        $stmt = $pdo->query('SELECT id, name, slug, icon, sort_order, created_at, updated_at FROM categories ORDER BY sort_order, name');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['results' => $rows]);
        exit;
    }

    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $name = trim((string) ($body['name'] ?? ''));
        $slug = trim((string) ($body['slug'] ?? ''));
        $icon = trim((string) ($body['icon'] ?? ''));
        $sortOrder = isset($body['sort_order']) ? (int) $body['sort_order'] : 0;

        if ($name === '') {
            http_response_code(400);
            echo json_encode(['error' => '이름을 입력하세요.']);
            exit;
        }
        if ($slug === '') {
            $slug = preg_replace('/\s+/', '-', strtolower($name));
        }

        $stmt = $pdo->prepare('INSERT INTO categories (name, slug, icon, sort_order) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $slug, $icon, $sortOrder]);
        echo json_encode(['id' => (int) $pdo->lastInsertId(), 'ok' => true]);
        exit;
    }

    if ($method === 'PUT' && $id > 0) {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $name = trim((string) ($body['name'] ?? ''));
        $slug = trim((string) ($body['slug'] ?? ''));
        $icon = trim((string) ($body['icon'] ?? ''));
        $sortOrder = isset($body['sort_order']) ? (int) $body['sort_order'] : 0;

        if ($name === '') {
            http_response_code(400);
            echo json_encode(['error' => '이름을 입력하세요.']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE categories SET name=?, slug=?, icon=?, sort_order=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$name, $slug, $icon, $sortOrder, $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($method === 'DELETE' && $id > 0) {
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id=?');
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
