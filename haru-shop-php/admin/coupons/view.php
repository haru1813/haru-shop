<?php
require_once __DIR__ . '/../inc/auth.php';
requireAdmin();

$pageTitle = '쿠폰';
$loadGrid = true;
require_once __DIR__ . '/../inc/header.php';
?>

<div class="admin-page">
    <div class="head"><h1>쿠폰</h1></div>
    <p id="msg" class="msg" style="display:none;"></p>
    <div class="card admin-search-form">
        <form id="form-search" onsubmit="return false;">
            <div class="form-row">
                <div class="form-field">
                    <label for="search_code">쿠폰 코드</label>
                    <input type="text" id="search_code" placeholder="코드">
                </div>
                <div class="form-field">
                    <label for="search_name">쿠폰명</label>
                    <input type="text" id="search_name" placeholder="쿠폰명">
                </div>
            </div>
        </form>
    </div>
    <div class="admin-list-actions">
        <button type="button" class="btn btn-search" id="btn-search">검색</button>
        <button type="button" class="btn btn-add" id="btn-input">입력</button>
        <button type="button" class="btn btn-edit" id="btn-edit">수정</button>
        <button type="button" class="btn btn-delete" id="btn-delete">삭제</button>
    </div>
    <div class="card admin-grid-wrap" style="flex:1 1 0%; min-height:0; display:flex; flex-direction:column; padding:0.35rem;">
        <div id="coupon-grid" data-ax5grid="coupon-grid" style="height:100%; min-height:240px;"></div>
    </div>
</div>

<div id="coupon-modal" class="admin-modal-overlay" role="dialog" aria-modal="true">
    <div class="admin-modal-box" onclick="event.stopPropagation()">
        <h2 class="admin-modal-title" id="coupon-modal-title">쿠폰 입력</h2>
        <div class="admin-modal-body">
            <form id="form-coupon" class="admin-modal-form">
                <div class="field">
                    <label for="modal-code">쿠폰 코드</label>
                    <input type="text" id="modal-code" name="code" placeholder="예: WELCOME10" required>
                </div>
                <div class="field">
                    <label for="modal-name">쿠폰명</label>
                    <input type="text" id="modal-name" name="name" placeholder="노출용 이름" required>
                </div>
                <div class="field">
                    <label for="modal-discount_type">할인 유형</label>
                    <select id="modal-discount_type" name="discount_type">
                        <option value="percent">퍼센트 (%)</option>
                        <option value="fixed">정액 (원)</option>
                    </select>
                </div>
                <div class="field">
                    <label for="modal-discount_value">할인값 (% 또는 원)</label>
                    <input type="number" id="modal-discount_value" name="discount_value" min="0" value="0">
                </div>
                <div class="field">
                    <label for="modal-min_order_amount">최소 주문 금액 (원)</label>
                    <input type="number" id="modal-min_order_amount" name="min_order_amount" min="0" value="0">
                </div>
                <div class="field">
                    <label for="modal-max_discount_amount">최대 할인 금액 (원, %일 때 상한)</label>
                    <input type="number" id="modal-max_discount_amount" name="max_discount_amount" min="0" placeholder="비우면 없음">
                </div>
                <div class="field">
                    <label for="modal-valid_from">사용 가능 시작일시</label>
                    <input type="text" id="modal-valid_from" name="valid_from" placeholder="2025-01-01 00:00:00" required>
                </div>
                <div class="field">
                    <label for="modal-valid_until">사용 가능 종료일시</label>
                    <input type="text" id="modal-valid_until" name="valid_until" placeholder="2025-12-31 23:59:59" required>
                </div>
                <div class="field">
                    <label for="modal-total_quantity">총 발급 수량</label>
                    <input type="number" id="modal-total_quantity" name="total_quantity" min="0" placeholder="비우면 무제한">
                </div>
                <div class="field" style="display:flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" id="modal-is_active" name="is_active" value="1" checked>
                    <label for="modal-is_active" style="margin:0;">사용 가능</label>
                </div>
                <p id="coupon-modal-error" class="error" style="display:none;"></p>
                <div class="admin-modal-actions">
                    <button type="button" class="btn btn-secondary" id="coupon-modal-cancel">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
