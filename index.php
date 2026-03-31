<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_email = $_SESSION['user_email'] ?? 'Unknown';
$data_file = __DIR__ . '/play_list_data.json';
$url_message = '';
$url_error = '';
$entered_url = '';

if (!file_exists($data_file)) {
    file_put_contents($data_file, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_url'])) {
        $entered_url = trim($_POST['playlist_url'] ?? '');

        if ($entered_url === '' || !filter_var($entered_url, FILTER_VALIDATE_URL)) {
            $url_error = 'Please enter a valid URL.';
        } else {
            $saved_urls = json_decode((string) file_get_contents($data_file), true);
            if (!is_array($saved_urls)) {
                $saved_urls = [];
            }

            $saved_urls[] = $entered_url;
            file_put_contents($data_file, json_encode($saved_urls, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $url_message = 'URL added successfully.';
            $entered_url = '';
        }
    }

    if (isset($_POST['clear_urls'])) {
        file_put_contents($data_file, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        unset($_SESSION['play_queue'], $_SESSION['play_index']);
        $url_message = 'URL list cleared successfully.';
    }
}

$playlist_urls = json_decode((string) file_get_contents($data_file), true);
if (!is_array($playlist_urls)) {
    $playlist_urls = [];
}

if (isset($_POST['play_urls'])) {
    if ($playlist_urls) {
        $play_queue = array_values($playlist_urls);
        shuffle($play_queue);
        $_SESSION['play_queue'] = $play_queue;
        $_SESSION['play_index'] = 0;
        header('Location: index.php?play=1');
        exit;
    }

    $url_error = 'No URLs are available to play.';
}

if (isset($_GET['next']) && isset($_SESSION['play_queue'], $_SESSION['play_index'])) {
    $_SESSION['play_index']++;
}

function get_playable_url($url) {
    $parts = parse_url($url);
    if (!$parts || empty($parts['host'])) {
        return $url;
    }

    $host = strtolower($parts['host']);

    if (strpos($host, 'youtube.com') !== false) {
        parse_str($parts['query'] ?? '', $query);
        if (!empty($query['v'])) {
            return 'https://www.youtube.com/embed/' . rawurlencode($query['v']) . '?autoplay=1';
        }
    }

    if (strpos($host, 'youtu.be') !== false) {
        $video_id = trim($parts['path'] ?? '', '/');
        if ($video_id !== '') {
            return 'https://www.youtube.com/embed/' . rawurlencode($video_id) . '?autoplay=1';
        }
    }

    return $url;
}

$play_queue = $_SESSION['play_queue'] ?? [];
$current_play_index = $_SESSION['play_index'] ?? 0;
$play_delay_seconds = 180;
$is_playing = !empty($play_queue) && array_key_exists($current_play_index, $play_queue);
$current_play_url = $is_playing ? $play_queue[$current_play_index] : null;
$current_embedded_url = $current_play_url ? get_playable_url($current_play_url) : null;

if (!empty($play_queue) && $current_play_index >= count($play_queue)) {
    unset($_SESSION['play_queue'], $_SESSION['play_index']);
    $is_playing = false;
    $current_play_url = null;
    $current_embedded_url = null;
    $url_message = 'Playback finished. All URLs were played once in random order.';
}

if ($is_playing && $current_play_url) {
    header('Refresh: ' . $play_delay_seconds . '; url=index.php?play=1&next=1');
}

$help_content = file_exists(__DIR__ . '/README.md')
    ? file_get_contents(__DIR__ . '/README.md')
    : 'README.md file not found.';
?><!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Guess my song</title>
    <style>
        body{
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 40px auto;
            background-image: url('Wallpaper.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            color: #fff;
        }
        a{color:#cfe8ff;text-decoration:none;}
        a:hover{text-decoration:underline;}
        .card{
            border:1px solid rgba(255,255,255,0.25);
            padding:20px;
            border-radius:9px;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(4px);
        }
        .topbar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:16px;
            margin-bottom:16px;
        }
        .topbar-actions{
            display:flex;
            gap:10px;
            align-items:flex-start;
            flex-wrap:wrap;
        }
        .menu{position:relative;}
        .menu summary{
            list-style:none;
            cursor:pointer;
            padding:10px 14px;
            border-radius:6px;
            border:1px solid rgba(255,255,255,0.25);
            background: rgba(255,255,255,0.12);
            user-select:none;
        }
        .menu summary::-webkit-details-marker{display:none;}
        .menu-items{
            position:absolute;
            right:0;
            top:calc(100% + 8px);
            min-width:170px;
            border-radius:8px;
            overflow:hidden;
            background: rgba(0, 0, 0, 0.88);
            border:1px solid rgba(255,255,255,0.18);
            box-shadow:0 8px 20px rgba(0,0,0,0.25);
        }
        .menu-items a{
            display:block;
            padding:10px 12px;
            color:#fff;
        }
        .menu-items a:hover{
            background: rgba(255,255,255,0.1);
            text-decoration:none;
        }
        .help-panel{
            padding:12px 14px;
            min-width:320px;
            max-width:420px;
            max-height:280px;
            overflow-y:auto;
        }
        .help-content{
            white-space:pre-wrap;
            line-height:1.5;
            color:#f5f5f5;
            font-size:14px;
        }
        .danger{color:#ffb3b3 !important;}
        .success{color:#b9ffb9;}
        .error{color:#ffb3b3;}
        .url-form{margin-top:20px;}
        .url-form label{display:block;margin-bottom:8px;font-weight:bold;}
        .url-row{display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
        .url-row input{
            flex:1;
            min-width:260px;
            padding:10px 12px;
            border-radius:6px;
            border:1px solid rgba(255,255,255,0.25);
            background: rgba(255,255,255,0.12);
            color:#fff;
        }
        .url-row input::placeholder{color:#ddd;}
        .url-row button{
            padding:10px 16px;
            border:none;
            border-radius:6px;
            cursor:pointer;
            background:#2d7dff;
            color:#fff;
            font-weight:bold;
        }
        .playlist{margin-top:22px;}
        .playlist ul{padding-left:20px;}
        .playlist li{margin-bottom:8px;word-break:break-all;}
        .play-actions{
            margin-top:16px;
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            align-items:center;
        }
        .play-actions button{
            padding:10px 18px;
            border:none;
            border-radius:6px;
            cursor:pointer;
            color:#fff;
            font-weight:bold;
        }
        #playButton{background:#16a34a;}
        #clearButton{background:#b91c1c;}
        .play-status{margin-top:10px;color:#cfe8ff;}
        .player-box{
            margin-top:18px;
            padding:14px;
            border-radius:10px;
            background: rgba(255,255,255,0.08);
        }
        .player-frame{
            width:100%;
            height:315px;
            border:0;
            border-radius:10px;
            margin-top:10px;
            background:#000;
        }
        .hidden{display:none;}
        .corner-picture{
            position: fixed;
            right: 20px;
            bottom: 20px;
            width: min(270px, 42vw);
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
            pointer-events: none;
            z-index: 1;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="topbar">
            <h1>Guess my song</h1>
            <div class="topbar-actions">
                <details class="menu">
                    <summary>Help ▾</summary>
                    <div class="menu-items help-panel">
                        <div class="help-content"><?= htmlspecialchars($help_content) ?></div>
                    </div>
                </details>
                <details class="menu">
                    <summary>Settings ▾</summary>
                    <div class="menu-items">
                        <a class="danger" href="delete_account.php">Delete Account</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </details>
            </div>
        </div>
        <p>You are logged in as <strong><?= htmlspecialchars($user_email) ?></strong>.</p>

        <?php if ($url_message): ?>
            <p class="success"><?= htmlspecialchars($url_message) ?></p>
        <?php endif; ?>

        <?php if ($url_error): ?>
            <p class="error"><?= htmlspecialchars($url_error) ?></p>
        <?php endif; ?>

        <form method="post" class="url-form">
            <label for="playlist_url">Enter URL</label>
            <div class="url-row">
                <input
                    type="url"
                    id="playlist_url"
                    name="playlist_url"
                    placeholder="https://example.com"
                    value="<?= htmlspecialchars($entered_url) ?>"
                    required
                >
                <button type="submit" name="add_url">ADD</button>
            </div>
        </form>

        <div class="playlist">
            <h2>Saved URLs</h2>
            <?php if ($playlist_urls): ?>
                <?php if (!$is_playing): ?>
                    <ul id="playlistList">
                        <?php foreach ($playlist_urls as $index => $saved_url): ?>
                            <li>
                                <a href="<?= htmlspecialchars($saved_url) ?>" target="_blank" rel="noopener noreferrer">
                                    <?= $index + 1 ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="play-actions">
                    <form method="post" style="display:inline;">
                        <button type="submit" id="playButton" name="play_urls">PLAY</button>
                    </form>
                    <form method="post" style="display:inline;">
                        <button type="submit" id="clearButton" name="clear_urls">Clear URLs List</button>
                    </form>
                </div>

                <?php if ($is_playing && $current_play_url): ?>
                    <div class="player-box">
                        <h3>Now Playing</h3>
                        <p class="play-status">
                            Playing item <?= $current_play_index + 1 ?> of <?= count($play_queue) ?> in random order...
                        </p>
                        <iframe
                            class="player-frame"
                            src="<?= htmlspecialchars($current_embedded_url) ?>"
                            allow="autoplay; encrypted-media"
                            allowfullscreen
                            loading="lazy"
                        ></iframe>
                        <p>
                            <a href="<?= htmlspecialchars($current_play_url) ?>" target="_blank" rel="noopener noreferrer">
                                Open current URL directly
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p>No URLs added yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <img src="Picture_1.jpg" alt="Corner picture" class="corner-picture">

    <script>
        const dropdownMenus = document.querySelectorAll('.menu');

        document.addEventListener('click', (event) => {
            dropdownMenus.forEach((menu) => {
                if (menu.open && !menu.contains(event.target)) {
                    menu.open = false;
                }
            });
        });
    </script>
</body>
</html>
