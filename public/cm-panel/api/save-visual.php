<?php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

// --- 1. KONFIGURACJA ---
$github_token = 'SEKRETNY_TOKEN_Z_GITHUBA'; // Zostaw to tak, jeśli używasz GitHub Actions
$github_user = 'sebastiancienkus';
$github_repo = 'cmedia';
$file_path = 'src/data/content.json'; 

// --- 2. AUTORYZACJA ---
if ($data['username'] !== 'seba' || $data['password'] !== '727911300') {
    echo json_encode(['success' => false, 'error' => 'Błąd logowania']); 
    exit;
}

// --- 3. POBIERANIE AKTUALNEGO SHA Z GITHUBA ---
// Potrzebujemy SHA, żeby GitHub przyjął naszą aktualizację
$url = "https://api.github.com/repos/$github_user/$github_repo/contents/$file_path";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "User-Agent: CMedia-CMS",
    "Authorization: Bearer $github_token"
]);
$response = json_decode(curl_exec($ch), true);

if (!isset($response['sha'])) {
    echo json_encode(['success' => false, 'error' => 'GitHub API Error: Nie znaleziono pliku lub zły token']);
    exit;
}

$sha = $response['sha'];
// Dekodujemy treść pliku z GitHuba, żeby mieć bazę do zmian
$content = json_decode(base64_decode($response['content']), true);

// --- 4. NAKŁADANIE ZMIAN WIZUALNYCH ---
foreach ($data['changes'] as $fullKey => $newValue) {
    if (strpos($fullKey, 'global.') === 0) {
        $key = str_replace('global.', '', $fullKey);
        $content['global'][$key] = $newValue;
    } else if (strpos($fullKey, 'index.') === 0) {
        $key = str_replace('index.', '', $fullKey);
        $content['pages']['index'][$key] = $newValue;
    } else {
        // Obsługa kluczy generowanych automatycznie
        $content['pages']['index'][$fullKey] = $newValue;
    }
}

$jsonOutput = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// --- 5. ⚡ KLUCZ: NATYCHMIASTOWY ZAPIS LOKALNY ---
// Używamy DOCUMENT_ROOT, żeby mieć 100% pewności, że plik trafi do głównego folderu
$local_path = $_SERVER['DOCUMENT_ROOT'] . '/content.json';
file_put_contents($local_path, $jsonOutput);

// --- 6. AKTUALIZACJA GITHUBA (W TLE) ---
$putData = json_encode([
    'message' => 'Visual CMS Update 🚀',
    'content' => base64_encode($jsonOutput),
    'sha' => $sha
]);

$ch2 = curl_init($url);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch2, CURLOPT_POSTFIELDS, $putData);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    "User-Agent: CMedia-CMS",
    "Authorization: Bearer $github_token",
    "Content-Type: application/json"
]);

$res = curl_exec($ch2);
$http_code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

// Zwracamy sukces, bo plik lokalny już został nadpisany!
if ($http_code == 200 || $http_code == 201) {
    echo json_encode(['success' => true, 'mode' => 'instant']);
} else {
    // Jeśli GitHub wywali błąd (np. konflikt SHA), i tak mówimy sukces, 
    // bo na Seohost już się zapisało i użytkownik widzi zmianę.
    echo json_encode(['success' => true, 'warning' => 'Zapisano lokalnie, GitHub API: ' . $http_code]);
}
?>
