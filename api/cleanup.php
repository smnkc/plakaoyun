<?php
function cleanupOldRooms() {
    $dir = __DIR__ . '/data/';
    if (!is_dir($dir)) return;

    $files = glob($dir . '*.json');
    $now = time();

    foreach ($files as $file) {
        $data = json_decode(file_get_contents($file), true);
        if (!$data) {
            unlink($file);
            continue;
        }

        $lastSync = isset($data['last_sync']) ? $data['last_sync'] : 0;
        $status = isset($data['status']) ? $data['status'] : 'waiting';
        $endedAt = isset($data['ended_at']) ? $data['ended_at'] : 0;

        // If no one joined and it's been more than 60 seconds since last sync
        if ($status === 'waiting' && ($now - $lastSync > 60)) {
            unlink($file);
        }
        // If game ended, delete after 60 seconds
        else if ($status === 'finished' && $endedAt > 0 && ($now - $endedAt > 60)) {
            unlink($file);
        }
        // General fallback: if no activity for 3 minutes, kill it
        else if ($now - $lastSync > 180) {
            unlink($file);
        }
    }
}
// Automatically run cleanup on API calls
cleanupOldRooms();
?>
