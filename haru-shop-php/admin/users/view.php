<?php
require_once __DIR__ . '/../inc/auth.php';
requireAdmin();

$pageTitle = '사용자';
$loadGrid = true;
require_once __DIR__ . '/../inc/header.php';
?>

<div class="admin-page">
    <div class="head"><h1>사용자</h1></div>
    <p id="msg" class="msg" style="display:none;"></p>
    <div class="card admin-search-form">
        <form id="form-search" onsubmit="return false;">
            <div class="form-row">
                <div class="form-field">
                    <label for="search_email">이메일</label>
                    <input type="text" id="search_email" placeholder="이메일">
                </div>
                <div class="form-field">
                    <label for="search_name">이름</label>
                    <input type="text" id="search_name" placeholder="이름">
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
        <div id="user-grid" data-ax5grid="user-grid" style="height:100%; min-height:240px;"></div>
    </div>
</div>

<div id="user-modal" class="admin-modal-overlay" role="dialog" aria-modal="true">
    <div class="admin-modal-box" onclick="event.stopPropagation()">
        <h2 class="admin-modal-title" id="user-modal-title">사용자 입력</h2>
        <div class="admin-modal-body">
            <form id="form-user" class="admin-modal-form">
                <div class="field" id="field-email">
                    <label for="modal-email">이메일</label>
                    <input type="email" id="modal-email" name="email" placeholder="email@example.com" required>
                </div>
                <div class="field">
                    <label for="modal-name">이름</label>
                    <input type="text" id="modal-name" name="name" placeholder="이름">
                </div>
                <div class="field" id="field-picture">
                    <label for="modal-picture">프로필 이미지 URL</label>
                    <input type="text" id="modal-picture" name="picture" placeholder="https://...">
                </div>
                <div class="field" id="field-provider">
                    <label for="modal-provider">로그인 제공자</label>
                    <select id="modal-provider" name="provider">
                        <option value="google">google</option>
                        <option value="naver">naver</option>
                        <option value="kakao">kakao</option>
                    </select>
                </div>
                <div class="field">
                    <label for="modal-role">역할</label>
                    <select id="modal-role" name="role">
                        <option value="user">user</option>
                        <option value="seller">seller</option>
                        <option value="admin">admin</option>
                    </select>
                </div>
                <p id="user-modal-error" class="error" style="display:none;"></p>
                <div class="admin-modal-actions">
                    <button type="button" class="btn btn-secondary" id="user-modal-cancel">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
var apiBase = '/admin/users/api.php';
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
        var searchEmail = (document.getElementById('search_email') || {}).value.trim().toLowerCase();
        var searchName = (document.getElementById('search_name') || {}).value.trim().toLowerCase();
        if (searchEmail) list = list.filter(function(r) { return (r.email || '').toLowerCase().indexOf(searchEmail) >= 0; });
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
        target: $('[data-ax5grid="user-grid"]'),
        showLineNumber: true,
        showRowSelector: true,
        multipleSelect: true,
        lineNumberColumnWidth: 36,
        rowSelectorColumnWidth: 28,
        header: { align: 'center', columnHeight: 28 },
        body: { align: 'left', columnHeight: 28 },
        columns: [
            { key: 'id', label: 'ID', width: 70, align: 'left' },
            { key: 'email', label: '이메일', width: 200, align: 'left' },
            { key: 'name', label: '이름', width: 100, align: 'left' },
            { key: 'provider', label: '제공자', width: 80, align: 'left' },
            { key: 'role', label: '역할', width: 70, align: 'left' },
            { key: 'created_at', label: '가입일', width: 160, align: 'left' }
        ]
    });
    gridInstance.setData(Array.isArray(gridData) ? gridData : []);
}

var modalEl = null;
var formEl = null;

function openModal() {
    if (!modalEl) modalEl = document.getElementById('user-modal');
    if (modalEl) modalEl.classList.add('is-open');
}

