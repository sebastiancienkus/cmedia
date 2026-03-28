<?php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

// --- KONFIGURACJA ---
$github_token = 'SEKRETNY_TOKEN_Z_GITHUBA'; 
$github_user = 'sebastiancienkus';
$github_repo = 'cmedia';
$file_path = 'src/data/content.json'; 

// 1. Prosta autoryzacja
if ($data['username'] !== 'seba' || $data['password'] !== '727911300') {
    echo json_encode(['success' => false, 'error' => 'Błąd logowania']); 
    exit;
}

// 2. Pobieramy aktualny stan z GitHuba (potrzebujemy SHA do zapisu)
$url = "https://api.github.com/repos/$github_user/$github_repo/contents/$file_path";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: CMedia", "Authorization: Bearer $github_token"]);
$response = json_decode(curl_exec($ch), true);

if (!isset($response['sha'])) {
    echo json_encode(['success' => false, 'error' => 'Nie udało się połączyć z GitHubem']);
    exit;
}

$sha = $response['sha'];
$content = json_decode(base64_decode($response['content']), true);

// 3. Mapujemy zmiany wizualne do struktury JSON
foreach ($data['changes'] as $fullKey => $newValue) {
    if (strpos($fullKey, 'global.') === 0) {
        $key = str_replace('global.', '', $fullKey);
        $content['global'][$key] = $newValue;
    } else if (strpos($fullKey, 'index.') === 0) {
        $key = str_replace('index.', '', $fullKey);
        $content['pages']['index'][$key] = $newValue;
    } else {
        // Jeśli klucz nie pasuje do wzorca, wrzucamy go do sekcji auto
        $content['pages']['index'][$fullKey] = $newValue;
    }
}

// 4. Przygotowujemy finalny JSON
$jsonOutput = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// --- ⚡ NOWOŚĆ: NATYCHMIASTOWY ZAPIS LOKALNY (SEOHOST) ---
// Ścieżka do pliku content.json w Twoim public_html
$local_path = __DIR__ . '/../../content.json';
file_put_contents($local_path, $jsonOutput);
// ------------------------------------------------------

// 5. Wysyłamy zmiany do GitHub (Aktualizacja repozytorium w tle)
$putData = json_encode([
    'message' => 'Visual CMS Update 🚀',
    'content' => base64_encode($jsonOutput),
    'sha' => $sha
]);

$ch2 = curl_init($url);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch2, CURLOPT_POSTFIELDS, $putData);
curl_setopt($ch2, CURLOPT_HTTPHEADER, ["User-Agent: CMedia", "Authorization: Bearer $github_token", "Content-Type: application/json"]);

$res = curl_exec($ch2);
$http_code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

// Zwracamy sukces, jeśli zapis lokalny się udał (nawet jeśli GitHub jeszcze mieli)
if ($http_code == 200 || $http_code == 201) {
    echo json_encode(['success' => true, 'local' => 'updated']);
} else {
    // Jeśli GitHub wyrzuci błąd, nadal informujemy o tym, ale plik lokalny już działa
    echo json_encode(['success' => true, 'warning' => 'Zapisano lokalnie, ale błąd GitHub: ' . $http_code]);
}
?>
