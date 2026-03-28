<?php
header('Content-Type: application/json');
$path = '../../../src/data/content.json';
if (file_exists($path)) {
    echo file_get_contents($path);
} else {
    echo json_encode(['error' => 'Plik nie istnieje']);
}
?>
