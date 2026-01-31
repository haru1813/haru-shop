<?php
require_once __DIR__ . '/../inc/auth.php';
requireAdmin();

$pageTitle = '배송비 템플릿';
$loadGrid = true;
require_once __DIR__ . '/../inc/header.php';
?>

<div class="admin-page">
    <div class="head"><h1>배송비 템플릿</h1></div>
    <p id="msg" class="msg" style="display:none;"></p>
    <div class="card admin-search-form">
        <form id="form-search" onsubmit="return false;">
            <div class="form-row">
                <div class="form-field">
                    <label for="search_name">템플릿명</label>
                    <input type="text" id="search_name" placeholder="템플릿명">
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
        <div id="delivery-grid" data-ax5grid="delivery-grid" style="height:100%; min-height:240px;"></div>
    </div>
</div>

<div id="delivery-modal" class="admin-modal-overlay" role="dialog" aria-modal="true">
    <div class="admin-modal-box" onclick="event.stopPropagation()">
        <h2 class="admin-modal-title" id="delivery-modal-title">배송비 템플릿 입력</h2>
        <div class="admin-modal-body">
            <form id="form-delivery" class="admin-modal-form">
                <div class="field">
                    <label for="modal-name">템플릿명</label>
                    <input type="text" id="modal-name" name="name" placeholder="예: 기본배송, 무료배송" required>
                </div>
                <div class="field">
                    <label for="modal-fee_type">유형</label>
                    <select id="modal-fee_type" name="fee_type">
                        <option value="free">무료 (free)</option>
                        <option value="paid" selected>유료 (paid)</option>
                        <option value="conditional_free">조건부 무료 (conditional_free)</option>
                        <option value="per_quantity">수량별 부과 (per_quantity)</option>
                    </select>
                </div>
                <div class="field">
                    <label for="modal-base_fee">기본 배송비 (원)</label>
                    <input type="number" id="modal-base_fee" name="base_fee" min="0" value="0">
                </div>
                <div class="field">
                    <label for="modal-free_over_amount">조건부 무료 기준 금액 (원)</label>
                    <input type="number" id="modal-free_over_amount" name="free_over_amount" min="0" placeholder="비우면 없음">
                </div>
                <div class="field">
                    <label for="modal-fee_per_quantity">수량별 N개당 배송비 (원)</label>
                    <input type="number" id="modal-fee_per_quantity" name="fee_per_quantity" min="0" placeholder="수량별 시">
                </div>
                <div class="field">
                    <label for="modal-quantity_unit">수량 단위 (예: 2개당 → 2)</label>
                    <input type="number" id="modal-quantity_unit" name="quantity_unit" min="1" placeholder="수량별 시">
                </div>
                <div class="field">
                    <label for="modal-shipping_method">배송방법</label>
                    <select id="modal-shipping_method" name="shipping_method">
                        <option value="parcel" selected>택배 (parcel)</option>
                        <option value="direct">직배송 (direct)</option>
                        <option value="pickup">방문수령 (pickup)</option>
                        <option value="quick">퀵/당일 (quick)</option>
                    </select>
                </div>
                <div class="field">
                    <label for="modal-external_id">외부 ID (ESM 등)</label>
                    <input type="text" id="modal-external_id" name="external_id" placeholder="선택">
                </div>
                <div class="field">
                    <label for="modal-sort_order">정렬순서</label>
                    <input type="number" id="modal-sort_order" name="sort_order" value="0">
                </div>
                <div class="field" style="display:flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" id="modal-is_active" name="is_active" value="1" checked>
                    <label for="modal-is_active" style="margin:0;">사용</label>
                </div>
                <p id="delivery-modal-error" class="error" style="display:none;"></p>
                <div class="admin-modal-actions">
                    <button type="button" class="btn btn-secondary" id="delivery-modal-cancel">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
var apiBase = '/admin/delivery_fee_templates/api.php';
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
        var searchName = (document.getElementById('search_name') || {}).value;
        if (searchName) {
            searchName = searchName.trim().toLowerCase();
            list = list.filter(function(row) { return (row.name || '').toLowerCase().indexOf(searchName) >= 0; });
        }
        gridData = list;
        if (gridInstance && typeof gridInstance.setData === 'function') gridInstance.setData(gridData);
    }).catch(function() { showMsg('목록을 불러오지 못했습니다.', true); });
}

