<?php
require_once __DIR__ . '/../inc/auth.php';
requireAdmin();

$pageTitle = '찜';
$loadGrid = true;
require_once __DIR__ . '/../inc/header.php';
?>

<div class="admin-page">
    <div class="head"><h1>찜</h1></div>
    <p id="msg" class="msg" style="display:none;"></p>
    <div class="card admin-search-form">
        <form id="form-search" onsubmit="return false;">
            <div class="form-row">
                <div class="form-field">
                    <label for="search_product_name">상품명</label>
                    <input type="text" id="search_product_name" placeholder="상품명">
                </div>
            </div>
        </form>
    </div>
    <div class="admin-list-actions">
        <button type="button" class="btn btn-search" id="btn-search">검색</button>
        <button type="button" class="btn btn-delete" id="btn-delete">삭제</button>
    </div>
    <div class="card admin-grid-wrap" style="flex:1 1 0%; min-height:0; display:flex; flex-direction:column; padding:0.35rem;">
        <div id="wishlist-grid" data-ax5grid="wishlist-grid" style="height:100%; min-height:240px;"></div>
    </div>
</div>

<script>
(function() {
var apiBase = '/admin/wishlists/api.php';
var gridInstance = null;
var gridData = [];

function showMsg(text, isError) {
    var el = document.getElementById('msg');
    el.textContent = text;
    el.className = 'msg ' + (isError ? 'error' : 'success');
    el.style.display = 'block';
    setTimeout(function() { el.style.display = 'none'; }, 4000);
}

function loadList() {
    fetch(apiBase).then(function(res) {
        if (!res.ok) { showMsg('목록을 불러오지 못했습니다.', true); return res.json().then(function() {}); }
        return res.json();
    }).then(function(data) {
        if (!data) return;
        var list = Array.isArray(data.results) ? data.results : (Array.isArray(data) ? data : []);
        var search = (document.getElementById('search_product_name') || {}).value.trim().toLowerCase();
        if (search) list = list.filter(function(r) { return (r.product_name || '').toLowerCase().indexOf(search) >= 0; });
        gridData = list;
        if (gridInstance && typeof gridInstance.setData === 'function') gridInstance.setData(gridData);
    }).catch(function() { showMsg('목록을 불러오지 못했습니다.', true); });
}

function initGrid() {
    if (typeof ax5 === 'undefined' || !ax5.ui || !ax5.ui.grid) { setTimeout(initGrid, 100); return; }
    var $ = window.jQuery;
    gridInstance = new ax5.ui.grid();
    gridInstance.setConfig({
        target: $('[data-ax5grid="wishlist-grid"]'),
        showLineNumber: true,
        showRowSelector: true,
        multipleSelect: true,
        lineNumberColumnWidth: 36,
        rowSelectorColumnWidth: 28,
        header: { align: 'center', columnHeight: 28 },
        body: { align: 'left', columnHeight: 28 },
        columns: [
            { key: 'id', label: 'ID', width: 70, align: 'left' },
            { key: 'user_id', label: '회원ID', width: 80, align: 'right' },
            { key: 'product_id', label: '상품ID', width: 80, align: 'right' },
            { key: 'product_name', label: '상품명', width: 200, align: 'left' },
            { key: 'created_at', label: '찜한일시', width: 160, align: 'left' }
        ]
    });
    gridInstance.setData(Array.isArray(gridData) ? gridData : []);
}

document.getElementById('btn-search').addEventListener('click', loadList);
document.getElementById('btn-delete').addEventListener('click', function() {
    var selected = [];
    if (gridInstance && gridInstance.getList) {
        try { selected = gridInstance.getList('selected') || []; } catch (e) { selected = []; }
        if (!Array.isArray(selected)) selected = [];
    }
    if (!selected || selected.length === 0) { alert('삭제할 행을 선택하세요.'); return; }
    if (!confirm('선택한 ' + selected.length + '개를 삭제하시겠습니까?')) return;
    var done = 0, total = selected.length;
    selected.forEach(function(row) {
        fetch(apiBase + '?id=' + row.id, { method: 'DELETE' }).then(function(res) {
            done++;
            if (done === total) { showMsg('삭제되었습니다.'); loadList(); }
        });
    });
});

loadList();
initGrid();
})();
</script>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
