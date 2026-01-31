<?php
require_once __DIR__ . '/../inc/auth.php';

if (!empty($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: /admin/login/view.php');
    exit;
}

if (isAdminLoggedIn()) {
    header('Location: /admin/index.php');
    exit;
}

$pageTitle = '로그인';
$error = '';
require_once __DIR__ . '/../inc/header.php';
?>

<div class="admin-login">
    <div class="card">
        <h1>관리자 로그인</h1>
        <form id="login-form">
            <div class="field">
                <label for="email">이메일</label>
                <input type="email" id="email" name="email" placeholder="admin@haru.local" value="admin@haru.local" required>
            </div>
            <div class="field">
                <label for="password">비밀번호</label>
                <input type="password" id="password" name="password" placeholder="비밀번호" required>
            </div>
            <p id="login-error" class="error" style="display:none;"></p>
            <button type="submit">로그인</button>
        </form>
    </div>
</div>

<script>
document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errEl = document.getElementById('login-error');
    errEl.style.display = 'none';
    const res = await fetch('/admin/login/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            email: document.getElementById('email').value,
            password: document.getElementById('password').value
        })
    });
    const data = await res.json();
    if (res.ok && data.ok) {
        window.location.href = '/admin/index.php';
        return;
    }
    errEl.textContent = data.error || '로그인에 실패했습니다.';
    errEl.style.display = 'block';
});
</script>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
