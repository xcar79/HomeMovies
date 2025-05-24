<?php
define('VLC_HOST', '127.0.0.1');
define('VLC_PORT', 8080);
define('VLC_PASS', 'vlc123');
$statusUrl = 'http://' . VLC_HOST . ':' . VLC_PORT . '/requests/status.xml';
define('MOVIES_PATH', 'H:\\Peliculas');
$validExt = ['mp4','avi','mkv','mov'];
$movies = array_filter(scandir(MOVIES_PATH), function($f) use ($validExt) {
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    return is_file(MOVIES_PATH . DIRECTORY_SEPARATOR . $f) && in_array($ext, $validExt);
});
$ctx = stream_context_create([ 'http' => [
    'header'  => 'Authorization: Basic ' . base64_encode(':' . VLC_PASS),
    'timeout' => 1
]]);
if (isset($_GET['ajax']) && $_GET['ajax'] === 'status') {
    $response = ['state'=>'N/A','time'=>0,'length'=>0];
    if ($xml = @file_get_contents($statusUrl, false, $ctx)) {
        $s = simplexml_load_string($xml);
        $response['state']  = (string)$s->state;
        $response['time']   = (int)$s->time;
        $response['length'] = (int)$s->length;
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    if (isset($_POST['close'])) {
        shell_exec('taskkill /IM vlc.exe /F');
    }
    if (isset($_POST['position'])) {
        $pos = intval($_POST['position']);
        $length = 0;
        if ($xml = @file_get_contents($statusUrl, false, $ctx)) {
            $s = simplexml_load_string($xml);
            $length = (int)$s->length;
        }
        if ($length > 0) {
            $percent = round(($pos / $length) * 100);
            $seekVal = rawurlencode($percent . '%');
            @file_get_contents($statusUrl . "?command=seek&val={$seekVal}", false, $ctx);
        }
    }
    if (isset($_POST['command'])) {
        @file_get_contents($statusUrl . "?command=" . $_POST['command'], false, $ctx);
    }
    echo json_encode(['ok'=>true]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['file'])) {
        $file = basename($_POST['file']);
        if (in_array($file, $movies)) {
            shell_exec('taskkill /IM vlc.exe /F');
            usleep(500000);
            $path = MOVIES_PATH . DIRECTORY_SEPARATOR . $file;
            pclose(popen(
              "\"C:\\Program Files\\VideoLAN\\VLC\\vlc.exe\" --intf http --http-password=" . VLC_PASS .
              " --fullscreen --one-instance --no-video-title-show \"$path\"", 
            'r'));
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Control VLC</title>
  <style>
    html, body {
      height: 100%;
      margin: 0;
      background: #000;
      color: #fff;
    }
    .container {
      display: flex;
      flex-direction: column;
      height: 100%;
    }
    .search-wrapper {
      position: relative;
      display: flex;
      align-items: center;
      padding: 10px;
      background: #111;
    }
    .search-wrapper .home {
      font-size: 24px;
      color: #fff;
      margin-right: 8px;
      cursor: pointer;
    }
    #search {
      flex: 1;
      padding: 8px 32px 8px 8px;
      font-size: 16px;
      background: #222;
      border: 1px solid #555;
      color: #fff;
    }
    #search::-ms-clear { display: none; }
    .search-wrapper .clear-btn {
      position: absolute;
      right: 20px;
      font-size: 18px;
      color: #aaa;
      cursor: pointer;
      display: none;
    }
    #list {
      flex: 1;
      overflow-y: auto;
      padding: 10px;
      list-style: none;
      margin: 0;
    }
    #list li {
      margin: 5px 0;
    }
    .btn {
      background: #222;
      border: 1px solid #555;
      color: #fff;
      padding: 8px 16px;
      cursor: pointer;
    }
    .btn:hover {
      background: #444;
    }
    .controls {
      display: flex;
      align-items: center;
      padding: 10px;
      background: #111;
    }
    .controls button {
      font-size: 24px;
      padding: 12px;
      margin: 0 10px;
      background: #222;
      border: 1px solid #555;
      color: #fff;
      flex-shrink: 0;
    }
    .slider {
      flex: 1;
      margin: 0 8px;
    }
    @media (max-width: 600px) {
      .search-wrapper {
        padding: 16px;
      }
      #search {
        padding: 16px 48px 16px 8px;
        font-size: 20px;
      }
      .controls {
        padding: 16px;
      }
      .controls button {
        font-size: 22px;
        padding: 8px 10px;
        margin: 0 8px;
      }
      .slider {
        height: 24px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="search-wrapper">
      <span class="home" onclick="location.reload()">🏠</span>
      <input id="search" type="text" placeholder="Buscar...">
      <span class="clear-btn" id="clearSearch">✖️</span>
    </div>
    <ul id="list">
      <?php foreach($movies as $m): ?>
      <li>
        <form method="post" style="display:inline;">
          <input type="hidden" name="file" value="<?=htmlspecialchars($m)?>">
          <button class="btn"><?=htmlspecialchars($m)?></button>
        </form>
      </li>
      <?php endforeach; ?>
    </ul>
    <div class="controls">
      <button id="playpause">⏯️</button>
      <input id="progress" class="slider" type="range" min="0" max="100" value="0">
      <button id="close">❌</button>
    </div>
  </div>
  <script>
    const search = document.getElementById('search');
    const clearBtn = document.getElementById('clearSearch');
    search.addEventListener('input', () => {
      clearBtn.style.display = search.value ? 'block' : 'none';
      const term = search.value.toLowerCase();
      document.querySelectorAll('#list li').forEach(li => {
        li.style.display = li.textContent.toLowerCase().includes(term) ? '' : 'none';
      });
    });
    clearBtn.addEventListener('click', () => {
      search.value = '';
      clearBtn.style.display = 'none';
      search.dispatchEvent(new Event('input'));
    });
    function ajax(data) {
      fetch('', { method: 'POST', body: data });
    }
    document.getElementById('playpause').onclick = () => {
      const fd = new FormData();
      fd.append('ajax','1');
      fd.append('command','pl_pause');
      ajax(fd);
    };
    document.getElementById('close').onclick = () => {
      const fd = new FormData();
      fd.append('ajax','1');
      fd.append('close','1');
      ajax(fd);
    };
    const slider = document.getElementById('progress');
    slider.oninput = () => {
      const fd = new FormData();
      fd.append('ajax','1');
      fd.append('position', slider.value);
      ajax(fd);
    };
    setInterval(() => {
      fetch('?ajax=status')
        .then(r => r.json())
        .then(d => {
          slider.max = d.length;
          slider.value = d.time;
        });
    }, 1000);
  </script>
</body>
</html>