function initGrid() {
    if (typeof ax5 === 'undefined' || !ax5.ui || !ax5.ui.grid) { setTimeout(initGrid, 100); return; }
    var $ = window.jQuery;
    gridInstance = new ax5.ui.grid();
    gridInstance.setConfig({
        target: $('[data-ax5grid="delivery-grid"]'),
        showLineNumber: true,
        showRowSelector: true,
        multipleSelect: true,
        lineNumberColumnWidth: 36,
        rowSelectorColumnWidth: 28,
        header: { align: 'center', columnHeight: 28 },
        body: { align: 'left', columnHeight: 28 },
        columns: [
            { key: 'id', label: 'ID', width: 70, align: 'left' },
            { key: 'name', label: '템플릿명', width: 140, align: 'left' },
            { key: 'fee_type', label: '유형', width: 100, align: 'left' },
            { key: 'base_fee', label: '기본비', width: 80, align: 'right' },
            { key: 'free_over_amount', label: '조건무료기준', width: 100, align: 'right' },
            { key: 'sort_order', label: '순서', width: 60, align: 'right' },
            { key: 'is_active', label: '사용', width: 50, align: 'center' },
            { key: 'created_at', label: '등록일', width: 160, align: 'left' }
        ]
    });
    gridInstance.setData(Array.isArray(gridData) ? gridData : []);
}

var modalEl = null;
var formEl = null;

function openModal() {
    if (!modalEl) modalEl = document.getElementById('delivery-modal');
    if (modalEl) modalEl.classList.add('is-open');
}

function closeModal() {
    if (!modalEl) modalEl = document.getElementById('delivery-modal');
    if (modalEl) modalEl.classList.remove('is-open');
}

function doEdit(id) {
    fetch(apiBase + '?id=' + id).then(function(r) {
        if (!r.ok) { showMsg('데이터를 불러오지 못했습니다.', true); return null; }
        return r.json();
    }).then(function(row) {
        if (!row) return;
        document.getElementById('modal-name').value = row.name || '';
        document.getElementById('modal-fee_type').value = row.fee_type || 'paid';
        document.getElementById('modal-base_fee').value = row.base_fee != null ? row.base_fee : 0;
        document.getElementById('modal-free_over_amount').value = row.free_over_amount != null && row.free_over_amount !== '' ? row.free_over_amount : '';
        document.getElementById('modal-fee_per_quantity').value = row.fee_per_quantity != null && row.fee_per_quantity !== '' ? row.fee_per_quantity : '';
        document.getElementById('modal-quantity_unit').value = row.quantity_unit != null && row.quantity_unit !== '' ? row.quantity_unit : '';
        document.getElementById('modal-shipping_method').value = row.shipping_method || 'parcel';
        document.getElementById('modal-external_id').value = row.external_id || '';
        document.getElementById('modal-sort_order').value = row.sort_order ?? 0;
        document.getElementById('modal-is_active').checked = row.is_active != null ? !!row.is_active : true;
        formEl.dataset.editId = id;
        document.getElementById('delivery-modal-title').textContent = '배송비 템플릿 수정';
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

formEl = document.getElementById('form-delivery');

document.getElementById('btn-search').addEventListener('click', loadList);
document.getElementById('btn-input').addEventListener('click', function() {
    formEl.reset();
    document.getElementById('modal-base_fee').value = 0;
    document.getElementById('modal-sort_order').value = 0;
    document.getElementById('modal-fee_type').value = 'paid';
    document.getElementById('modal-shipping_method').value = 'parcel';
    document.getElementById('modal-is_active').checked = true;
    formEl.dataset.editId = '';
    document.getElementById('delivery-modal-title').textContent = '배송비 템플릿 입력';
    document.getElementById('delivery-modal-error').style.display = 'none';
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

document.getElementById('delivery-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.getElementById('delivery-modal-cancel').addEventListener('click', closeModal);

formEl.addEventListener('submit', function(e) {
    e.preventDefault();
    var errEl = document.getElementById('delivery-modal-error');
    errEl.style.display = 'none';
    var editId = formEl.dataset.editId || '';
    var body = {
        name: document.getElementById('modal-name').value.trim(),
        fee_type: document.getElementById('modal-fee_type').value,
        base_fee: parseInt(document.getElementById('modal-base_fee').value, 10) || 0,
        free_over_amount: document.getElementById('modal-free_over_amount').value === '' ? null : parseInt(document.getElementById('modal-free_over_amount').value, 10),
        fee_per_quantity: document.getElementById('modal-fee_per_quantity').value === '' ? null : parseInt(document.getElementById('modal-fee_per_quantity').value, 10),
        quantity_unit: document.getElementById('modal-quantity_unit').value === '' ? null : parseInt(document.getElementById('modal-quantity_unit').value, 10),
        shipping_method: document.getElementById('modal-shipping_method').value,
        external_id: document.getElementById('modal-external_id').value.trim(),
        sort_order: parseInt(document.getElementById('modal-sort_order').value, 10) || 0,
        is_active: document.getElementById('modal-is_active').checked ? 1 : 0
    };
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
