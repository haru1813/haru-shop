<?php
require_once __DIR__ . '/../inc/auth.php';
requireAdmin();

$pageTitle = '주소 코드';
$loadGrid = true;
require_once __DIR__ . '/../inc/header.php';
?>

<div class="admin-page">
    <div class="head"><h1>주소 코드</h1></div>
    <p id="msg" class="msg" style="display:none;"></p>
    <div class="card admin-search-form">
        <form id="form-search" onsubmit="return false;">
            <div class="form-row">
                <div class="form-field">
                    <label for="search_code">코드</label>
                    <input type="text" id="search_code" placeholder="코드">
                </div>
                <div class="form-field">
                    <label for="search_name">명칭</label>
                    <input type="text" id="search_name" placeholder="명칭">
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
        <div id="address-grid" data-ax5grid="address-grid" style="height:100%; min-height:240px;"></div>
    </div>
</div>

<div id="address-modal" class="admin-modal-overlay" role="dialog" aria-modal="true">
    <div class="admin-modal-box" onclick="event.stopPropagation()">
        <h2 class="admin-modal-title" id="address-modal-title">주소 코드 입력</h2>
        <div class="admin-modal-body">
            <form id="form-address" class="admin-modal-form">
                <div class="field">
                    <label for="modal-code">주소 코드</label>
                    <input type="text" id="modal-code" name="code" placeholder="예: WH01, RT01" required>
                </div>
                <div class="field">
                    <label for="modal-address_type">용도</label>
                    <select id="modal-address_type" name="address_type">
                        <option value="warehouse">출고지 (warehouse)</option>
                        <option value="return">반품/교환지 (return)</option>
                    </select>
                </div>
                <div class="field">
                    <label for="modal-name">관리용 명칭</label>
                    <input type="text" id="modal-name" name="name" placeholder="예: 본사 창고">
                </div>
                <div class="field">
                    <label for="modal-recipient_name">수령인/담당자</label>
                    <input type="text" id="modal-recipient_name" name="recipient_name" placeholder="담당자">
                </div>
                <div class="field">
                    <label for="modal-phone">연락처</label>
                    <input type="text" id="modal-phone" name="phone" placeholder="연락처">
                </div>
                <div class="field">
                    <label for="modal-postal_code">우편번호</label>
                    <input type="text" id="modal-postal_code" name="postal_code" placeholder="우편번호">
                </div>
                <div class="field">
                    <label for="modal-address">기본 주소</label>
                    <input type="text" id="modal-address" name="address" placeholder="기본 주소" required>
                </div>
                <div class="field">
                    <label for="modal-address_detail">상세 주소</label>
                    <input type="text" id="modal-address_detail" name="address_detail" placeholder="상세 주소">
                </div>
                <div class="field" style="display:flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" id="modal-is_active" name="is_active" value="1" checked>
                    <label for="modal-is_active" style="margin:0;">사용</label>
                </div>
                <p id="address-modal-error" class="error" style="display:none;"></p>
                <div class="admin-modal-actions">
                    <button type="button" class="btn btn-secondary" id="address-modal-cancel">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
var apiBase = '/admin/address_codes/api.php';
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
        target: $('[data-ax5grid="address-grid"]'),
        showLineNumber: true,
        showRowSelector: true,
        multipleSelect: true,
        lineNumberColumnWidth: 36,
        rowSelectorColumnWidth: 28,
        header: { align: 'center', columnHeight: 28 },
        body: { align: 'left', columnHeight: 28 },
        columns: [
            { key: 'id', label: 'ID', width: 70, align: 'left' },
            { key: 'code', label: '코드', width: 80, align: 'left' },
            { key: 'address_type', label: '용도', width: 90, align: 'left' },
            { key: 'name', label: '명칭', width: 120, align: 'left' },
            { key: 'recipient_name', label: '수령인', width: 90, align: 'left' },
            { key: 'address', label: '주소', width: 200, align: 'left' },
            { key: 'is_active', label: '사용', width: 50, align: 'center' },
            { key: 'created_at', label: '등록일', width: 160, align: 'left' }
        ]
    });
    gridInstance.setData(Array.isArray(gridData) ? gridData : []);
}

var modalEl = null;
var formEl = null;

function openModal() {
    if (!modalEl) modalEl = document.getElementById('address-modal');
    if (modalEl) modalEl.classList.add('is-open');
}

function closeModal() {
    if (!modalEl) modalEl = document.getElementById('address-modal');
    if (modalEl) modalEl.classList.remove('is-open');
}

function doEdit(id) {
    fetch(apiBase + '?id=' + id).then(function(r) {
        if (!r.ok) { showMsg('데이터를 불러오지 못했습니다.', true); return null; }
        return r.json();
    }).then(function(row) {
        if (!row) return;
        document.getElementById('modal-code').value = row.code || '';
        document.getElementById('modal-address_type').value = row.address_type || 'warehouse';
        document.getElementById('modal-name').value = row.name || '';
        document.getElementById('modal-recipient_name').value = row.recipient_name || '';
        document.getElementById('modal-phone').value = row.phone || '';
        document.getElementById('modal-postal_code').value = row.postal_code || '';
        document.getElementById('modal-address').value = row.address || '';
        document.getElementById('modal-address_detail').value = row.address_detail || '';
        document.getElementById('modal-is_active').checked = row.is_active != null ? !!row.is_active : true;
        formEl.dataset.editId = id;
        document.getElementById('address-modal-title').textContent = '주소 코드 수정';
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

formEl = document.getElementById('form-address');

document.getElementById('btn-search').addEventListener('click', loadList);
document.getElementById('btn-input').addEventListener('click', function() {
    formEl.reset();
    document.getElementById('modal-address_type').value = 'warehouse';
    document.getElementById('modal-is_active').checked = true;
    formEl.dataset.editId = '';
    document.getElementById('address-modal-title').textContent = '주소 코드 입력';
    document.getElementById('address-modal-error').style.display = 'none';
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

document.getElementById('address-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.getElementById('address-modal-cancel').addEventListener('click', closeModal);

formEl.addEventListener('submit', function(e) {
    e.preventDefault();
    var errEl = document.getElementById('address-modal-error');
    errEl.style.display = 'none';
    var editId = formEl.dataset.editId || '';
    var body = {
        code: document.getElementById('modal-code').value.trim(),
        address_type: document.getElementById('modal-address_type').value,
        name: document.getElementById('modal-name').value.trim(),
        recipient_name: document.getElementById('modal-recipient_name').value.trim(),
        phone: document.getElementById('modal-phone').value.trim(),
        postal_code: document.getElementById('modal-postal_code').value.trim(),
        address: document.getElementById('modal-address').value.trim(),
        address_detail: document.getElementById('modal-address_detail').value.trim(),
        is_active: document.getElementById('modal-is_active').checked ? 1 : 0
    };
    if (!body.code) { errEl.textContent = '주소 코드를 입력하세요.'; errEl.style.display = 'block'; return; }
    if (!body.address) { errEl.textContent = '기본 주소를 입력하세요.'; errEl.style.display = 'block'; return; }
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
