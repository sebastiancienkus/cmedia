<?php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

// --- KONFIGURACJA ---
$github_token = 'SEKRETNY_TOKEN_Z_GITHUBA'; 
$github_user = 'sebastiancienkus';
$github_repo = 'cmedia';
$file_path = 'src/data/content.json'; 

// Prosta autoryzacja
if ($data['username'] !== 'seba' || $data['password'] !== '727911300') {
    echo json_encode(['success' => false, 'error' => 'Błąd logowania']); exit;
}

// 1. Pobieramy plik z GitHuba
$url = "https://api.github.com/repos/$github_user/$github_repo/contents/$file_path";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: CMedia", "Authorization: Bearer $github_token"]);
$response = json_decode(curl_exec($ch), true);
$sha = $response['sha'];
$content = json_decode(base64_decode($response['content']), true);

// 2. Mapujemy zmiany do JSON-a
foreach ($data['changes'] as $fullKey => $newValue) {
    if (strpos($fullKey, 'global.') === 0) {
        // Obsługa GLOBALNYCH: global.phone -> $content['global']['phone']
        $key = str_replace('global.', '', $fullKey);
        $content['global'][$key] = $newValue;
    } else if (strpos($fullKey, 'index.') === 0) {
        // Obsługa STRONY GŁÓWNEJ: index.heroTitle -> $content['pages']['index']['heroTitle']
        $key = str_replace('index.', '', $fullKey);
        $content['pages']['index'][$key] = $newValue;
    }
}

// 3. Wysyłamy do GitHub
$jsonOutput = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
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

if ($http_code == 200 || $http_code == 201) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Błąd GitHub API: ' . $http_code]);
}
?>
