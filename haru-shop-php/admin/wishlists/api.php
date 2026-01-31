<?php
/**
 * 찜 API
 * GET: 목록 | DELETE ?id=X: 삭제
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

try {
    if ($method === 'GET') {
        $stmt = $pdo->query("
            SELECT w.id, w.user_id, w.product_id, w.created_at, p.name AS product_name
            FROM wishlists w
            LEFT JOIN products p ON w.product_id = p.id
            ORDER BY w.created_at DESC LIMIT 500
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['results' => $rows]);
        exit;
    }

    if ($method === 'DELETE' && $id > 0) {
        $stmt = $pdo->prepare('DELETE FROM wishlists WHERE id=?');
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
