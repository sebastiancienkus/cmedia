<?php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

// --- KONFIGURACJA ---
$moj_login = 'seba'; 
$moje_haslo = '727911300'; 
$github_token = 'SEKRETNY_TOKEN_Z_GITHUBA'; 
$github_user = 'sebastiancienkus';
$github_repo = 'cmedia';
$file_path = 'src/data/content.json';
// --------------------

// 1. Uniwersalne sprawdzenie hasła
if ($data['username'] !== $moj_login || $data['password'] !== $moje_haslo) {
    echo json_encode(['success' => false, 'error' => 'Błędny login lub hasło!']);
    exit;
}

// 2. Jeśli to tylko logowanie - kończymy sukcesem tutaj
if ($data['action'] === 'login') {
    echo json_encode(['success' => true]);
    exit;
}

// 3. Jeśli to zapis - jedziemy z procedurą GitHub
if ($data['action'] === 'save') {
    $newContent = [
        'heroTitle' => $data['heroTitle'],
        'heroSubtitle' => $data['heroSubtitle'],
        'contactEmail' => 'biuro@sebastiancienkus.com'
    ];
    $jsonContent = json_encode($newContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $base64Content = base64_encode($jsonContent);

    // Pobieramy SHA
    $url = "https://api.github.com/repos/$github_user/$github_repo/contents/$file_path";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: CMedia", "Authorization: Bearer $github_token"]);
    $fileData = json_decode(curl_exec($ch), true);
    $sha = $fileData['sha'] ?? null;

    // Wysyłamy Commit
    $putData = json_encode(['message' => 'CMS Update', 'content' => $base64Content, 'sha' => $sha]);
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
        echo json_encode(['success' => false, 'error' => 'GitHub API Error']);
    }
}
?>
