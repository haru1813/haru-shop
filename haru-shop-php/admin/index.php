<?php
require_once __DIR__ . '/inc/auth.php';
requireAdmin();

$pageTitle = '대시보드';

$count = function ($pdo, $table) {
    try {
        return (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
};

$count_categories = 0;
$count_products = 0;
$count_orders = 0;
$count_banners = 0;
$count_coupons = 0;
$count_users = 0;
$count_delivery_templates = 0;
$count_address_codes = 0;
try {
    $pdo = require __DIR__ . '/inc/db.php';
    $count_categories = $count($pdo, 'categories');
    $count_products = $count($pdo, 'products');
    $count_orders = $count($pdo, 'orders');
    $count_banners = $count($pdo, 'banners');
    $count_coupons = $count($pdo, 'coupons');
    $count_users = $count($pdo, 'users');
    $count_delivery_templates = $count($pdo, 'delivery_fee_templates');
    $count_address_codes = $count($pdo, 'address_codes');
} catch (Throwable $e) { /* ignore */ }

require_once __DIR__ . '/inc/header.php';
?>

<div class="admin-page">
    <div class="dashboard">
        <h1>대시보드</h1>
        <div class="cards">
            <a href="/admin/categories/view.php" class="card card-link">
                <span class="label">카테고리</span>
                <span class="count"><?= (int) $count_categories ?></span>
            </a>
            <a href="/admin/products/view.php" class="card card-link">
                <span class="label">상품</span>
                <span class="count"><?= (int) $count_products ?></span>
            </a>
            <a href="/admin/delivery_fee_templates/view.php" class="card card-link">
                <span class="label">배송비 템플릿</span>
                <span class="count"><?= (int) $count_delivery_templates ?></span>
            </a>
            <a href="/admin/address_codes/view.php" class="card card-link">
                <span class="label">주소 코드</span>
                <span class="count"><?= (int) $count_address_codes ?></span>
            </a>
            <a href="/admin/banners/view.php" class="card card-link">
                <span class="label">배너</span>
                <span class="count"><?= (int) $count_banners ?></span>
            </a>
            <a href="/admin/orders/view.php" class="card card-link">
                <span class="label">주문</span>
                <span class="count"><?= (int) $count_orders ?></span>
            </a>
            <a href="/admin/coupons/view.php" class="card card-link">
                <span class="label">쿠폰</span>
                <span class="count"><?= (int) $count_coupons ?></span>
            </a>
            <a href="/admin/users/view.php" class="card card-link">
                <span class="label">사용자</span>
                <span class="count"><?= (int) $count_users ?></span>
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
