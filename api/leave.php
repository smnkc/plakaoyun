<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['code'])) {
    echo json_encode(['error' => 'Geçersiz istek.']);
    exit;
}

$code = $input['code'];
$file = __DIR__ . '/data/' . $code . '.json';

if (file_exists($file)) {
    unlink($file);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Dosya zaten yok.']);
}
?>
