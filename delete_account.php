<?php
session_start();
require_once 'db.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();

    session_start();
    $_SESSION['flash'] = 'Your account has been deleted.';
    header('Location: login.php');
    exit;
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Account</title>
    <style>
        body{font-family:Arial,sans-serif;max-width:500px;margin:40px auto;background:#111;color:#fff;}
        .box{padding:20px;border-radius:10px;background:#222;border:1px solid #444;}
        .actions{display:flex;gap:10px;margin-top:16px;}
        button,a{padding:10px 14px;border-radius:6px;text-decoration:none;border:none;cursor:pointer;}
        button{background:#b00020;color:#fff;}
        a{background:#555;color:#fff;display:inline-block;}
    </style>
</head>
<body>
    <div class="box">
        <h1>Delete Account</h1>
        <p>Are you sure you want to permanently delete your account?</p>
        <div class="actions">
            <form method="post">
                <button type="submit">Yes, Delete Account</button>
            </form>
            <a href="index.php">Cancel</a>
        </div>
    </div>
</body>
</html>
