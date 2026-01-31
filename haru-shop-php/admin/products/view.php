<?php
require_once __DIR__ . '/../inc/auth.php';
requireAdmin();

$pageTitle = '상품';
$loadGrid = true;
require_once __DIR__ . '/../inc/header.php';
?>

<div class="admin-page">
    <div class="head">
        <h1>상품</h1>
    </div>

    <p id="msg" class="msg" style="display:none;"></p>

    <div class="card admin-search-form">
        <form id="form-search" onsubmit="return false;">
            <div class="form-row">
                <div class="form-field">
                    <label for="search_name">상품명</label>
                    <input type="text" id="search_name" placeholder="상품명">
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
        <div id="product-grid" data-ax5grid="product-grid" style="height:100%; min-height:240px;"></div>
    </div>
</div>

<div id="product-modal" class="admin-modal-overlay" role="dialog" aria-modal="true">
    <div class="admin-modal-box" onclick="event.stopPropagation()">
        <h2 class="admin-modal-title" id="product-modal-title">상품 입력</h2>
        <div class="admin-modal-body">
            <form id="form-product" class="admin-modal-form">
                <div class="field">
                    <label for="modal-category_id">카테고리</label>
                    <select id="modal-category_id" name="category_id" required>
                        <option value="">선택</option>
                    </select>
                </div>
                <div class="field">
                    <label for="modal-name">상품명</label>
                    <input type="text" id="modal-name" name="name" placeholder="상품명" required>
                </div>
                <div class="field">
                    <label for="modal-slug">슬러그</label>
                    <input type="text" id="modal-slug" name="slug" placeholder="url-slug (비우면 자동)">
                </div>
                <div class="field">
                    <label for="modal-price">가격 (원)</label>
                    <input type="number" id="modal-price" name="price" min="0" value="0" required>
                </div>
                <div class="field">
                    <label for="modal-stock">재고</label>
                    <input type="number" id="modal-stock" name="stock" min="0" value="0">
                </div>
                <div class="field">
                    <label for="modal-image_url">대표 이미지 URL</label>
                    <input type="text" id="modal-image_url" name="image_url" placeholder="https://...">
                </div>
                <div class="field">
                    <label for="modal-description">설명</label>
                    <textarea id="modal-description" name="description" rows="3" placeholder="상품 소개"></textarea>
                </div>
                <div class="field">
                    <label for="modal-delivery_fee_template_id">배송비 템플릿</label>
                    <select id="modal-delivery_fee_template_id" name="delivery_fee_template_id">
                        <option value="">없음 (기본)</option>
                    </select>
                </div>
                <div class="field field-checkbox">
                    <input type="checkbox" id="modal-is_active" name="is_active" value="1" checked>
                    <label for="modal-is_active">노출</label>
                </div>
                <div class="field">
                    <div class="option-groups-header">
                        <span class="field-label">옵션 그룹</span>
                        <button type="button" class="btn btn-small" id="btn-add-option-group">+ 그룹 추가</button>
                    </div>
                    <div id="option-groups-container" class="option-groups-container"></div>
                </div>
                <p id="product-modal-error" class="error" style="display:none;"></p>
                <div class="admin-modal-actions">
                    <button type="button" class="btn btn-secondary" id="product-modal-cancel">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
var apiBase = '/admin/products/api.php';
var gridInstance = null;
var gridData = [];
var categories = [];
var deliveryTemplates = [];

function showMsg(text, isError) {
    var el = document.getElementById('msg');
    el.textContent = text;
    el.className = 'msg ' + (isError ? 'error' : 'success');
    el.style.display = 'block';
    setTimeout(function() { el.style.display = 'none'; }, 4000);
}

