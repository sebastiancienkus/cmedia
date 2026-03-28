<?php
header('Content-Type: application/json');

// Poprawiona ścieżka: wychodzimy z api/ oraz z cm-panel/ i jesteśmy w public_html
$path = __DIR__ . '/../../content.json';

if (file_exists($path)) {
    $content = file_get_contents($path);
    if ($content) {
        echo $content;
    } else {
        echo json_encode(['heroTitle' => 'Błąd', 'heroSubtitle' => 'Plik jest pusty']);
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Nie znaleziono pliku pod adresem: ' . $path]);
}
?>
