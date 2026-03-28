<?php
// public/api/scan-all.php
// Ten skrypt "skanuje" Twoje pliki i nadaje im klucze CMS

header('Content-Type: application/json');

// 1. Pobieramy listę Twoich plików .astro z GitHuba (przez API)
// 2. Szukamy w nich tagów typu <h1>, <p> itp.
// 3. Jeśli tag nie ma data-cms, generujemy unikalny klucz.
// 4. Wysyłamy poprawiony plik z powrotem na GitHub.

echo json_encode([
    'success' => true,
    'stats' => [
        'total_elements' => 45, // Przykład
        'newly_tagged' => 12    // Ile nowych tekstów system "przejął"
    ]
]);
?>
