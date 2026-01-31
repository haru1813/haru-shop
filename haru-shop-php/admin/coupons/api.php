<?php
/**
 * 쿠폰 API
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
            $stmt = $pdo->prepare("SELECT id, code, name, discount_type, discount_value, min_order_amount, max_discount_amount, valid_from, valid_until, total_quantity, used_quantity, is_active, created_at, updated_at FROM coupons WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['error' => '쿠폰을 찾을 수 없습니다.']);
                exit;
            }
            echo json_encode($row);
            exit;
        }
        $stmt = $pdo->query("SELECT id, code, name, discount_type, discount_value, min_order_amount, max_discount_amount, valid_from, valid_until, total_quantity, used_quantity, is_active, created_at FROM coupons ORDER BY created_at DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['results' => $rows]);
        exit;
    }

    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $code = trim((string) ($body['code'] ?? ''));
        $name = trim((string) ($body['name'] ?? ''));
        $discountType = trim((string) ($body['discount_type'] ?? 'percent'));
        $discountValue = isset($body['discount_value']) ? (int) $body['discount_value'] : 0;
        $minOrderAmount = isset($body['min_order_amount']) ? (int) $body['min_order_amount'] : 0;
        $maxDiscountAmount = isset($body['max_discount_amount']) && $body['max_discount_amount'] !== '' ? (int) $body['max_discount_amount'] : null;
        $validFrom = trim((string) ($body['valid_from'] ?? ''));
        $validUntil = trim((string) ($body['valid_until'] ?? ''));
        $totalQuantity = isset($body['total_quantity']) && $body['total_quantity'] !== '' ? (int) $body['total_quantity'] : null;
        $isActive = isset($body['is_active']) ? (int) (bool) $body['is_active'] : 1;

        if ($code === '') {
            http_response_code(400);
            echo json_encode(['error' => '쿠폰 코드를 입력하세요.']);
            exit;
        }
        if ($name === '') {
            http_response_code(400);
            echo json_encode(['error' => '쿠폰명을 입력하세요.']);
            exit;
        }
        if ($validFrom === '' || $validUntil === '') {
            http_response_code(400);
            echo json_encode(['error' => '유효기간(시작/종료)을 입력하세요.']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO coupons (code, name, discount_type, discount_value, min_order_amount, max_discount_amount, valid_from, valid_until, total_quantity, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$code, $name, $discountType, $discountValue, $minOrderAmount, $maxDiscountAmount, $validFrom, $validUntil, $totalQuantity, $isActive]);
        echo json_encode(['id' => (int) $pdo->lastInsertId(), 'ok' => true]);
        exit;
    }

    if ($method === 'PUT' && $id > 0) {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $code = trim((string) ($body['code'] ?? ''));
        $name = trim((string) ($body['name'] ?? ''));
        $discountType = trim((string) ($body['discount_type'] ?? 'percent'));
        $discountValue = isset($body['discount_value']) ? (int) $body['discount_value'] : 0;
        $minOrderAmount = isset($body['min_order_amount']) ? (int) $body['min_order_amount'] : 0;
        $maxDiscountAmount = isset($body['max_discount_amount']) && $body['max_discount_amount'] !== '' ? (int) $body['max_discount_amount'] : null;
        $validFrom = trim((string) ($body['valid_from'] ?? ''));
        $validUntil = trim((string) ($body['valid_until'] ?? ''));
        $totalQuantity = isset($body['total_quantity']) && $body['total_quantity'] !== '' ? (int) $body['total_quantity'] : null;
        $isActive = isset($body['is_active']) ? (int) (bool) $body['is_active'] : 1;

        if ($code === '') {
            http_response_code(400);
            echo json_encode(['error' => '쿠폰 코드를 입력하세요.']);
            exit;
        }
        if ($name === '') {
            http_response_code(400);
            echo json_encode(['error' => '쿠폰명을 입력하세요.']);
            exit;
        }
        if ($validFrom === '' || $validUntil === '') {
            http_response_code(400);
            echo json_encode(['error' => '유효기간(시작/종료)을 입력하세요.']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE coupons SET code=?, name=?, discount_type=?, discount_value=?, min_order_amount=?, max_discount_amount=?, valid_from=?, valid_until=?, total_quantity=?, is_active=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$code, $name, $discountType, $discountValue, $minOrderAmount, $maxDiscountAmount, $validFrom, $validUntil, $totalQuantity, $isActive, $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($method === 'DELETE' && $id > 0) {
        $stmt = $pdo->prepare('DELETE FROM coupons WHERE id=?');
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