var apiBase = '/admin/coupons/api.php';
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
        var searchCode = (document.getElementById('search_code') || {}).value.trim().toLowerCase();
        var searchName = (document.getElementById('search_name') || {}).value.trim().toLowerCase();
        if (searchCode) list = list.filter(function(r) { return (r.code || '').toLowerCase().indexOf(searchCode) >= 0; });
        if (searchName) list = list.filter(function(r) { return (r.name || '').toLowerCase().indexOf(searchName) >= 0; });
        gridData = list;
        if (gridInstance && typeof gridInstance.setData === 'function') gridInstance.setData(gridData);
    }).catch(function() { showMsg('목록을 불러오지 못했습니다.', true); });
}

function initGrid() {
    if (typeof ax5 === 'undefined' || !ax5.ui || !ax5.ui.grid) { setTimeout(initGrid, 100); return; }
    var $ = window.jQuery;
    gridInstance = new ax5.ui.grid();
    gridInstance.setConfig({
        target: $('[data-ax5grid="coupon-grid"]'),
        showLineNumber: true,
        showRowSelector: true,
        multipleSelect: true,
        lineNumberColumnWidth: 36,
        rowSelectorColumnWidth: 28,
        header: { align: 'center', columnHeight: 28 },
        body: { align: 'left', columnHeight: 28 },
        columns: [
            { key: 'id', label: 'ID', width: 70, align: 'left' },
            { key: 'code', label: '코드', width: 100, align: 'left' },
            { key: 'name', label: '쿠폰명', width: 140, align: 'left' },
            { key: 'discount_type', label: '유형', width: 70, align: 'left' },
            { key: 'discount_value', label: '할인값', width: 80, align: 'right' },
            { key: 'min_order_amount', label: '최소주문', width: 90, align: 'right' },
            { key: 'valid_from', label: '시작일', width: 140, align: 'left' },
            { key: 'valid_until', label: '종료일', width: 140, align: 'left' },
            { key: 'is_active', label: '사용', width: 50, align: 'center' },
            { key: 'created_at', label: '등록일', width: 160, align: 'left' }
        ]
    });
    gridInstance.setData(Array.isArray(gridData) ? gridData : []);
}

var modalEl = null;
var formEl = null;

function openModal() {
    if (!modalEl) modalEl = document.getElementById('coupon-modal');
    if (modalEl) modalEl.classList.add('is-open');
}

function closeModal() {
    if (!modalEl) modalEl = document.getElementById('coupon-modal');
    if (modalEl) modalEl.classList.remove('is-open');
}

function doEdit(id) {
    fetch(apiBase + '?id=' + id).then(function(r) {
        if (!r.ok) { showMsg('데이터를 불러오지 못했습니다.', true); return null; }
        return r.json();
    }).then(function(row) {
        if (!row) return;
        document.getElementById('modal-code').value = row.code || '';
        document.getElementById('modal-name').value = row.name || '';
        document.getElementById('modal-discount_type').value = row.discount_type || 'percent';
        document.getElementById('modal-discount_value').value = row.discount_value != null ? row.discount_value : 0;
        document.getElementById('modal-min_order_amount').value = row.min_order_amount != null ? row.min_order_amount : 0;
        document.getElementById('modal-max_discount_amount').value = row.max_discount_amount != null && row.max_discount_amount !== '' ? row.max_discount_amount : '';
        document.getElementById('modal-valid_from').value = row.valid_from || '';
        document.getElementById('modal-valid_until').value = row.valid_until || '';
        document.getElementById('modal-total_quantity').value = row.total_quantity != null && row.total_quantity !== '' ? row.total_quantity : '';
        document.getElementById('modal-is_active').checked = row.is_active != null ? !!row.is_active : true;
        formEl.dataset.editId = id;
        document.getElementById('coupon-modal-title').textContent = '쿠폰 수정';
        openModal();
    });
}

