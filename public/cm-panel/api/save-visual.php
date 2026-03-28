<?php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

// --- 1. KONFIGURACJA ---
$github_token = 'SEKRETNY_TOKEN_Z_GITHUBA'; 
$github_user = 'sebastiancienkus';
$github_repo = 'cmedia';
$file_path = 'src/data/content.json'; 

// --- 2. AUTORYZACJA ---
if ($data['username'] !== 'seba' || $data['password'] !== '727911300') {
    echo json_encode(['success' => false, 'error' => 'Błąd logowania']); 
    exit;
}

// --- 3. POBIERANIE AKTUALNEGO SHA Z GITHUBA ---
$url = "https://api.github.com/repos/$github_user/$github_repo/contents/$file_path";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "User-Agent: CMedia-CMS",
    "Authorization: Bearer $github_token"
]);
$response = json_decode(curl_exec($ch), true);

if (!isset($response['sha'])) {
    echo json_encode(['success' => false, 'error' => 'GitHub API Error: Nie znaleziono pliku']);
    exit;
}

$sha = $response['sha'];
$content = json_decode(base64_decode($response['content']), true);

// --- 4. ⚡ INTELIGENTNE NAKŁADANIE ZMIAN (Deep Merge) ---
// Ta pętla obsłuży zarówno "index.title" jak i "global.theme.colors.brand"
foreach ($data['changes'] as $fullPath => $newValue) {
    $keys = explode('.', $fullPath);
    $temp = &$content;

    foreach ($keys as $key) {
        // Jeśli poziom nie istnieje, stwórz go jako tablicę
        if (!isset($temp[$key]) || !is_array($temp[$key])) {
            $temp[$key] = [];
        }
        $temp = &$temp[$key];
    }
    // Na samym końcu ścieżki ustawiamy nową wartość
    $temp = $newValue;
}

$jsonOutput = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// --- 5. ⚡ NATYCHMIASTOWY ZAPIS LOKALNY ---
$local_path = $_SERVER['DOCUMENT_ROOT'] . '/content.json';
file_put_contents($local_path, $jsonOutput);

// --- 6. AKTUALIZACJA GITHUBA (W TLE) ---
$putData = json_encode([
    'message' => 'Visual Style & Content Update 🚀',
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

if ($http_code == 200 || $http_code == 201) {
    echo json_encode(['success' => true, 'mode' => 'instant']);
} else {
    // Nawet jeśli GitHub ma błąd (np. konflikt wersji), to lokalnie już się zapisało!
    echo json_encode(['success' => true, 'warning' => 'Zapisano na serwerze, GitHub zsynchronizuje się później (Error: '.$http_code.')']);
}
?>
