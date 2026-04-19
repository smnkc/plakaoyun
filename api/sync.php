<?php
header('Content-Type: application/json');
// Do not auto-cleanup on every sync to reduce disk IO, we'll do it rarely
// require_once 'cleanup.php'; 

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['code']) || empty($input['player_id'])) {
    echo json_encode(['error' => 'Geçersiz istek.']);
    exit;
}

$code = $input['code'];
$playerId = $input['player_id'];
$file = __DIR__ . '/data/' . $code . '.json';

if (!file_exists($file)) {
    echo json_encode(['success' => false, 'error' => 'Oda bulunamadı veya silinmiş.']);
    exit;
}

$fp = fopen($file, 'c+');
if (!$fp) {
    echo json_encode(['success' => false, 'error' => 'Dosya erişim hatası.']);
    exit;
}

// Get exclusive lock
flock($fp, LOCK_EX);

$jsonContent = stream_get_contents($fp);
$state = json_decode($jsonContent, true);

if (!$state) {
    flock($fp, LOCK_UN);
    fclose($fp);
    echo json_encode(['success' => false, 'error' => 'Oyun verisi okunamadı.']);
    exit;
}

$state['last_sync'] = time();
$isP1 = ($state['player1'] && $state['player1']['id'] === $playerId);
$isP2 = ($state['player2'] && $state['player2']['id'] === $playerId);

if (!$isP1 && !$isP2) {
    flock($fp, LOCK_UN);
    fclose($fp);
    echo json_encode(['success' => false, 'error' => 'Bu odada oyuncu değilsiniz.']);
    exit;
}

if (isset($input['score'])) {
    if ($isP1) {
        $state['player1']['score'] = $input['score'];
        if (isset($input['finished'])) $state['player1']['finished'] = $input['finished'];
        if (isset($input['lost'])) $state['player1']['lost'] = $input['lost'];
        if (isset($input['time_taken'])) $state['player1']['time_taken'] = $input['time_taken'];
    } else {
        $state['player2']['score'] = $input['score'];
        if (isset($input['finished'])) $state['player2']['finished'] = $input['finished'];
        if (isset($input['lost'])) $state['player2']['lost'] = $input['lost'];
        if (isset($input['time_taken'])) $state['player2']['time_taken'] = $input['time_taken'];
    }
}

if ($state['status'] === 'playing') {
    $p1Done = (isset($state['player1']['finished']) && $state['player1']['finished']) || (isset($state['player1']['lost']) && $state['player1']['lost']);
    $p2Done = (isset($state['player2']['finished']) && $state['player2']['finished']) || (isset($state['player2']['lost']) && $state['player2']['lost']);
    
    if ($p1Done && $p2Done) {
        $state['status'] = 'finished';
        $state['ended_at'] = time();
    }
}

// Rewrite file
ftruncate($fp, 0);
rewind($fp);
fwrite($fp, json_encode($state));
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);

echo json_encode(['success' => true, 'state' => $state]);
?>
