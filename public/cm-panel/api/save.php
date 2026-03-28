<?php
header('Content-Type: application/json');

// Odbieramy dane z formularza
$data = json_decode(file_get_contents('php://input'), true);

// ==========================================
// 🔐 TWOJA KONFIGURACJA (UZUPEŁNIJ TO!)
// ==========================================
$moj_login = 'seba';               // Wymyśl login do panelu (np. admin)
$moje_haslo = '727911300';             // Wymyśl hasło do panelu

$github_token = 'SEKRETNY_TOKEN_Z_GITHUBA' // Zostaw tak jak jest
$github_user = 'sebastiancienkus';                // TWOJ_LOGIN_GITHUB Np. sebastiancienkus
$github_repo = 'cmedia';       // NAZWA_TWOJEGO_REPOZYTORIUM', Nazwa projektu na GitHubie
$file_path = 'src/data/content.json';              // Ścieżka do pliku (tego nie zmieniaj)
// ==========================================

// 1. Sprawdzamy, czy login i hasło z panelu się zgadzają
if ($data['username'] !== $moj_login || $data['password'] !== $moje_haslo) {
    echo json_encode(['success' => false, 'error' => 'Błędny login lub hasło! 🛑']);
    exit;
}

// 2. Pakujemy nowe teksty do formatu JSON
$newContent = [
    'heroTitle' => $data['heroTitle'],
    'heroSubtitle' => $data['heroSubtitle'],
    'contactEmail' => 'biuro@sebastiancienkus.com'
];
$jsonContent = json_encode($newContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$base64Content = base64_encode($jsonContent);

// 3. Pobieramy obecny plik z GitHuba (żeby zdobyć jego unikalny kod "SHA")
$url = "https://api.github.com/repos/$github_user/$github_repo/contents/$file_path";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "User-Agent: CMedia-CMS",
    "Authorization: Bearer $github_token",
    "X-GitHub-Api-Version: 2022-11-28"
]);
$fileData = json_decode(curl_exec($ch), true);
$sha = $fileData['sha'] ?? null;

// 4. Wysyłamy nową wersję z powrotem na GitHuba (Commit)
$putData = json_encode([
    'message' => '📝 Zmiana tekstu z panelu cm-panel',
    'content' => $base64Content,
    'sha' => $sha
]);

$ch2 = curl_init($url);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch2, CURLOPT_POSTFIELDS, $putData);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    "User-Agent: CMedia-CMS",
    "Authorization: Bearer $github_token",
    "Content-Type: application/json",
    "X-GitHub-Api-Version: 2022-11-28"
]);

$response2 = curl_exec($ch2);
$httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

if ($httpCode == 200 || $httpCode == 201) {
    echo json_encode(['success' => true]);
} else {
    // W razie błędu wypisujemy co dokładnie zwrócił GitHub
    $errorMsg = json_decode($response2, true)['message'] ?? 'Nieznany błąd GitHuba';
    echo json_encode(['success' => false, 'error' => "Błąd GitHuba: " . $errorMsg]);
}
?>
