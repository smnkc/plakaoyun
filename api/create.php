<?php
header('Content-Type: application/json');
require_once 'cleanup.php';

$dir = __DIR__ . '/data/';
if (!is_dir($dir)) mkdir($dir, 0777, true);

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$duration = isset($input['duration']) ? $input['duration'] : 'unlimited';
$hostName = isset($input['playerName']) ? $input['playerName'] : 'Oyuncu 1';
$gameMode = isset($input['gameMode']) ? $input['gameMode'] : 'cansiz';
$livesLimit = isset($input['livesLimit']) ? $input['livesLimit'] : 3;
$passLimit = isset($input['passLimit']) ? $input['passLimit'] : -1;

$code = "";
do {
    $code = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    $file = $dir . $code . '.json';
} while (file_exists($file));

$state = [
    'code' => $code,
    'status' => 'waiting', // waiting, playing, finished
    'last_sync' => time(),
    'duration' => $duration,
    'gameMode' => $gameMode,
    'livesLimit' => $livesLimit,
    'passLimit' => $passLimit,
    'started_at' => 0,
    'ended_at' => 0,
    'player1' => [
        'id' => uniqid(),
        'name' => $hostName,
        'score' => 0,
        'finished' => false,
        'lost' => false,
        'time_taken' => 0
    ],
    'player2' => null
];

file_put_contents($file, json_encode($state));

echo json_encode(['success' => true, 'code' => $code, 'player_id' => $state['player1']['id']]);
?>
