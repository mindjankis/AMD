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
$current_play_index = $_SESSION['current_play_index'] ?? -1;
$is_finished_playing = false;

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
        unset($_SESSION['current_play_index']);
        $current_play_index = -1;
        $is_finished_playing = false;
        $url_message = 'URL list cleared successfully.';
    }

    if (isset($_POST['shuffle_urls'])) {
        $saved_urls = json_decode((string) file_get_contents($data_file), true);
        if (!is_array($saved_urls)) {
            $saved_urls = [];
        }

        if (count($saved_urls) > 0) {
            shuffle($saved_urls);
            file_put_contents($data_file, json_encode($saved_urls, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            unset($_SESSION['current_play_index']);
            $current_play_index = -1;
            $is_finished_playing = false;
            $url_message = 'URLs shuffled successfully.';
        } else {
            $url_error = 'No URLs to shuffle.';
        }
    }

    if (isset($_POST['advance_play'])) {
        $saved_urls = json_decode((string) file_get_contents($data_file), true);
        if (!is_array($saved_urls)) {
            $saved_urls = [];
        }

        if (count($saved_urls) > 0) {
            $next_index = $current_play_index + 1;

            if ($next_index < count($saved_urls)) {
                $current_play_index = $next_index;
                $_SESSION['current_play_index'] = $current_play_index;

                if ($current_play_index === count($saved_urls) - 1) {
                    $url_message = 'Last URL opened. Playback will stop after this track.';
                } else {
                    $url_message = 'Track ' . ($current_play_index + 1) . ' opened successfully.';
                }
            } else {
                $is_finished_playing = true;
                $_SESSION['current_play_index'] = max(count($saved_urls) - 1, -1);
                $url_message = 'All URLs have already been played.';
            }
        } else {
            $url_error = 'No URLs available to play.';
        }
    }
}

if (isset($_GET['reset_play'])) {
    unset($_SESSION['current_play_index']);
    header('Location: index.php');
    exit;
}

$playlist_urls = json_decode((string) file_get_contents($data_file), true);
if (!is_array($playlist_urls)) {
    $playlist_urls = [];
}

$playlist_count = count($playlist_urls);
$next_play_index = $current_play_index + 1;
$next_play_url = ($next_play_index >= 0 && $next_play_index < $playlist_count)
    ? $playlist_urls[$next_play_index]
    : null;

$current_play_url = ($current_play_index >= 0 && $current_play_index < $playlist_count)
    ? $playlist_urls[$current_play_index]
    : null;

function extract_youtube_video_id($url) {
    $parts = parse_url((string) $url);
    if (!$parts || empty($parts['host'])) {
        return null;
    }

    $host = strtolower((string) $parts['host']);

    if (strpos($host, 'youtu.be') !== false) {
        $video_id = trim((string) ($parts['path'] ?? ''), '/');
        return $video_id !== '' ? $video_id : null;
    }

    if (strpos($host, 'youtube.com') !== false) {
        parse_str((string) ($parts['query'] ?? ''), $query);
        if (!empty($query['v'])) {
            return (string) $query['v'];
        }

        $path_parts = array_values(array_filter(explode('/', trim((string) ($parts['path'] ?? ''), '/'))));
        if (isset($path_parts[0], $path_parts[1]) && in_array($path_parts[0], ['embed', 'shorts'], true)) {
            return (string) $path_parts[1];
        }
    }

    return null;
}

$current_play_video_id = $current_play_url ? extract_youtube_video_id($current_play_url) : null;

if ($playlist_count > 0 && $next_play_url === null && $current_play_index >= 0) {
    $is_finished_playing = true;
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
        #shuffleButton{background:#f59e0b;}
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
                <ul id="playlistList">
                    <?php foreach ($playlist_urls as $index => $saved_url): ?>
                        <li>
                            <a href="<?= htmlspecialchars($saved_url) ?>" target="_blank" rel="noopener noreferrer">
                                <?= $index + 1 ?>
                            </a>
                            <?php if ($index === $current_play_index): ?>
                                <span style="color: #16a34a; font-weight: bold;">(Currently Playing)</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($current_play_index >= 0 && $current_play_index < $playlist_count): ?>
                    <p style="margin-top: 12px; color: #cfe8ff;">
                        <strong>Current Track:</strong> Track <?= $current_play_index + 1 ?> of <?= $playlist_count ?>
                    </p>
                <?php endif; ?>

                <?php if ($current_play_video_id): ?>
                    <div class="player-box">
                        <h3>Embedded Player</h3>
                        <p id="playerStatusText" class="play-status">Loading player...</p>
                        <div id="ytPlayer" class="player-frame"></div>
                    </div>
                <?php elseif ($current_play_url): ?>
                    <p class="error" style="margin-top: 12px;">
                        Current track is not a supported YouTube URL for embedded playback.
                    </p>
                <?php endif; ?>

                <?php if ($next_play_url && !$is_finished_playing): ?>
                    <p style="margin-top: 12px; color: #cfe8ff;">
                        <strong>Next Track:</strong> Track <?= $next_play_index + 1 ?> of <?= $playlist_count ?>
                    </p>
                <?php endif; ?>

                <?php if ($is_finished_playing): ?>
                    <p style="margin-top: 12px; color: #b9ffb9; font-weight: bold;">
                        ✓ All URLs have been played!
                    </p>
                    <a href="index.php?reset_play=1" style="display: inline-block; margin-top: 10px; padding: 8px 14px; background: #2d7dff; color: #fff; border-radius: 6px; text-decoration: none; font-weight: bold;">
                        Reset & Play Again
                    </a>
                <?php endif; ?>

                <div class="play-actions">
                    <form method="post" style="display:inline;">
                        <button type="submit" id="playButton" name="advance_play" <?= $next_play_url && !$is_finished_playing ? '' : 'disabled style="opacity: 0.5; cursor: not-allowed;"' ?>>
                            <?= $current_play_index < 0 ? 'Play' : 'Play Next' ?>
                        </button>
                    </form>
                    <form method="post" style="display:inline;">
                        <button type="submit" id="shuffleButton" name="shuffle_urls">Shuffle</button>
                    </form>
                    <form method="post" style="display:inline;">
                        <button type="submit" id="clearButton" name="clear_urls">Clear URLs List</button>
                    </form>
                </div>
            <?php else: ?>
                <p>No URLs added yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <img src="Picture_1.jpg" alt="Corner picture" class="corner-picture">

    <script src="https://www.youtube.com/iframe_api"></script>
    <script>
        const dropdownMenus = document.querySelectorAll('.menu');
        const currentVideoId = <?= json_encode($current_play_video_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const playerStatusText = document.getElementById('playerStatusText');
        let ytPlayer = null;
        let ytInitialized = false;

        document.addEventListener('click', (event) => {
            dropdownMenus.forEach((menu) => {
                if (menu.open && !menu.contains(event.target)) {
                    menu.open = false;
                }
            });
        });

        function setPlayerStatus(message) {
            if (playerStatusText) {
                playerStatusText.textContent = message;
            }
        }

        function tryStartPlayback() {
            if (!ytPlayer || typeof ytPlayer.playVideo !== 'function') {
                return;
            }

            ytPlayer.playVideo();

            // On mobile browsers autoplay with sound may be blocked.
            window.setTimeout(() => {
                if (!ytPlayer || typeof ytPlayer.getPlayerState !== 'function') {
                    return;
                }

                if (ytPlayer.getPlayerState() !== YT.PlayerState.PLAYING) {
                    ytPlayer.mute();
                    ytPlayer.playVideo();
                    setPlayerStatus('Autoplay blocked with sound. Playing muted; unmute in player controls.');
                }
            }, 800);
        }

        function initializeYouTubePlayer() {
            if (ytInitialized || !currentVideoId || !document.getElementById('ytPlayer')) {
                return;
            }

            ytInitialized = true;

            ytPlayer = new YT.Player('ytPlayer', {
                width: '100%',
                height: '315',
                videoId: currentVideoId,
                playerVars: {
                    autoplay: 1,
                    playsinline: 1,
                    rel: 0,
                    modestbranding: 1
                },
                events: {
                    onReady: () => {
                        setPlayerStatus('Player ready. Starting track...');
                        tryStartPlayback();
                    },
                    onStateChange: (event) => {
                        if (event.data === YT.PlayerState.PLAYING) {
                            setPlayerStatus('Now playing.');
                        } else if (event.data === YT.PlayerState.PAUSED) {
                            setPlayerStatus('Paused.');
                        } else if (event.data === YT.PlayerState.ENDED) {
                            setPlayerStatus('Track ended. Click Play Next to continue.');
                        }
                    },
                    onError: (event) => {
                        const errorMap = {
                            2: 'Invalid YouTube video ID.',
                            5: 'HTML5 player error.',
                            100: 'Video not found or removed.',
                            101: 'Video cannot be played in embedded players.',
                            150: 'Video cannot be played in embedded players.'
                        };
                        setPlayerStatus(errorMap[event.data] || 'Unable to play this video in embedded mode.');
                    }
                }
            });
        }

        window.onYouTubeIframeAPIReady = initializeYouTubePlayer;

        if (window.YT && window.YT.Player) {
            initializeYouTubePlayer();
        }
    </script>
</body>
</html>