function loadList() {
    fetch(apiBase).then(function(res) {
        if (!res.ok) {
            showMsg('목록을 불러오지 못했습니다.', true);
            return res.json().then(function() {});
        }
        return res.json();
    }).then(function(data) {
        if (!data) return;
        var list = Array.isArray(data.results) ? data.results : (Array.isArray(data) ? data : []);
        var searchName = (document.getElementById('search_name') || {}).value;
        if (searchName) {
            searchName = searchName.trim().toLowerCase();
            list = list.filter(function(row) { return (row.name || '').toLowerCase().indexOf(searchName) >= 0; });
        }
        list.forEach(function(row) {
            row.delivery_fee_template_name = (row.delivery_fee_template_name != null && row.delivery_fee_template_name !== '') ? row.delivery_fee_template_name : '-';
            row.option_summary = (row.option_summary != null && row.option_summary !== '') ? row.option_summary : '-';
        });
        gridData = Array.isArray(list) ? list : [];
        if (gridInstance && typeof gridInstance.setData === 'function') {
            gridInstance.setData(gridData);
        }
    }).catch(function() { showMsg('목록을 불러오지 못했습니다.', true); });
}

function loadCategories(cb) {
    fetch('/admin/categories/api.php').then(function(r) { return r.json(); }).then(function(data) {
        categories = Array.isArray(data.results) ? data.results : [];
        var sel = document.getElementById('modal-category_id');
        if (!sel) return;
        sel.innerHTML = '<option value="">선택</option>';
        categories.forEach(function(c) {
            var opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name || ('ID ' + c.id);
            sel.appendChild(opt);
        });
        if (cb) cb();
    });
}

function loadDeliveryTemplates(cb) {
    fetch('/admin/delivery_fee_templates/api.php').then(function(r) { return r.json(); }).then(function(data) {
        deliveryTemplates = Array.isArray(data.results) ? data.results : [];
        var sel = document.getElementById('modal-delivery_fee_template_id');
        if (!sel) return;
        sel.innerHTML = '<option value="">없음 (기본)</option>';
        deliveryTemplates.forEach(function(t) {
            var opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = (t.name || ('ID ' + t.id)) + (t.base_fee != null ? ' - ' + t.base_fee + '원' : '');
            sel.appendChild(opt);
        });
        if (cb) cb();
    });
}

function initGrid() {
    if (typeof ax5 === 'undefined' || !ax5.ui || !ax5.ui.grid) {
        setTimeout(initGrid, 100);
        return;
    }
    var $ = window.jQuery;
    gridInstance = new ax5.ui.grid();
    gridInstance.setConfig({
        target: $('[data-ax5grid="product-grid"]'),
        showLineNumber: true,
        showRowSelector: true,
        multipleSelect: true,
        lineNumberColumnWidth: 36,
        rowSelectorColumnWidth: 28,
        header: { align: 'center', columnHeight: 28 },
        body: { align: 'left', columnHeight: 28 },
        columns: [
            { key: 'id', label: 'ID', width: 70, align: 'left' },
            { key: 'name', label: '상품명', width: 160, align: 'left' },
            { key: 'category_name', label: '카테고리', width: 90, align: 'left' },
            { key: 'option_summary', label: '옵션', width: 200, align: 'left' },
            { key: 'price', label: '가격', width: 90, align: 'right' },
            { key: 'stock', label: '재고', width: 60, align: 'right' },
            { key: 'delivery_fee_template_name', label: '배송비템플릿', width: 100, align: 'left' },
            { key: 'is_active', label: '노출', width: 50, align: 'center' },
            { key: 'created_at', label: '등록일', width: 150, align: 'left' }
        ]
    });
    gridInstance.setData(Array.isArray(gridData) ? gridData : []);
}

var productModalEl = null;
var productFormEl = null;

function openProductModal() {
    if (!productModalEl) productModalEl = document.getElementById('product-modal');
    if (productModalEl) productModalEl.classList.add('is-open');
}

function closeProductModal() {
    if (!productModalEl) productModalEl = document.getElementById('product-modal');
    if (productModalEl) productModalEl.classList.remove('is-open');
}

