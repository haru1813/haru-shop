<?php
require_once __DIR__ . '/../inc/auth.php';
requireAdmin();

$pageTitle = '주문';
$loadGrid = true;
require_once __DIR__ . '/../inc/header.php';
?>

<div class="admin-page">
    <div class="head"><h1>주문</h1></div>
    <p id="msg" class="msg" style="display:none;"></p>
    <div class="card admin-search-form">
        <form id="form-search" onsubmit="return false;">
            <div class="form-row">
                <div class="form-field">
                    <label for="search_order_number">주문번호</label>
                    <input type="text" id="search_order_number" placeholder="주문번호">
                </div>
            </div>
        </form>
    </div>
    <div class="admin-list-actions">
        <button type="button" class="btn btn-search" id="btn-search">검색</button>
        <button type="button" class="btn btn-edit" id="btn-edit">상태 수정</button>
    </div>
    <div class="card admin-grid-wrap" style="flex:1 1 0%; min-height:0; display:flex; flex-direction:column; padding:0.35rem;">
        <div id="order-grid" data-ax5grid="order-grid" style="height:100%; min-height:240px;"></div>
    </div>
</div>

<div id="order-modal" class="admin-modal-overlay" role="dialog" aria-modal="true">
    <div class="admin-modal-box" onclick="event.stopPropagation()">
        <h2 class="admin-modal-title">주문 상태 수정</h2>
        <div class="admin-modal-body">
            <form id="form-order" class="admin-modal-form">
                <div class="field">
                    <label for="modal-status">상태</label>
                    <select id="modal-status" name="status">
                        <option value="payment_complete">결제완료 (payment_complete)</option>
                        <option value="preparing">배송준비중 (preparing)</option>
                        <option value="shipping">배송중 (shipping)</option>
                        <option value="delivered">배송완료 (delivered)</option>
                    </select>
                </div>
                <p id="order-modal-error" class="error" style="display:none;"></p>
                <div class="admin-modal-actions">
                    <button type="button" class="btn btn-secondary" id="order-modal-cancel">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
var apiBase = '/admin/orders/api.php';
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
        var search = (document.getElementById('search_order_number') || {}).value.trim().toLowerCase();
        if (search) list = list.filter(function(r) { return (r.order_number || '').toLowerCase().indexOf(search) >= 0; });
        gridData = list;
        if (gridInstance && typeof gridInstance.setData === 'function') gridInstance.setData(gridData);
    }).catch(function() { showMsg('목록을 불러오지 못했습니다.', true); });
}

function initGrid() {
    if (typeof ax5 === 'undefined' || !ax5.ui || !ax5.ui.grid) { setTimeout(initGrid, 100); return; }
    var $ = window.jQuery;
    gridInstance = new ax5.ui.grid();
    gridInstance.setConfig({
        target: $('[data-ax5grid="order-grid"]'),
        showLineNumber: true,
        showRowSelector: true,
        multipleSelect: true,
        lineNumberColumnWidth: 36,
        rowSelectorColumnWidth: 28,
        header: { align: 'center', columnHeight: 28 },
        body: { align: 'left', columnHeight: 28 },
        columns: [
            { key: 'id', label: 'ID', width: 70, align: 'left' },
            { key: 'order_number', label: '주문번호', width: 140, align: 'left' },
            { key: 'user_id', label: '회원ID', width: 80, align: 'right' },
            { key: 'status', label: '상태', width: 120, align: 'left' },
            { key: 'total_amount', label: '상품합계', width: 100, align: 'right' },
            { key: 'delivery_fee', label: '배송비', width: 80, align: 'right' },
            { key: 'receiver_name', label: '수령인', width: 90, align: 'left' },
            { key: 'created_at', label: '주문일', width: 160, align: 'left' }
        ]
    });
    gridInstance.setData(Array.isArray(gridData) ? gridData : []);
}

var modalEl = document.getElementById('order-modal');
var formEl = document.getElementById('form-order');

function openModal() { if (modalEl) modalEl.classList.add('is-open'); }
function closeModal() { if (modalEl) modalEl.classList.remove('is-open'); }

document.getElementById('btn-search').addEventListener('click', loadList);
document.getElementById('btn-edit').addEventListener('click', function() {
    var selected = [];
    if (gridInstance && gridInstance.getList) {
        try { selected = gridInstance.getList('selected') || []; } catch (e) { selected = []; }
        if (!Array.isArray(selected)) selected = [];
    }
    if (!selected || selected.length !== 1) {
        alert(selected && selected.length > 1 ? '한 건만 선택하세요.' : '수정할 주문을 선택하세요.');
        return;
    }
    document.getElementById('modal-status').value = selected[0].status || 'payment_complete';
    formEl.dataset.editId = selected[0].id;
    document.getElementById('order-modal-error').style.display = 'none';
    openModal();
});

document.getElementById('order-modal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
document.getElementById('order-modal-cancel').addEventListener('click', closeModal);

formEl.addEventListener('submit', function(e) {
    e.preventDefault();
    var errEl = document.getElementById('order-modal-error');
    errEl.style.display = 'none';
    var editId = formEl.dataset.editId || '';
    if (!editId) return;
    var body = { status: document.getElementById('modal-status').value };
    fetch(apiBase + '?id=' + editId, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
        .then(function(res) { return res.json().then(function(data) { return { res: res, data: data }; }); })
        .then(function(r) {
            if (r.res.ok) {
                showMsg('수정되었습니다.');
                closeModal();
                formEl.dataset.editId = '';
                loadList();
            } else {
                errEl.textContent = (r.data && r.data.error) || '저장 실패';
                errEl.style.display = 'block';
            }
        });
});

loadList();
initGrid();
})();
</script>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
