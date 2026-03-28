<?php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

// --- KONFIGURACJA (identyczna jak wcześniej) ---
$github_token = 'SEKRETNY_TOKEN_Z_GITHUBA'; 
$github_user = 'sebastiancienkus';
$github_repo = 'cmedia';
$file_path = 'src/data/content.json'; 

if ($data['username'] !== 'seba' || $data['password'] !== 'TWOJE_HASLO') {
    echo json_encode(['success' => false, 'error' => 'Błąd autoryzacji']); exit;
}

// 1. Pobieramy aktualny plik z GitHuba
$url = "https://api.github.com/repos/$github_user/$github_repo/contents/$file_path";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: CMedia", "Authorization: Bearer $github_token"]);
$response = json_decode(curl_exec($ch), true);
$sha = $response['sha'];
$currentContent = json_decode(base64_decode($response['content']), true);

// 2. Aplikujemy zmiany wizualne (mapujemy klucze typu "index.heroTitle")
foreach ($data['changes'] as $key => $value) {
    if (strpos($key, 'index.') === 0) {
        $realKey = str_replace('index.', '', $key);
        $currentContent['pages']['index'][$realKey] = $value;
    } elseif ($key === 'email' || $key === 'phone') {
        $currentContent['global'][$key] = $value;
    }
}

// 3. Wysyłamy zaktualizowany plik z powrotem
$jsonContent = json_encode($currentContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$putData = json_encode([
    'message' => 'Visual CMS Update',
    'content' => base64_encode($jsonContent),
    'sha' => $sha
]);

$ch2 = curl_init($url);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch2, CURLOPT_POSTFIELDS, $putData);
curl_setopt($ch2, CURLOPT_HTTPHEADER, ["User-Agent: CMedia", "Authorization: Bearer $github_token", "Content-Type: application/json"]);

$res = curl_exec($ch2);
$code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

if ($code == 200 || $code == 201) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'GitHub API Error: ' . $code]);
}
?>
