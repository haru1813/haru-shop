<?php
/**
 * 주소 코드 API
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
            $stmt = $pdo->prepare("SELECT id, code, address_type, name, recipient_name, phone, postal_code, address, address_detail, is_active, created_at, updated_at FROM address_codes WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['error' => '주소 코드를 찾을 수 없습니다.']);
                exit;
            }
            echo json_encode($row);
            exit;
        }
        $stmt = $pdo->query("SELECT id, code, address_type, name, recipient_name, phone, address, is_active, created_at FROM address_codes ORDER BY address_type, id");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['results' => $rows]);
        exit;
    }

    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $code = trim((string) ($body['code'] ?? ''));
        $addressType = trim((string) ($body['address_type'] ?? 'warehouse'));
        $name = trim((string) ($body['name'] ?? ''));
        $recipientName = trim((string) ($body['recipient_name'] ?? ''));
        $phone = trim((string) ($body['phone'] ?? ''));
        $postalCode = trim((string) ($body['postal_code'] ?? ''));
        $address = trim((string) ($body['address'] ?? ''));
        $addressDetail = trim((string) ($body['address_detail'] ?? ''));
        $isActive = isset($body['is_active']) ? (int) (bool) $body['is_active'] : 1;

        if ($code === '') {
            http_response_code(400);
            echo json_encode(['error' => '주소 코드를 입력하세요.']);
            exit;
        }
        if ($address === '') {
            http_response_code(400);
            echo json_encode(['error' => '기본 주소를 입력하세요.']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO address_codes (code, address_type, name, recipient_name, phone, postal_code, address, address_detail, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$code, $addressType, $name, $recipientName ?: null, $phone ?: null, $postalCode ?: null, $address, $addressDetail ?: null, $isActive]);
        echo json_encode(['id' => (int) $pdo->lastInsertId(), 'ok' => true]);
        exit;
    }

    if ($method === 'PUT' && $id > 0) {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $code = trim((string) ($body['code'] ?? ''));
        $addressType = trim((string) ($body['address_type'] ?? 'warehouse'));
        $name = trim((string) ($body['name'] ?? ''));
        $recipientName = trim((string) ($body['recipient_name'] ?? ''));
        $phone = trim((string) ($body['phone'] ?? ''));
        $postalCode = trim((string) ($body['postal_code'] ?? ''));
        $address = trim((string) ($body['address'] ?? ''));
        $addressDetail = trim((string) ($body['address_detail'] ?? ''));
        $isActive = isset($body['is_active']) ? (int) (bool) $body['is_active'] : 1;

        if ($code === '') {
            http_response_code(400);
            echo json_encode(['error' => '주소 코드를 입력하세요.']);
            exit;
        }
        if ($address === '') {
            http_response_code(400);
            echo json_encode(['error' => '기본 주소를 입력하세요.']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE address_codes SET code=?, address_type=?, name=?, recipient_name=?, phone=?, postal_code=?, address=?, address_detail=?, is_active=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$code, $addressType, $name, $recipientName ?: null, $phone ?: null, $postalCode ?: null, $address, $addressDetail ?: null, $isActive, $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($method === 'DELETE' && $id > 0) {
        $stmt = $pdo->prepare('DELETE FROM address_codes WHERE id=?');
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
