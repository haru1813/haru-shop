<?php
/**
 * 배송비 템플릿 API
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
            $stmt = $pdo->prepare("SELECT id, name, fee_type, base_fee, free_over_amount, fee_per_quantity, quantity_unit, shipping_method, external_id, sort_order, is_active, created_at, updated_at FROM delivery_fee_templates WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['error' => '템플릿을 찾을 수 없습니다.']);
                exit;
            }
            echo json_encode($row);
            exit;
        }
        $stmt = $pdo->query("SELECT id, name, fee_type, base_fee, free_over_amount, sort_order, is_active, created_at FROM delivery_fee_templates ORDER BY sort_order, id");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['results' => $rows]);
        exit;
    }

    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $name = trim((string) ($body['name'] ?? ''));
        $feeType = trim((string) ($body['fee_type'] ?? 'paid'));
        $baseFee = isset($body['base_fee']) ? (int) $body['base_fee'] : 0;
        $freeOverAmount = isset($body['free_over_amount']) && $body['free_over_amount'] !== '' ? (int) $body['free_over_amount'] : null;
        $feePerQuantity = isset($body['fee_per_quantity']) && $body['fee_per_quantity'] !== '' ? (int) $body['fee_per_quantity'] : null;
        $quantityUnit = isset($body['quantity_unit']) && $body['quantity_unit'] !== '' ? (int) $body['quantity_unit'] : null;
        $shippingMethod = trim((string) ($body['shipping_method'] ?? 'parcel'));
        $externalId = trim((string) ($body['external_id'] ?? ''));
        $sortOrder = isset($body['sort_order']) ? (int) $body['sort_order'] : 0;
        $isActive = isset($body['is_active']) ? (int) (bool) $body['is_active'] : 1;

        if ($name === '') {
            http_response_code(400);
            echo json_encode(['error' => '템플릿명을 입력하세요.']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO delivery_fee_templates (name, fee_type, base_fee, free_over_amount, fee_per_quantity, quantity_unit, shipping_method, external_id, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $feeType, $baseFee, $freeOverAmount, $feePerQuantity, $quantityUnit, $shippingMethod, $externalId ?: null, $sortOrder, $isActive]);
        echo json_encode(['id' => (int) $pdo->lastInsertId(), 'ok' => true]);
        exit;
    }

    if ($method === 'PUT' && $id > 0) {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $name = trim((string) ($body['name'] ?? ''));
        $feeType = trim((string) ($body['fee_type'] ?? 'paid'));
        $baseFee = isset($body['base_fee']) ? (int) $body['base_fee'] : 0;
        $freeOverAmount = isset($body['free_over_amount']) && $body['free_over_amount'] !== '' ? (int) $body['free_over_amount'] : null;
        $feePerQuantity = isset($body['fee_per_quantity']) && $body['fee_per_quantity'] !== '' ? (int) $body['fee_per_quantity'] : null;
        $quantityUnit = isset($body['quantity_unit']) && $body['quantity_unit'] !== '' ? (int) $body['quantity_unit'] : null;
        $shippingMethod = trim((string) ($body['shipping_method'] ?? 'parcel'));
        $externalId = trim((string) ($body['external_id'] ?? ''));
        $sortOrder = isset($body['sort_order']) ? (int) $body['sort_order'] : 0;
        $isActive = isset($body['is_active']) ? (int) (bool) $body['is_active'] : 1;

        if ($name === '') {
            http_response_code(400);
            echo json_encode(['error' => '템플릿명을 입력하세요.']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE delivery_fee_templates SET name=?, fee_type=?, base_fee=?, free_over_amount=?, fee_per_quantity=?, quantity_unit=?, shipping_method=?, external_id=?, sort_order=?, is_active=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$name, $feeType, $baseFee, $freeOverAmount, $feePerQuantity, $quantityUnit, $shippingMethod, $externalId ?: null, $sortOrder, $isActive, $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($method === 'DELETE' && $id > 0) {
        $stmt = $pdo->prepare('DELETE FROM delivery_fee_templates WHERE id=?');
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
