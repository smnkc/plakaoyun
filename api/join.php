<?php
header('Content-Type: application/json');
require_once 'cleanup.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['code'])) {
    echo json_encode(['error' => 'Geçersiz istek.']);
    exit;
}

$code = $input['code'];
$playerName = isset($input['playerName']) ? $input['playerName'] : 'Rakip';
$file = __DIR__ . '/data/' . $code . '.json';

$fp = fopen($file, 'c+');
if (!$fp) {
    echo json_encode(['error' => 'Dosya erişim hatası.']);
    exit;
}

flock($fp, LOCK_EX);
$jsonContent = stream_get_contents($fp);
$state = json_decode($jsonContent, true);

if (!$state) {
    flock($fp, LOCK_UN);
    fclose($fp);
    echo json_encode(['error' => 'Oyun verisi okunamadı.']);
    exit;
}

if ($state['status'] !== 'waiting') {
    flock($fp, LOCK_UN);
    fclose($fp);
    echo json_encode(['error' => 'Oyun zaten başlamış veya bitmiş.']);
    exit;
}

if ($state['player2'] !== null) {
    flock($fp, LOCK_UN);
    fclose($fp);
    echo json_encode(['error' => 'Oda dolu.']);
    exit;
}

// 2. oyuncuyu ekle ve oyunu başlat
$state['player2'] = [
    'id' => uniqid(),
    'name' => $playerName,
    'score' => 0,
    'finished' => false,
    'lost' => false,
    'time_taken' => 0
];
$state['status'] = 'playing';
$state['started_at'] = time();
$state['last_sync'] = time();

// Rewrite
ftruncate($fp, 0);
rewind($fp);
fwrite($fp, json_encode($state));
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);

echo json_encode([
    'success' => true, 
    'player_id' => $state['player2']['id'],
    'duration' => $state['duration'],
    'gameMode' => isset($state['gameMode']) ? $state['gameMode'] : 'cansiz',
    'livesLimit' => isset($state['livesLimit']) ? $state['livesLimit'] : 3,
    'passLimit' => isset($state['passLimit']) ? $state['passLimit'] : -1
]);
?>
