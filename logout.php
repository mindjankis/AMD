<?php
session_start();

$data_file = __DIR__ . '/play_list_data.json';
if (file_exists($data_file)) {
    file_put_contents($data_file, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();
header('Location: login.php');
exit;