function addOptionGroup(data) {
    data = data || {};
    var container = document.getElementById('option-groups-container');
    var g = document.createElement('div');
    g.className = 'option-group-card';
    g.innerHTML =
        '<div class="option-group-head">' +
        '  <input type="text" class="option-group-name" placeholder="그룹명 (예: 색상, 사이즈)" value="' + (data.name || '').replace(/"/g, '&quot;') + '">' +
        '  <select class="option-group-type"><option value="combination"' + (data.option_type === 'combination' ? ' selected' : '') + '>조합형</option><option value="simple"' + (data.option_type === 'simple' ? ' selected' : '') + '>단독형</option><option value="text"' + (data.option_type === 'text' ? ' selected' : '') + '>직접입력</option></select>' +
        '  <input type="text" class="option-group-key" placeholder="SKU키(조합형, 예: color)" value="' + (data.option_key || '').replace(/"/g, '&quot;') + '" title="조합형일 때만 사용">' +
        '  <label class="option-group-required"><input type="checkbox" class="option-group-is-required"' + (data.is_required ? ' checked' : '') + '> 필수</label>' +
        '  <button type="button" class="btn btn-small btn-remove-group">삭제</button>' +
        '</div>' +
        '<div class="option-items-list"></div>' +
        '<button type="button" class="btn btn-small btn-add-item">+ 항목 추가</button>';
    container.appendChild(g);
    var itemsList = g.querySelector('.option-items-list');
    (data.items || []).forEach(function(it) {
        addOptionItem(itemsList, it);
    });
    g.querySelector('.btn-add-item').addEventListener('click', function() { addOptionItem(itemsList, {}); });
    g.querySelector('.btn-remove-group').addEventListener('click', function() { g.remove(); });
    return g;
}

function addOptionItem(container, data) {
    data = data || {};
    var row = document.createElement('div');
    row.className = 'option-item-row';
    row.innerHTML =
        '<input type="text" class="item-name" placeholder="표시명" value="' + (data.name || '').replace(/"/g, '&quot;') + '">' +
        '<input type="text" class="item-value" placeholder="값(SKU용)" value="' + (data.value != null ? String(data.value).replace(/"/g, '&quot;') : '') + '">' +
        '<input type="number" class="item-price" placeholder="추가금액" value="' + (data.option_price != null ? data.option_price : 0) + '" min="0">' +
        '<button type="button" class="btn btn-small btn-remove-item">삭제</button>';
    container.appendChild(row);
    row.querySelector('.btn-remove-item').addEventListener('click', function() { row.remove(); });
}

function getOptionGroupsFromForm() {
    var groups = [];
    document.querySelectorAll('#option-groups-container .option-group-card').forEach(function(card) {
        var name = (card.querySelector('.option-group-name') || {}).value;
        if (!name || !name.trim()) return;
        var optionType = (card.querySelector('.option-group-type') || {}).value || 'combination';
        var optionKey = (card.querySelector('.option-group-key') || {}).value.trim();
        var isRequired = (card.querySelector('.option-group-is-required') || {}).checked ? 1 : 0;
        var items = [];
        (card.querySelectorAll('.option-item-row') || []).forEach(function(row) {
            var itemName = (row.querySelector('.item-name') || {}).value;
            if (!itemName || !itemName.trim()) return;
            items.push({
                name: itemName.trim(),
                value: (row.querySelector('.item-value') || {}).value.trim() || itemName.trim(),
                option_price: parseInt((row.querySelector('.item-price') || {}).value, 10) || 0
            });
        });
        groups.push({ name: name.trim(), option_type: optionType, option_key: optionKey || null, is_required: isRequired, items: items });
    });
    return groups;
}

function setOptionGroups(groups) {
    var container = document.getElementById('option-groups-container');
    container.innerHTML = '';
    (groups || []).forEach(function(g) {
        addOptionGroup({
            name: g.name,
            option_type: g.option_type || 'combination',
            option_key: g.option_key || '',
            is_required: g.is_required,
            items: (g.items || []).map(function(it) { return { name: it.name, value: it.value, option_price: it.option_price != null ? it.option_price : 0 }; })
        });
    });
}

function doEdit(id) {
    fetch(apiBase + '?id=' + id).then(function(r) {
        if (!r.ok) { showMsg('상품 정보를 불러오지 못했습니다.', true); return null; }
        return r.json();
    }).then(function(row) {
        if (!row) return;
        document.getElementById('modal-category_id').value = row.category_id || '';
        document.getElementById('modal-name').value = row.name || '';
        document.getElementById('modal-slug').value = row.slug || '';
        document.getElementById('modal-price').value = row.price ?? 0;
        document.getElementById('modal-stock').value = row.stock ?? 0;
        document.getElementById('modal-image_url').value = row.image_url || '';
        document.getElementById('modal-description').value = row.description || '';
        document.getElementById('modal-delivery_fee_template_id').value = row.delivery_fee_template_id != null && row.delivery_fee_template_id !== '' ? String(row.delivery_fee_template_id) : '';
        document.getElementById('modal-is_active').checked = row.is_active != null ? !!row.is_active : true;
        setOptionGroups(Array.isArray(row.option_groups) ? row.option_groups : []);
        productFormEl.dataset.editId = id;
        document.getElementById('product-modal-title').textContent = '상품 수정';
        openProductModal();
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

productFormEl = document.getElementById('form-product');

document.getElementById('btn-search').addEventListener('click', loadList);
document.getElementById('btn-add-option-group').addEventListener('click', function() {
    addOptionGroup({});
});

document.getElementById('btn-input').addEventListener('click', function() {
    productFormEl.reset();
    document.getElementById('modal-price').value = 0;
    document.getElementById('modal-stock').value = 0;
    document.getElementById('modal-is_active').checked = true;
    setOptionGroups([]);
    productFormEl.dataset.editId = '';
    document.getElementById('product-modal-title').textContent = '상품 입력';
    document.getElementById('product-modal-error').style.display = 'none';
    loadCategories(function() { loadDeliveryTemplates(openProductModal); });
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
    loadCategories(function() { loadDeliveryTemplates(function() { doEdit(selected[0].id); }); });
});
document.getElementById('btn-delete').addEventListener('click', doDeleteSelected);

document.getElementById('product-modal').addEventListener('click', function(e) {
    if (e.target === this) closeProductModal();
});
document.getElementById('product-modal-cancel').addEventListener('click', closeProductModal);

productFormEl.addEventListener('submit', function(e) {
    e.preventDefault();
    var errEl = document.getElementById('product-modal-error');
    errEl.style.display = 'none';
    var editId = productFormEl.dataset.editId || '';
    var catVal = document.getElementById('modal-category_id').value;
    if (!catVal) {
        errEl.textContent = '카테고리를 선택하세요.';
        errEl.style.display = 'block';
        return;
    }
    var body = {
        category_id: parseInt(catVal, 10),
        name: document.getElementById('modal-name').value.trim(),
        slug: document.getElementById('modal-slug').value.trim(),
        price: parseInt(document.getElementById('modal-price').value, 10) || 0,
        stock: parseInt(document.getElementById('modal-stock').value, 10) || 0,
        image_url: document.getElementById('modal-image_url').value.trim(),
        description: document.getElementById('modal-description').value.trim(),
        delivery_fee_template_id: document.getElementById('modal-delivery_fee_template_id').value || null,
        is_active: document.getElementById('modal-is_active').checked ? 1 : 0,
        option_groups: getOptionGroupsFromForm()
    };
    var url = editId ? apiBase + '?id=' + editId : apiBase;
    var method = editId ? 'PUT' : 'POST';
    fetch(url, { method: method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
        .then(function(res) { return res.json().then(function(data) { return { res: res, data: data }; }); })
        .then(function(r) {
            if (r.res.ok) {
                showMsg(editId ? '수정되었습니다.' : '등록되었습니다.');
                closeProductModal();
                productFormEl.reset();
                productFormEl.dataset.editId = '';
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

loadCategories();
loadDeliveryTemplates();
loadList();
initGrid();
})();
</script>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
