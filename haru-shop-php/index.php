<?php
/**
 * 첫 화면: 로그인 (Nuxt admin login 스타일)
 * 로그인 시 /admin/index.php 로 이동
 */
require_once __DIR__ . '/admin/inc/auth.php';

if (isAdminLoggedIn()) {
    header('Location: /admin/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인 - Haru Shop 관리자</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f1f5f9; }
        .admin-login { width: 100%; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .card { background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); width: 100%; max-width: 360px; }
        .card h1 { margin: 0 0 1.5rem; font-size: 1.25rem; }
        .field { margin-bottom: 1rem; }
        .field label { display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #475569; }
        .field input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem; box-sizing: border-box; }
        .error { color: #dc2626; font-size: 0.875rem; margin-bottom: 0.5rem; }
        button { width: 100%; padding: 0.75rem; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; margin-top: 0.5rem; }
        button:hover { opacity: 0.95; }
        button:disabled { opacity: 0.7; cursor: not-allowed; }
    </style>
</head>
<body>
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
</body>
</html>
