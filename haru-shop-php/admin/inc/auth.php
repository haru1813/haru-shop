<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        if (isApiRequest()) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        header('Location: /admin/login/view.php');
        exit;
    }
}

function isApiRequest(): bool {
    return str_contains($_SERVER['REQUEST_URI'] ?? '', 'api.php') ||
        (!empty($_GET['api']) && $_GET['api'] === '1');
}
