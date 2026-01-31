<?php
require_once __DIR__ . '/../inc/auth.php';
requireAdmin();

$pageTitle = '카테고리';
$loadGrid = true;
require_once __DIR__ . '/../inc/header.php';
?>

<div class="admin-page">
    <div class="head">
        <h1>카테고리</h1>
    </div>

    <p id="msg" class="msg" style="display:none;"></p>

    <div class="card admin-search-form">
        <form id="form-search" onsubmit="return false;">
            <div class="form-row">
                <div class="form-field">
                    <label for="search-name">이름</label>
                    <input type="text" id="search-name" placeholder="이름">
                </div>
                <div class="form-field">
                    <label for="search-slug">슬러그</label>
                    <input type="text" id="search-slug" placeholder="슬러그">
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
        <div id="category-grid" data-ax5grid="category-grid" style="height:100%; min-height:240px;"></div>
    </div>
</div>

<div id="category-modal" class="admin-modal-overlay" role="dialog" aria-modal="true">
    <div class="admin-modal-box" onclick="event.stopPropagation()">
        <h2 class="admin-modal-title" id="category-modal-title">카테고리 입력</h2>
        <div class="admin-modal-body">
            <form id="form-category" class="admin-modal-form">
                <div class="field">
                    <label for="modal-name">이름</label>
                    <input type="text" id="modal-name" name="name" placeholder="이름" required>
                </div>
                <div class="field">
                    <label for="modal-slug">슬러그</label>
                    <input type="text" id="modal-slug" name="slug" placeholder="슬러그">
                </div>
                <div class="field">
                    <label for="modal-icon">아이콘</label>
                    <input type="text" id="modal-icon" name="icon" placeholder="shirt, layout-grid 등">
                </div>
                <div class="field">
                    <label for="modal-sort_order">정렬순서</label>
                    <input type="number" id="modal-sort_order" name="sort_order" value="0">
                </div>
                <p id="modal-error" class="error" style="display:none;"></p>
                <div class="admin-modal-actions">
                    <button type="button" class="btn btn-secondary" id="category-modal-cancel">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
var apiBase = '/admin/categories/api.php';
var gridInstance = null;
var gridData = [];

function showMsg(text, isError) {
    var el = document.getElementById('msg');
    el.textContent = text;
    el.className = 'msg ' + (isError ? 'error' : 'success');
    el.style.display = 'block';
    setTimeout(function() { el.style.display = 'none'; }, 4000);
}

function escapeHtml(s) {
    var div = document.createElement('div');
    div.textContent = s == null ? '' : s;
    return div.innerHTML;
}

function loadList() {
    fetch(apiBase).then(function(res) {
        if (!res.ok) { showMsg('목록을 불러오지 못했습니다.', true); return res.json().then(function() {}); }
        return res.json();
    }).then(function(data) {
        if (!data) return;
        var list = Array.isArray(data.results) ? data.results : (Array.isArray(data) ? data : []);
        if (!Array.isArray(list)) list = [];
        var searchName = (document.getElementById('search-name') && document.getElementById('search-name').value || '').trim().toLowerCase();
        var searchSlug = (document.getElementById('search-slug') && document.getElementById('search-slug').value || '').trim().toLowerCase();
        if (searchName) list = list.filter(function(r) { return (r.name || '').toLowerCase().indexOf(searchName) >= 0; });
        if (searchSlug) list = list.filter(function(r) { return (r.slug || '').toLowerCase().indexOf(searchSlug) >= 0; });
        gridData = list;
        if (gridInstance && typeof gridInstance.setData === 'function') {
            gridInstance.setData(gridData);
        }
    }).catch(function() { showMsg('목록을 불러오지 못했습니다.', true); });
}

