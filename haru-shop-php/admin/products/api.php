<?php
/**
 * 상품 API
 * GET: 목록 (카테고리명 포함) | GET ?id=X: 단건 | POST: 생성 | PUT ?id=X: 수정 | DELETE ?id=X: 삭제
 * DB 테이블: products
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

requireAdmin();

/**
 * option_groups: [ { name, option_type, option_key?, is_required, sort_order, items: [ { name, value?, option_price } ] } ]
 * combination 타입만 option_key 사용, 조합으로 product_skus 생성
 */
function saveOptionGroups(PDO $pdo, $productId, array $optionGroups) {
    $productId = (int) $productId;
    $combinationMasters = []; // option_key => [ [value, option_price], ... ]
    $sortOrderMaster = 0;
    foreach ($optionGroups as $g) {
        $name = trim((string) ($g['name'] ?? ''));
        if ($name === '') continue;
        $optionType = in_array($g['option_type'] ?? '', ['simple', 'combination', 'text'], true) ? $g['option_type'] : 'combination';
        $optionKey = $optionType === 'combination' ? trim((string) ($g['option_key'] ?? '')) : null;
        if ($optionType === 'combination' && $optionKey === '') $optionKey = preg_replace('/\s+/', '_', strtolower($name));
        $isRequired = isset($g['is_required']) ? (int) (bool) $g['is_required'] : 0;
        $items = isset($g['items']) && is_array($g['items']) ? $g['items'] : [];

        $stmt = $pdo->prepare('INSERT INTO option_masters (product_id, name, option_type, option_key, is_required, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$productId, $name, $optionType, $optionKey, $isRequired, $sortOrderMaster++]);
        $masterId = (int) $pdo->lastInsertId();

        $itemSort = 0;
        $valuesForSku = [];
        foreach ($items as $it) {
            $itemName = trim((string) ($it['name'] ?? ''));
            if ($itemName === '') continue;
            $itemValue = isset($it['value']) ? trim((string) $it['value']) : $itemName;
            if ($itemValue === '') $itemValue = $itemName;
            $optionPrice = isset($it['option_price']) ? (int) $it['option_price'] : 0;
            $pdo->prepare('INSERT INTO option_items (option_master_id, name, value, option_price, sort_order) VALUES (?, ?, ?, ?, ?)')->execute([$masterId, $itemName, $itemValue, $optionPrice, $itemSort++]);
            if ($optionType === 'combination' && $optionKey !== null) {
                $valuesForSku[] = ['key' => $optionKey . ':' . $itemValue, 'option_price' => $optionPrice];
            }
        }
        if ($optionType === 'combination' && $optionKey !== null && !empty($valuesForSku)) {
            $combinationMasters[] = $valuesForSku;
        }
    }
    if (empty($combinationMasters)) return;
    $cartesian = [[]];
    foreach ($combinationMasters as $values) {
        $next = [];
        foreach ($cartesian as $prefix) {
            foreach ($values as $v) {
                $next[] = array_merge($prefix, [$v]);
            }
        }
        $cartesian = $next;
    }
    $stmtSku = $pdo->prepare('INSERT INTO product_skus (product_id, option_key, option_price, stock, sell_status) VALUES (?, ?, ?, ?, ?)');
    foreach ($cartesian as $combo) {
        $parts = [];
        $totalOptionPrice = 0;
        foreach ($combo as $v) {
            $parts[] = $v['key'];
            $totalOptionPrice += $v['option_price'];
        }
        $optionKey = implode(',', $parts);
        $stmtSku->execute([$productId, $optionKey, $totalOptionPrice, 15, 'on_sale']);
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

try {
    if ($method === 'GET') {
        if ($id > 0) {
            $stmt = $pdo->prepare("
                SELECT p.id, p.category_id, p.name, p.slug, p.price, p.description, p.image_url,
                       p.stock, p.is_active, p.delivery_fee_template_id, p.created_at, p.updated_at,
                       c.name AS category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['error' => '상품을 찾을 수 없습니다.']);
                exit;
            }
            $stmt2 = $pdo->prepare("
                SELECT om.id, om.name, om.option_type, om.option_key, om.is_required, om.sort_order
                FROM option_masters om WHERE om.product_id = ? ORDER BY om.sort_order, om.id
            ");
            $stmt2->execute([$id]);
            $masters = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            foreach ($masters as &$m) {
                $m['id'] = (int) $m['id'];
                $m['is_required'] = (int) $m['is_required'];
                $m['sort_order'] = (int) $m['sort_order'];
                $stmt3 = $pdo->prepare("SELECT id, name, value, option_price, sort_order FROM option_items WHERE option_master_id = ? ORDER BY sort_order, id");
                $stmt3->execute([$m['id']]);
                $m['items'] = $stmt3->fetchAll(PDO::FETCH_ASSOC);
                foreach ($m['items'] as &$it) {
                    $it['id'] = (int) $it['id'];
                    $it['option_price'] = (int) $it['option_price'];
                    $it['sort_order'] = (int) $it['sort_order'];
                }
            }
            $row['option_groups'] = $masters;
            echo json_encode($row);
            exit;
        }

        $stmt = $pdo->query("
            SELECT p.id, p.name, p.slug, p.price, p.stock, p.is_active, p.delivery_fee_template_id, p.created_at,
                   c.name AS category_name,
                   d.name AS delivery_fee_template_name,
                   (SELECT GROUP_CONCAT(
                      CONCAT(
                          om.name,
                          '[',
                          CASE om.option_type WHEN 'simple' THEN '단독형' WHEN 'combination' THEN '조합형' WHEN 'text' THEN '직접입력형' ELSE om.option_type END,
                          '](',
                          COALESCE((SELECT GROUP_CONCAT(oi.name ORDER BY oi.sort_order) FROM option_items oi WHERE oi.option_master_id = om.id), '-'),
                          ')'
                      )
                      ORDER BY om.sort_order, om.id
                      SEPARATOR ', '
                   ) FROM option_masters om WHERE om.product_id = p.id) AS option_summary
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN delivery_fee_templates d ON p.delivery_fee_template_id = d.id
            ORDER BY p.created_at DESC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            if ($r['option_summary'] === null || $r['option_summary'] === '') {
                $r['option_summary'] = '-';
            }
        }
        echo json_encode(['results' => $rows]);
        exit;
    }

    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $categoryId = isset($body['category_id']) ? (int) $body['category_id'] : 0;
        $name = trim((string) ($body['name'] ?? ''));
        $slug = trim((string) ($body['slug'] ?? ''));
        $price = isset($body['price']) ? (int) $body['price'] : 0;
        $description = trim((string) ($body['description'] ?? ''));
        $imageUrl = trim((string) ($body['image_url'] ?? ''));
        $stock = isset($body['stock']) ? (int) $body['stock'] : 0;
        $isActive = isset($body['is_active']) ? (int) (bool) $body['is_active'] : 1;
        $deliveryFeeTemplateId = isset($body['delivery_fee_template_id']) && $body['delivery_fee_template_id'] !== '' ? (int) $body['delivery_fee_template_id'] : null;

        if ($name === '') {
            http_response_code(400);
            echo json_encode(['error' => '상품명을 입력하세요.']);
            exit;
        }
        if ($categoryId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => '카테고리를 선택하세요.']);
            exit;
        }
        if ($price < 0) {
            http_response_code(400);
            echo json_encode(['error' => '가격은 0 이상이어야 합니다.']);
            exit;
        }
        if ($slug === '') {
            $slug = preg_replace('/\s+/', '-', strtolower($name));
        }

        $stmt = $pdo->prepare('INSERT INTO products (category_id, name, slug, price, description, image_url, stock, is_active, delivery_fee_template_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$categoryId, $name, $slug, $price, $description ?: null, $imageUrl ?: null, $stock, $isActive, $deliveryFeeTemplateId]);
        $productId = (int) $pdo->lastInsertId();
        $optionGroups = isset($body['option_groups']) && is_array($body['option_groups']) ? $body['option_groups'] : [];
        saveOptionGroups($pdo, $productId, $optionGroups);
        echo json_encode(['id' => $productId, 'ok' => true]);
        exit;
    }

    if ($method === 'PUT' && $id > 0) {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $categoryId = isset($body['category_id']) ? (int) $body['category_id'] : 0;
        $name = trim((string) ($body['name'] ?? ''));
        $slug = trim((string) ($body['slug'] ?? ''));
        $price = isset($body['price']) ? (int) $body['price'] : 0;
        $description = trim((string) ($body['description'] ?? ''));
        $imageUrl = trim((string) ($body['image_url'] ?? ''));
        $stock = isset($body['stock']) ? (int) $body['stock'] : 0;
        $isActive = isset($body['is_active']) ? (int) (bool) $body['is_active'] : 1;
        $deliveryFeeTemplateId = isset($body['delivery_fee_template_id']) && $body['delivery_fee_template_id'] !== '' ? (int) $body['delivery_fee_template_id'] : null;

        if ($name === '') {
            http_response_code(400);
            echo json_encode(['error' => '상품명을 입력하세요.']);
            exit;
        }
        if ($categoryId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => '카테고리를 선택하세요.']);
            exit;
        }
        if ($price < 0) {
            http_response_code(400);
            echo json_encode(['error' => '가격은 0 이상이어야 합니다.']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE products SET category_id=?, name=?, slug=?, price=?, description=?, image_url=?, stock=?, is_active=?, delivery_fee_template_id=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$categoryId, $name, $slug, $price, $description ?: null, $imageUrl ?: null, $stock, $isActive, $deliveryFeeTemplateId, $id]);
        $optionGroups = isset($body['option_groups']) && is_array($body['option_groups']) ? $body['option_groups'] : [];
        $pdo->prepare('DELETE FROM product_skus WHERE product_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM option_masters WHERE product_id = ?')->execute([$id]);
        saveOptionGroups($pdo, $id, $optionGroups);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($method === 'DELETE' && $id > 0) {
        $stmt = $pdo->prepare('DELETE FROM products WHERE id=?');
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