function closeModal() {
    if (!modalEl) modalEl = document.getElementById('user-modal');
    if (modalEl) modalEl.classList.remove('is-open');
}

function doEdit(id) {
    fetch(apiBase + '?id=' + id).then(function(r) {
        if (!r.ok) { showMsg('데이터를 불러오지 못했습니다.', true); return null; }
        return r.json();
    }).then(function(row) {
        if (!row) return;
        document.getElementById('modal-email').value = row.email || '';
        document.getElementById('modal-email').readOnly = true;
        document.getElementById('field-email').style.display = 'none';
        document.getElementById('modal-name').value = row.name || '';
        document.getElementById('modal-picture').value = row.picture || '';
        document.getElementById('modal-provider').value = row.provider || 'google';
        document.getElementById('modal-role').value = row.role || 'user';
        formEl.dataset.editId = id;
        document.getElementById('user-modal-title').textContent = '사용자 수정';
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
    if (!confirm('선택한 ' + selected.length + '명을 삭제하시겠습니까?')) return;
    var done = 0, total = selected.length;
    selected.forEach(function(row) {
        fetch(apiBase + '?id=' + row.id, { method: 'DELETE' }).then(function(res) {
            done++;
            if (done === total) { showMsg('삭제되었습니다.'); loadList(); }
        });
    });
}

formEl = document.getElementById('form-user');

document.getElementById('btn-search').addEventListener('click', loadList);
document.getElementById('btn-input').addEventListener('click', function() {
    formEl.reset();
    document.getElementById('modal-email').readOnly = false;
    document.getElementById('field-email').style.display = '';
    document.getElementById('modal-provider').value = 'google';
    document.getElementById('modal-role').value = 'user';
    formEl.dataset.editId = '';
    document.getElementById('user-modal-title').textContent = '사용자 입력';
    document.getElementById('user-modal-error').style.display = 'none';
    openModal();
});
document.getElementById('btn-edit').addEventListener('click', function() {
    var selected = [];
    if (gridInstance && gridInstance.getList) {
        try { selected = gridInstance.getList('selected') || []; } catch (e) { selected = []; }
        if (!Array.isArray(selected)) selected = [];
    }
    if (!selected || selected.length !== 1) {
        alert(selected && selected.length > 1 ? '한 명만 선택하세요.' : '수정할 행을 선택하세요.');
        return;
    }
    doEdit(selected[0].id);
});
document.getElementById('btn-delete').addEventListener('click', doDeleteSelected);

document.getElementById('user-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.getElementById('user-modal-cancel').addEventListener('click', closeModal);

formEl.addEventListener('submit', function(e) {
    e.preventDefault();
    var errEl = document.getElementById('user-modal-error');
    errEl.style.display = 'none';
    var editId = formEl.dataset.editId || '';
    var url = editId ? apiBase + '?id=' + editId : apiBase;
    var method = editId ? 'PUT' : 'POST';
    var body;
    if (editId) {
        body = {
            name: document.getElementById('modal-name').value.trim(),
            picture: document.getElementById('modal-picture').value.trim(),
            role: document.getElementById('modal-role').value
        };
    } else {
        body = {
            email: document.getElementById('modal-email').value.trim(),
            name: document.getElementById('modal-name').value.trim(),
            picture: document.getElementById('modal-picture').value.trim(),
            provider: document.getElementById('modal-provider').value,
            role: document.getElementById('modal-role').value
        };
        if (!body.email) { errEl.textContent = '이메일을 입력하세요.'; errEl.style.display = 'block'; return; }
    }
    fetch(url, { method: method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
        .then(function(res) { return res.json().then(function(data) { return { res: res, data: data }; }); })
        .then(function(r) {
            if (r.res.ok) {
                showMsg(editId ? '수정되었습니다.' : '등록되었습니다.');
                closeModal();
                formEl.reset();
                document.getElementById('modal-email').readOnly = false;
                document.getElementById('field-email').style.display = '';
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
