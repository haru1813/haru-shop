<?php
$pageTitle = $pageTitle ?? '관리자';
$req = $_SERVER['REQUEST_URI'] ?? '';
$isActive = function ($path) use ($req) {
    if ($path === '/admin/index.php' || $path === '/admin/') return ($req === '/admin/index.php' || rtrim($req, '/') === '/admin');
    return (strpos($req, $path) !== false);
};
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Haru Shop 관리자</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; min-height: 100vh; min-width: 100%; background: #f8fafc; }
        .admin-layout { min-height: 100vh; height: 100vh; display: flex; flex-direction: column; }
        .admin-navbar { margin: 0; padding: 0; width: 100%; position: sticky; top: 0; z-index: 100; background: #1e293b; color: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.15); box-sizing: border-box; }
        .admin-navbar-inner { margin: 0; padding: 0 0.5rem; display: flex; align-items: center; gap: 0.5rem; min-height: 44px; width: 100%; box-sizing: border-box; }
        .admin-navbar-brand { font-weight: 700; font-size: 1.125rem; color: #fff; text-decoration: none; white-space: nowrap; flex-shrink: 0; }
        .admin-navbar-brand:hover { color: #e2e8f0; }
        .admin-nav-links { display: flex; align-items: center; gap: 0; flex: 1; min-width: 0; padding: 0; margin: 0; overflow: visible; }
        .admin-nav-links::-webkit-scrollbar { height: 4px; }
        .admin-nav-links::-webkit-scrollbar-thumb { background: #475569; border-radius: 2px; }
        .admin-nav-links a { color: #94a3b8; text-decoration: none; padding: 0.5rem 0.625rem; border-radius: 4px; font-size: 0.875rem; white-space: nowrap; transition: color 0.15s, background 0.15s; }
        .admin-nav-links a:hover { color: #fff; background: #334155; }
        .admin-nav-links a.active { color: #fff; background: #334155; }
        .admin-navbar-end { flex-shrink: 0; }
        .admin-navbar-logout { padding: 0.4rem 0.75rem; margin: 0; background: transparent; color: #94a3b8; border: 1px solid #475569; border-radius: 4px; cursor: pointer; font-size: 0.875rem; text-decoration: none; display: inline-block; transition: color 0.15s, border-color 0.15s; }
        .admin-navbar-logout:hover { color: #fff; border-color: #64748b; }
        .admin-main { flex: 1; display: flex; flex-direction: column; min-height: 0; overflow: hidden; padding: 0 0.5rem 0.5rem 0.5rem; background: #f8fafc; }
        .admin-main-inner { flex: 1 1 0%; min-height: 0; display: flex; flex-direction: column; overflow: hidden; padding-top: 0.5rem; }
        .admin-main-inner > .admin-page { flex: 1 1 0%; min-height: 0; overflow: hidden; display: flex; flex-direction: column; }
        .admin-login { min-height: 60vh; display: flex; align-items: center; justify-content: center; }
        .admin-login .card { max-width: 360px; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .admin-login .card h1 { margin: 0 0 1.5rem; font-size: 1.25rem; }
        .admin-login .field label { display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #475569; }
        .admin-login .field input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem; box-sizing: border-box; }
        .admin-login .error { color: #dc2626; font-size: 0.875rem; margin-bottom: 0.5rem; }
        .admin-login button { width: 100%; padding: 0.75rem; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; margin-top: 0.5rem; }
        .admin-login button:disabled { opacity: 0.7; cursor: not-allowed; }
        .admin-page { display: flex; flex-direction: column; flex: 1 1 0%; min-height: 0; gap: 0.25rem; }
        .head { display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; margin-bottom: 0.15rem; }
        .head h1 { margin: 0; font-size: 1.15rem; }
        .card { background: #fff; border-radius: 6px; padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; flex-shrink: 0; box-shadow: 0 1px 2px rgba(0,0,0,0.06); }
        .admin-search-form { background: #fff; padding: 0.35rem 0.5rem; border-radius: 6px; border: 1px solid #e2e8f0; margin: 0; flex-shrink: 0; }
        .form-row { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: flex-end; }
        .form-field, .field { margin-bottom: 0.35rem; }
        .form-field:last-child, .field:last-child { margin-bottom: 0; }
        .form-field label, .field label { display: block; margin-bottom: 0.1rem; font-size: 0.8rem; color: #475569; }
        .form-field input, .form-field select, .field input { width: 100%; padding: 0.35rem 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.8125rem; min-width: 0; box-sizing: border-box; }
        .form-field input, .form-field select { min-width: 120px; }
        .admin-list-actions { display: flex; gap: 0.35rem; margin: 0; padding: 0.25rem 0; flex-shrink: 0; }
        .admin-list-actions .btn { padding: 0.35rem 0.65rem; border-radius: 6px; font-size: 0.8125rem; cursor: pointer; border: 1px solid #e2e8f0; background: #fff; color: #334155; text-decoration: none; }
        .admin-list-actions .btn:hover { background: #f8fafc; border-color: #cbd5e1; }
        .admin-list-actions .btn-search { background: #2563eb; color: #fff; border-color: #2563eb; }
        .admin-list-actions .btn-edit { background: #0ea5e9; color: #fff; border-color: #0ea5e9; }
        .admin-list-actions .btn-delete { background: #ef4444; color: #fff; border-color: #ef4444; }
        .admin-list-actions .btn-add { background: #22c55e; color: #fff; border-color: #22c55e; }
        .load-error { padding: 0.5rem 0.75rem; background: #fef2f2; color: #dc2626; border-radius: 6px; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; font-size: 0.8125rem; }
        .load-error .btn-retry { padding: 0.35rem 0.65rem; background: #dc2626; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 0.8125rem; }
        .loading { padding: 0.75rem; text-align: center; color: #64748b; flex-shrink: 0; font-size: 0.8125rem; }
        .admin-grid-wrap { flex: 1 1 0%; min-height: 200px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 6px; overflow: hidden; border: 1px solid #e2e8f0; font-size: 0.8125rem; }
        th, td { padding: 0.35rem 0.5rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-weight: 600; color: #334155; }
        tr:last-child td { border-bottom: none; }
        .admin-modal-form .field { margin-bottom: 1rem; }
        .admin-modal-form .field label { display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #475569; }
        .admin-modal-form .field input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; }
        .admin-modal-form .error { color: #dc2626; font-size: 0.875rem; margin-bottom: 0.5rem; }
        .admin-modal-form .form-actions { display: flex; gap: 0.5rem; margin-top: 1rem; }
        .admin-modal-form .form-actions button { padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-size: 0.875rem; }
        .admin-modal-form .form-actions button[type="submit"] { background: #2563eb; color: #fff; border: none; }
        .admin-modal-form .form-actions button[type="submit"]:disabled { opacity: 0.7; }
        .admin-modal-form .btn-cancel { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .msg { padding: 0.35rem 0.5rem; border-radius: 6px; margin-bottom: 0.25rem; font-size: 0.8125rem; }
        .msg.success { background: #dcfce7; color: #166534; }
        .msg.error { background: #fef2f2; color: #dc2626; }
        .btn { padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.875rem; cursor: pointer; border: 1px solid #e2e8f0; background: #fff; color: #334155; }
        .btn-primary { background: #2563eb; color: #fff; border-color: #2563eb; }
        .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-danger { background: #ef4444; color: #fff; border-color: #ef4444; }
        .dashboard h1 { margin: 0 0 0.5rem; font-size: 1.15rem; }
        .dashboard .cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.5rem; }
        .dashboard .card { background: #fff; padding: 0.5rem 0.75rem; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.06); display: flex; flex-direction: column; gap: 0.25rem; }
        .dashboard .card .label { font-size: 0.8rem; color: #64748b; }
        .dashboard .card .count { font-size: 1.25rem; font-weight: 600; }
        .dashboard .card.card-link { text-decoration: none; color: inherit; transition: box-shadow 0.15s; }
        .dashboard .card.card-link:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.12); }
        .admin-grid-wrap .ax5grid-body-table { font-size: 0.8125rem; }
        .admin-modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 1000; align-items: center; justify-content: center; }
        .admin-modal-overlay.is-open { display: flex; }
        .admin-modal-box { background: #fff; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); min-width: 360px; max-width: 680px; width: 90vw; max-height: 90vh; overflow: auto; }
        .admin-modal-title { margin: 0; padding: 0.75rem 1rem; font-size: 1rem; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
        .admin-modal-body { padding: 1rem; }
        .admin-modal-form .field { margin-bottom: 0.75rem; }
        .admin-modal-form .field label { display: block; margin-bottom: 0.2rem; font-size: 0.8125rem; color: #475569; }
        .admin-modal-form .field input,
        .admin-modal-form .field select,
        .admin-modal-form .field textarea { width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.875rem; box-sizing: border-box; font-family: inherit; }
        .admin-modal-form .field-checkbox { display: flex; align-items: center; gap: 0.5rem; }
        .admin-modal-form .field-checkbox input[type="checkbox"] { width: auto; flex: 0 0 auto; }
        .admin-modal-form .field-checkbox label { margin: 0; flex: 0 0 auto; cursor: pointer; }
        .admin-modal-form .field-label { display: block; margin-bottom: 0.35rem; font-size: 0.8125rem; color: #475569; }
        .admin-modal-form .option-colors { display: flex; flex-wrap: wrap; gap: 0.75rem 1rem; }
        .admin-modal-form .option-check { display: inline-flex; align-items: center; gap: 0.35rem; margin: 0; cursor: pointer; font-size: 0.875rem; }
        .admin-modal-form .option-check input { width: auto; }
        .option-groups-header { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.35rem; }
        .option-groups-header .field-label { margin: 0; }
        .option-groups-container { display: flex; flex-direction: column; gap: 0.75rem; max-height: 280px; overflow-y: auto; }
        .option-group-card { border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.5rem 0.75rem; background: #f8fafc; }
        .option-group-head { display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem; margin-bottom: 0.5rem; }
        .option-group-head .option-group-name { width: 120px; min-width: 80px; padding: 0.3rem 0.5rem; font-size: 0.8125rem; border: 1px solid #e2e8f0; border-radius: 4px; }
        .option-group-head .option-group-type { width: 90px; padding: 0.3rem 0.5rem; font-size: 0.8125rem; border: 1px solid #e2e8f0; border-radius: 4px; }
        .option-group-head .option-group-key { width: 90px; min-width: 60px; padding: 0.3rem 0.5rem; font-size: 0.8125rem; border: 1px solid #e2e8f0; border-radius: 4px; }
        .option-group-required { display: inline-flex; align-items: center; gap: 0.25rem; margin: 0; font-size: 0.8rem; color: #475569; cursor: pointer; }
        .option-group-required input { width: auto; }
        .option-items-list { display: flex; flex-direction: column; gap: 0.35rem; margin-bottom: 0.35rem; }
        .option-item-row { display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap; }
        .option-item-row .item-name { width: 100px; min-width: 60px; padding: 0.3rem 0.5rem; font-size: 0.8125rem; border: 1px solid #e2e8f0; border-radius: 4px; }
        .option-item-row .item-value { width: 90px; min-width: 50px; padding: 0.3rem 0.5rem; font-size: 0.8125rem; border: 1px solid #e2e8f0; border-radius: 4px; }
        .option-item-row .item-price { width: 70px; padding: 0.3rem 0.5rem; font-size: 0.8125rem; border: 1px solid #e2e8f0; border-radius: 4px; }
        .btn-small { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
        .btn-remove-group, .btn-remove-item { color: #64748b; border-color: #cbd5e1; }
        .btn-remove-group:hover, .btn-remove-item:hover { color: #dc2626; border-color: #fca5a5; }
        .admin-modal-form .error { color: #dc2626; font-size: 0.8125rem; margin-bottom: 0.5rem; }
        .admin-modal-actions { display: flex; gap: 0.5rem; justify-content: flex-end; padding: 0.75rem 1rem; border-top: 1px solid #e2e8f0; }
        .admin-modal-actions .btn { padding: 0.4rem 0.75rem; font-size: 0.8125rem; }
        .admin-nav-dropdown { position: relative; display: inline-block; }
        .admin-nav-dropdown-btn { color: #94a3b8; background: transparent; border: none; padding: 0.5rem 0.625rem; border-radius: 4px; font-size: 0.875rem; cursor: pointer; display: flex; align-items: center; gap: 0.25rem; }
        .admin-nav-dropdown-btn:hover { color: #fff; background: #334155; }
        .admin-nav-dropdown-btn.active { color: #fff; background: #334155; }
        .admin-nav-dropdown-content { display: none; position: absolute; top: 100%; left: 0; min-width: 180px; background: #1e293b; border: 1px solid #334155; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 1200; margin-top: 2px; padding: 0.25rem 0; }
        .admin-nav-dropdown:hover .admin-nav-dropdown-content,
        .admin-nav-dropdown.is-open .admin-nav-dropdown-content { display: block; }
        .admin-nav-dropdown-content a { display: block; color: #94a3b8; padding: 0.4rem 0.75rem; font-size: 0.8125rem; text-decoration: none; white-space: nowrap; }
        .admin-nav-dropdown-content a:hover { color: #fff; background: #334155; }
        .admin-nav-dropdown-content a.active { color: #fff; background: #334155; }
    </style>
<?php if (!empty($loadGrid)): ?>
    <link rel="stylesheet" href="https://unpkg.com/ax5ui-grid@1.4.126/dist/ax5grid.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/ax5core@1.4.126/dist/ax5core.min.js"></script>
    <script src="https://unpkg.com/ax5ui-grid@1.4.126/dist/ax5grid.min.js"></script>
<?php endif; ?>
</head>
<body>
<div class="admin-layout">
    <header class="admin-navbar">
        <div class="admin-navbar-inner">
            <a href="/admin/index.php" class="admin-navbar-brand">Haru Shop 관리자</a>
            <nav class="admin-nav-links">
                <a href="/admin/index.php" class="<?= $isActive('/admin/index.php') ? 'active' : '' ?>">대시보드</a>
                <div class="admin-nav-dropdown">
                    <button type="button" class="admin-nav-dropdown-btn <?= ($isActive('/admin/categories') || $isActive('/admin/products')) ? 'active' : '' ?>">상품·카테고리 ▾</button>
                    <div class="admin-nav-dropdown-content">
                        <a href="/admin/categories/view.php" class="<?= $isActive('/admin/categories') ? 'active' : '' ?>">카테고리</a>
                        <a href="/admin/products/view.php" class="<?= $isActive('/admin/products') ? 'active' : '' ?>">상품</a>
                    </div>
                </div>
                <div class="admin-nav-dropdown">
                    <button type="button" class="admin-nav-dropdown-btn <?= ($isActive('/admin/delivery_fee_templates') || $isActive('/admin/address_codes')) ? 'active' : '' ?>">배송·물류 ▾</button>
                    <div class="admin-nav-dropdown-content">
                        <a href="/admin/delivery_fee_templates/view.php" class="<?= $isActive('/admin/delivery_fee_templates') ? 'active' : '' ?>">배송비 템플릿</a>
                        <a href="/admin/address_codes/view.php" class="<?= $isActive('/admin/address_codes') ? 'active' : '' ?>">주소 코드</a>
                    </div>
                </div>
                <div class="admin-nav-dropdown">
                    <button type="button" class="admin-nav-dropdown-btn <?= $isActive('/admin/banners') ? 'active' : '' ?>">콘텐츠 ▾</button>
                    <div class="admin-nav-dropdown-content">
                        <a href="/admin/banners/view.php" class="<?= $isActive('/admin/banners') ? 'active' : '' ?>">배너</a>
                    </div>
                </div>
                <a href="/admin/orders/view.php" class="<?= $isActive('/admin/orders') ? 'active' : '' ?>">주문</a>
                <div class="admin-nav-dropdown">
                    <button type="button" class="admin-nav-dropdown-btn <?= ($isActive('/admin/coupons') || $isActive('/admin/users') || $isActive('/admin/cart_items') || $isActive('/admin/wishlists') || $isActive('/admin/reviews') || $isActive('/admin/inquiries')) ? 'active' : '' ?>">마이페이지 ▾</button>
                    <div class="admin-nav-dropdown-content">
                        <a href="/admin/coupons/view.php" class="<?= $isActive('/admin/coupons') ? 'active' : '' ?>">쿠폰</a>
                        <a href="/admin/users/view.php" class="<?= $isActive('/admin/users') ? 'active' : '' ?>">사용자</a>
                        <a href="/admin/cart_items/view.php" class="<?= $isActive('/admin/cart_items') ? 'active' : '' ?>">장바구니</a>
                        <a href="/admin/wishlists/view.php" class="<?= $isActive('/admin/wishlists') ? 'active' : '' ?>">찜</a>
                        <a href="/admin/reviews/view.php" class="<?= $isActive('/admin/reviews') ? 'active' : '' ?>">리뷰</a>
                        <a href="/admin/inquiries/view.php" class="<?= $isActive('/admin/inquiries') ? 'active' : '' ?>">문의</a>
                    </div>
                </div>
            </nav>
            <div class="admin-navbar-end">
                <a href="/admin/login/view.php?logout=1" class="admin-navbar-logout">로그아웃</a>
            </div>
        </div>
    </header>
    <script>
    (function() {
        document.querySelectorAll('.admin-nav-dropdown-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var dropdown = this.closest('.admin-nav-dropdown');
                var wasOpen = dropdown.classList.contains('is-open');
                document.querySelectorAll('.admin-nav-dropdown').forEach(function(d) { d.classList.remove('is-open'); });
                if (!wasOpen) dropdown.classList.add('is-open');
            });
        });
        document.addEventListener('click', function() {
            document.querySelectorAll('.admin-nav-dropdown').forEach(function(d) { d.classList.remove('is-open'); });
        });
        document.querySelectorAll('.admin-nav-dropdown-content').forEach(function(panel) {
            panel.addEventListener('click', function(e) { e.stopPropagation(); });
        });
    })();
    </script>
    <main class="admin-main">
        <div class="admin-main-inner">
