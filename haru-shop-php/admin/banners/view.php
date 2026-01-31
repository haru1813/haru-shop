<?php
require_once __DIR__ . '/../inc/auth.php';
requireAdmin();

$pageTitle = '배너';
$loadGrid = true;
require_once __DIR__ . '/../inc/header.php';
?>

<div class="admin-page">
    <div class="head"><h1>배너</h1></div>
    <p id="msg" class="msg" style="display:none;"></p>
    <div class="admin-list-actions">
        <button type="button" class="btn btn-search" id="btn-search">검색</button>
        <button type="button" class="btn btn-add" id="btn-input">입력</button>
        <button type="button" class="btn btn-edit" id="btn-edit">수정</button>
        <button type="button" class="btn btn-delete" id="btn-delete">삭제</button>
    </div>
    <div class="card admin-grid-wrap" style="flex:1 1 0%; min-height:0; display:flex; flex-direction:column; padding:0.35rem;">
        <div id="banner-grid" data-ax5grid="banner-grid" style="height:100%; min-height:240px;"></div>
    </div>
</div>

<div id="banner-modal" class="admin-modal-overlay" role="dialog" aria-modal="true">
    <div class="admin-modal-box" onclick="event.stopPropagation()">
        <h2 class="admin-modal-title" id="banner-modal-title">배너 입력</h2>
        <div class="admin-modal-body">
            <form id="form-banner" class="admin-modal-form">
                <div class="field">
                    <label for="modal-image_url">이미지 URL</label>
                    <input type="text" id="modal-image_url" name="image_url" placeholder="https://..." required>
                </div>
                <div class="field">
                    <label for="modal-link_url">클릭 시 이동 URL</label>
                    <input type="text" id="modal-link_url" name="link_url" placeholder="/ 또는 전체 URL">
                </div>
                <div class="field">
                    <label for="modal-sort_order">슬라이드 순서</label>
                    <input type="number" id="modal-sort_order" name="sort_order" value="0">
                </div>
                <div class="field" style="display:flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" id="modal-is_active" name="is_active" value="1" checked>
                    <label for="modal-is_active" style="margin:0;">노출</label>
                </div>
                <p id="banner-modal-error" class="error" style="display:none;"></p>
                <div class="admin-modal-actions">
                    <button type="button" class="btn btn-secondary" id="banner-modal-cancel">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
var apiBase = '/admin/banners/api.php';
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
        gridData = Array.isArray(data.results) ? data.results : (Array.isArray(data) ? data : []);
        if (gridInstance && typeof gridInstance.setData === 'function') gridInstance.setData(gridData);
    }).catch(function() { showMsg('목록을 불러오지 못했습니다.', true); });
}

function initGrid() {
    if (typeof ax5 === 'undefined' || !ax5.ui || !ax5.ui.grid) { setTimeout(initGrid, 100); return; }
    var $ = window.jQuery;
    gridInstance = new ax5.ui.grid();
    gridInstance.setConfig({
        target: $('[data-ax5grid="banner-grid"]'),
        showLineNumber: true,
        showRowSelector: true,
        multipleSelect: true,
        lineNumberColumnWidth: 36,
        rowSelectorColumnWidth: 28,
        header: { align: 'center', columnHeight: 28 },
        body: { align: 'left', columnHeight: 28 },
        columns: [
            { key: 'id', label: 'ID', width: 70, align: 'left' },
            { key: 'image_url', label: '이미지 URL', width: 220, align: 'left' },
            { key: 'link_url', label: '링크 URL', width: 180, align: 'left' },
            { key: 'sort_order', label: '순서', width: 70, align: 'right' },
            { key: 'is_active', label: '노출', width: 50, align: 'center' },
            { key: 'created_at', label: '등록일', width: 160, align: 'left' }
        ]
    });
    gridInstance.setData(Array.isArray(gridData) ? gridData : []);
}

var modalEl = null;
var formEl = null;

function openModal() {
    if (!modalEl) modalEl = document.getElementById('banner-modal');
    if (modalEl) modalEl.classList.add('is-open');
}

function closeModal() {
    if (!modalEl) modalEl = document.getElementById('banner-modal');
    if (modalEl) modalEl.classList.remove('is-open');
}

function doEdit(id) {
    fetch(apiBase + '?id=' + id).then(function(r) {
        if (!r.ok) { showMsg('데이터를 불러오지 못했습니다.', true); return null; }
        return r.json();
    }).then(function(row) {
        if (!row) return;
        document.getElementById('modal-image_url').value = row.image_url || '';
        document.getElementById('modal-link_url').value = row.link_url || '';
        document.getElementById('modal-sort_order').value = row.sort_order ?? 0;
        document.getElementById('modal-is_active').checked = row.is_active != null ? !!row.is_active : true;
        formEl.dataset.editId = id;
        document.getElementById('banner-modal-title').textContent = '배너 수정';
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

formEl = document.getElementById('form-banner');

document.getElementById('btn-search').addEventListener('click', loadList);
document.getElementById('btn-input').addEventListener('click', function() {
    formEl.reset();
    document.getElementById('modal-sort_order').value = 0;
    document.getElementById('modal-is_active').checked = true;
    formEl.dataset.editId = '';
    document.getElementById('banner-modal-title').textContent = '배너 입력';
    document.getElementById('banner-modal-error').style.display = 'none';
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

document.getElementById('banner-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.getElementById('banner-modal-cancel').addEventListener('click', closeModal);

formEl.addEventListener('submit', function(e) {
    e.preventDefault();
    var errEl = document.getElementById('banner-modal-error');
    errEl.style.display = 'none';
    var editId = formEl.dataset.editId || '';
    var body = {
        image_url: document.getElementById('modal-image_url').value.trim(),
        link_url: document.getElementById('modal-link_url').value.trim(),
        sort_order: parseInt(document.getElementById('modal-sort_order').value, 10) || 0,
        is_active: document.getElementById('modal-is_active').checked ? 1 : 0
    };
    if (!body.image_url) { errEl.textContent = '이미지 URL을 입력하세요.'; errEl.style.display = 'block'; return; }
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
