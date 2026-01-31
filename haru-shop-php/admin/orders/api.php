<?php
/**
 * 주문 API
 * GET: 목록 | PUT ?id=X: 상태 수정
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

try {
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT id, order_number, user_id, status, total_amount, delivery_fee, receiver_name, created_at FROM orders ORDER BY created_at DESC LIMIT 500");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['results' => $rows]);
        exit;
    }

    if ($method === 'PUT' && $id > 0) {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $status = trim((string) ($body['status'] ?? ''));
        $allowed = ['payment_complete', 'preparing', 'shipping', 'delivered'];
        if ($status === '' || !in_array($status, $allowed, true)) {
            http_response_code(400);
            echo json_encode(['error' => '유효한 상태를 선택하세요.']);
            exit;
        }
        $stmt = $pdo->prepare('UPDATE orders SET status=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$status, $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
}
