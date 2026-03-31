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
        $url_message = 'URL list cleared successfully.';
    }
}

$playlist_urls = json_decode((string) file_get_contents($data_file), true);
if (!is_array($playlist_urls)) {
    $playlist_urls = [];
}
$playlist_urls_json = json_encode(array_values($playlist_urls), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
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
            <details class="menu">
                <summary>Settings ▾</summary>
                <div class="menu-items">
                    <a class="danger" href="delete_account.php">Delete Account</a>
                    <a href="logout.php">Logout</a>
                </div>
            </details>
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
                <ul id="playlistList">
                    <?php foreach ($playlist_urls as $index => $saved_url): ?>
                        <li>
                            <a href="<?= htmlspecialchars($saved_url) ?>" target="_blank" rel="noopener noreferrer">
                                <?= $index + 1 ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="play-actions">
                    <button type="button" id="playButton">PLAY</button>
                    <form method="post" style="display:inline;">
                        <button type="submit" id="clearButton" name="clear_urls">Clear URLs List</button>
                    </form>
                    <p id="playStatus" class="play-status"></p>
                </div>
            <?php else: ?>
                <p>No URLs added yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <img src="Picture_1.jpg" alt="Corner picture" class="corner-picture">

    <script>
        const playlistUrls = <?= $playlist_urls_json ?: '[]' ?>;
        const playButton = document.getElementById('playButton');
        const playlistList = document.getElementById('playlistList');
        const playStatus = document.getElementById('playStatus');

        function shufflePlaylist(items) {
            for (let i = items.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [items[i], items[j]] = [items[j], items[i]];
            }
            return items;
        }

        if (playButton) {
            playButton.addEventListener('click', () => {
                if (!playlistUrls.length) {
                    if (playStatus) {
                        playStatus.textContent = 'No URLs are available to play.';
                    }
                    return;
                }

                if (playlistList) {
                    playlistList.classList.add('hidden');
                }

                const shuffledUrls = shufflePlaylist([...playlistUrls]);
                let currentIndex = 0;
                const delayMs = 8000;
                let playerWindow = window.open('about:blank', 'playlistPlayer');

                if (!playerWindow) {
                    playStatus.textContent = 'Please allow pop-ups so the URLs can open in your browser or media app.';
                    return;
                }

                const playNext = () => {
                    if (currentIndex >= shuffledUrls.length) {
                        playStatus.textContent = `Playback finished. ${shuffledUrls.length} URL(s) were opened once in random order.`;
                        return;
                    }

                    const nextUrl = shuffledUrls[currentIndex];
                    playStatus.textContent = `Playing item ${currentIndex + 1} of ${shuffledUrls.length} in random order...`;

                    if (playerWindow.closed) {
                        playerWindow = window.open('about:blank', 'playlistPlayer');
                        if (!playerWindow) {
                            playStatus.textContent = 'Playback stopped because the player window was blocked.';
                            return;
                        }
                    }

                    playerWindow.location.href = nextUrl;
                    currentIndex++;
                    setTimeout(playNext, delayMs);
                };

                playNext();
            });
        }
    </script>
</body>
</html>
