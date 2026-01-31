<?php
/**
 * 배너 API
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
            $stmt = $pdo->prepare("SELECT id, image_url, link_url, sort_order, is_active, created_at, updated_at FROM banners WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['error' => '배너를 찾을 수 없습니다.']);
                exit;
            }
            echo json_encode($row);
            exit;
        }
        $stmt = $pdo->query("SELECT id, image_url, link_url, sort_order, is_active, created_at FROM banners ORDER BY sort_order, id");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['results' => $rows]);
        exit;
    }

    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $imageUrl = trim((string) ($body['image_url'] ?? ''));
        $linkUrl = trim((string) ($body['link_url'] ?? ''));
        $sortOrder = isset($body['sort_order']) ? (int) $body['sort_order'] : 0;
        $isActive = isset($body['is_active']) ? (int) (bool) $body['is_active'] : 1;

        if ($imageUrl === '') {
            http_response_code(400);
            echo json_encode(['error' => '이미지 URL을 입력하세요.']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO banners (image_url, link_url, sort_order, is_active) VALUES (?, ?, ?, ?)');
        $stmt->execute([$imageUrl, $linkUrl ?: null, $sortOrder, $isActive]);
        echo json_encode(['id' => (int) $pdo->lastInsertId(), 'ok' => true]);
        exit;
    }

    if ($method === 'PUT' && $id > 0) {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $imageUrl = trim((string) ($body['image_url'] ?? ''));
        $linkUrl = trim((string) ($body['link_url'] ?? ''));
        $sortOrder = isset($body['sort_order']) ? (int) $body['sort_order'] : 0;
        $isActive = isset($body['is_active']) ? (int) (bool) $body['is_active'] : 1;

        if ($imageUrl === '') {
            http_response_code(400);
            echo json_encode(['error' => '이미지 URL을 입력하세요.']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE banners SET image_url=?, link_url=?, sort_order=?, is_active=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$imageUrl, $linkUrl ?: null, $sortOrder, $isActive, $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($method === 'DELETE' && $id > 0) {
        $stmt = $pdo->prepare('DELETE FROM banners WHERE id=?');
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