function doDeleteSelected() {
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
}

formEl = document.getElementById('form-coupon');

document.getElementById('btn-search').addEventListener('click', loadList);
document.getElementById('btn-input').addEventListener('click', function() {
    formEl.reset();
    document.getElementById('modal-discount_value').value = 0;
    document.getElementById('modal-min_order_amount').value = 0;
    document.getElementById('modal-discount_type').value = 'percent';
    document.getElementById('modal-is_active').checked = true;
    formEl.dataset.editId = '';
    document.getElementById('coupon-modal-title').textContent = '쿠폰 입력';
    document.getElementById('coupon-modal-error').style.display = 'none';
    openModal();
});
document.getElementById('btn-edit').addEventListener('click', function() {
    var selected = [];
    if (gridInstance && gridInstance.getList) {
        try { selected = gridInstance.getList('selected') || []; } catch (e) { selected = []; }
        if (!Array.isArray(selected)) selected = [];
    }
    if (!selected || selected.length !== 1) {
        alert(selected && selected.length > 1 ? '한 개만 선택하세요.' : '수정할 행을 선택하세요.');
        return;
    }
    doEdit(selected[0].id);
});
document.getElementById('btn-delete').addEventListener('click', doDeleteSelected);

document.getElementById('coupon-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.getElementById('coupon-modal-cancel').addEventListener('click', closeModal);

formEl.addEventListener('submit', function(e) {
    e.preventDefault();
    var errEl = document.getElementById('coupon-modal-error');
    errEl.style.display = 'none';
    var editId = formEl.dataset.editId || '';
    var validFrom = document.getElementById('modal-valid_from').value.trim();
    var validUntil = document.getElementById('modal-valid_until').value.trim();
    if (!validFrom || !validUntil) {
        errEl.textContent = '유효기간(시작/종료)을 입력하세요.';
        errEl.style.display = 'block';
        return;
    }
    var body = {
        code: document.getElementById('modal-code').value.trim(),
        name: document.getElementById('modal-name').value.trim(),
        discount_type: document.getElementById('modal-discount_type').value,
        discount_value: parseInt(document.getElementById('modal-discount_value').value, 10) || 0,
        min_order_amount: parseInt(document.getElementById('modal-min_order_amount').value, 10) || 0,
        max_discount_amount: document.getElementById('modal-max_discount_amount').value === '' ? null : parseInt(document.getElementById('modal-max_discount_amount').value, 10),
        valid_from: validFrom,
        valid_until: validUntil,
        total_quantity: document.getElementById('modal-total_quantity').value === '' ? null : parseInt(document.getElementById('modal-total_quantity').value, 10),
        is_active: document.getElementById('modal-is_active').checked ? 1 : 0
    };
    if (!body.code) { errEl.textContent = '쿠폰 코드를 입력하세요.'; errEl.style.display = 'block'; return; }
    if (!body.name) { errEl.textContent = '쿠폰명을 입력하세요.'; errEl.style.display = 'block'; return; }
    var url = editId ? apiBase + '?id=' + editId : apiBase;
    var method = editId ? 'PUT' : 'POST';
    fetch(url, { method: method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
        .then(function(res) { return res.json().then(function(data) { return { res: res, data: data }; }); })
        .then(function(r) {
            if (r.res.ok) {
                showMsg(editId ? '수정되었습니다.' : '등록되었습니다.');
                closeModal();
                formEl.reset();
                formEl.dataset.editId = '';
                loadList();
            } else {
                errEl.textContent = (r.data && r.data.error) || '저장 실패';
                errEl.style.display = 'block';
            }
        })
        .catch(function() {
            errEl.textContent = '저장 중 오류가 발생했습니다.';
            errEl.style.display = 'block';
        });
});

loadList();
initGrid();
})();
</script>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
