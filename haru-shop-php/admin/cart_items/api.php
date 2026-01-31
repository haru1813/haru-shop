<?php
/**
 * 장바구니 API
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
            SELECT c.id, c.user_id, c.product_id, c.quantity, c.created_at, p.name AS product_name
            FROM cart_items c
            LEFT JOIN products p ON c.product_id = p.id
            ORDER BY c.created_at DESC LIMIT 500
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['results' => $rows]);
        exit;
    }

    if ($method === 'DELETE' && $id > 0) {
        $stmt = $pdo->prepare('DELETE FROM cart_items WHERE id=?');
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
