<?php
header('Content-Type: application/json');

// Używamy __DIR__, aby PHP sam wiedział, gdzie dokładnie leży ten plik
$path = __DIR__ . '/../../../content.json';

if (file_exists($path)) {
    $content = file_get_contents($path);
    // Sprawdzamy, czy plik nie jest pusty
    if ($content) {
        echo $content;
    } else {
        echo json_encode(['heroTitle' => 'Plik jest pusty', 'heroSubtitle' => 'Wpisz dane ręcznie']);
    }
} else {
    // Jeśli pliku nie ma, wysyłamy błąd, który panel zrozumie
    http_response_code(404);
    echo json_encode(['error' => 'Nie znaleziono pliku content.json pod adresem: ' . $path]);
}
?>