function initGrid() {
    if (typeof ax5 === 'undefined' || !ax5.ui || !ax5.ui.grid) {
        setTimeout(initGrid, 100);
        return;
    }
    var $ = window.jQuery;
    gridInstance = new ax5.ui.grid();
    gridInstance.setConfig({
        target: $('[data-ax5grid="category-grid"]'),
        showLineNumber: true,
        showRowSelector: true,
        multipleSelect: true,
        lineNumberColumnWidth: 36,
        rowSelectorColumnWidth: 28,
        header: { align: 'center', columnHeight: 28 },
        body: { align: 'left', columnHeight: 28 },
        columns: [
            { key: 'id', label: 'ID', width: 70, align: 'left' },
            { key: 'name', label: '이름', width: 120, align: 'left' },
            { key: 'slug', label: '슬러그', width: 120, align: 'left' },
            { key: 'icon', label: '아이콘', width: 100, align: 'left' },
            { key: 'sort_order', label: '정렬', width: 70, align: 'right' },
            { key: 'created_at', label: '등록일', width: 160, align: 'left' }
        ]
    });
    gridInstance.setData(Array.isArray(gridData) ? gridData : []);
}

var modalEl = null;
var formEl = null;

function openCategoryModal() {
    if (!modalEl) modalEl = document.getElementById('category-modal');
    if (modalEl) modalEl.classList.add('is-open');
}

function closeCategoryModal() {
    if (!modalEl) modalEl = document.getElementById('category-modal');
    if (modalEl) modalEl.classList.remove('is-open');
}

function doEdit(id) {
    var row = gridData.find(function(r) { return String(r.id) === String(id); });
    if (!row) return;
    document.getElementById('modal-name').value = row.name || '';
    document.getElementById('modal-slug').value = row.slug || '';
    document.getElementById('modal-icon').value = row.icon || '';
    document.getElementById('modal-sort_order').value = row.sort_order ?? 0;
    formEl.dataset.editId = id;
    document.getElementById('category-modal-title').textContent = '카테고리 수정';
    openCategoryModal();
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
            done++; if (done === total) { showMsg('삭제되었습니다.'); loadList(); }
        });
    });
}

formEl = document.getElementById('form-category');

document.getElementById('btn-search').addEventListener('click', loadList);
document.getElementById('btn-input').addEventListener('click', function() {
    formEl.reset();
    document.getElementById('modal-sort_order').value = 0;
    formEl.dataset.editId = '';
    document.getElementById('category-modal-title').textContent = '카테고리 입력';
    document.getElementById('modal-error').style.display = 'none';
    openCategoryModal();
});
document.getElementById('btn-edit').addEventListener('click', function() {
    var selected = [];
    if (gridInstance && gridInstance.getList) {
        try { selected = gridInstance.getList('selected') || []; } catch (e) { selected = []; }
        if (!Array.isArray(selected)) selected = [];
    }
    if (!selected || selected.length !== 1) { alert(selected && selected.length > 1 ? '한 개만 선택하세요.' : '수정할 행을 선택하세요.'); return; }
    doEdit(selected[0].id);
});
document.getElementById('btn-delete').addEventListener('click', doDeleteSelected);

document.getElementById('category-modal').addEventListener('click', function(e) {
    if (e.target === this) closeCategoryModal();
});
document.getElementById('category-modal-cancel').addEventListener('click', closeCategoryModal);

formEl.addEventListener('submit', function(e) {
    e.preventDefault();
    var errEl = document.getElementById('modal-error');
    errEl.style.display = 'none';
    var editId = formEl.dataset.editId || '';
    var body = {
        name: document.getElementById('modal-name').value.trim(),
        slug: document.getElementById('modal-slug').value.trim(),
        icon: document.getElementById('modal-icon').value.trim(),
        sort_order: parseInt(document.getElementById('modal-sort_order').value, 10) || 0
    };
    var url = editId ? apiBase + '?id=' + editId : apiBase;
    var method = editId ? 'PUT' : 'POST';
    fetch(url, { method: method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
        .then(function(res) { return res.json().then(function(data) { return { res: res, data: data }; }); })
        .then(function(r) {
            if (r.res.ok) {
                showMsg(editId ? '수정되었습니다.' : '등록되었습니다.');
                closeCategoryModal();
                formEl.reset();
                document.getElementById('modal-sort_order').value = 0;
                formEl.dataset.editId = '';
                loadList();
            } else {
                errEl.textContent = r.data.error || '저장 실패';
                errEl.style.display = 'block';
            }
        });
});

loadList();
initGrid();
})();
</script>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
