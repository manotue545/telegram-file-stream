<?php
// proxy.php - Versão corrigida
error_reporting(E_ALL);
ini_set('display_errors', 0); // Desligar exibição de erros para não corromper o vídeo

$token = "8274275591:AAHDIz1Z7dpGQDesNQBES3Q7FoA_vxhG1eQ";
$file_id = $_GET['file_id'] ?? '';

if (empty($file_id)) {
    header('HTTP/1.0 400 Bad Request');
    die('File ID não fornecido');
}

// Headers CORS e cache
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Cache-Control: public, max-age=3600');

// Responder a requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Headers: Range');
    header('HTTP/1.1 200 OK');
    exit();
}

// Obter informações do arquivo
$getFile_url = "https://api.telegram.org/bot{$token}/getFile?file_id=" . urlencode($file_id);
$ch = curl_init($getFile_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'Telegram-Proxy/1.0'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    header('HTTP/1.0 502 Bad Gateway');
    die('Erro ao conectar com Telegram');
}

$data = json_decode($response, true);
if (!$data['ok']) {
    header('HTTP/1.0 404 Not Found');
    die('Arquivo não encontrado: ' . ($data['description'] ?? 'desconhecido'));
}

$file_path = $data['result']['file_path'];
$file_url = "https://api.telegram.org/file/bot{$token}/{$file_path}";

// Determinar o content-type baseado na extensão
$extensao = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
$content_types = [
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'ogg' => 'video/ogg',
    'mov' => 'video/quicktime',
    'avi' => 'video/x-msvideo',
    'mkv' => 'video/x-matroska'
];
$content_type = $content_types[$extensao] ?? 'video/mp4';

// IMPORTANTE: Headers específicos para vídeo
header("Content-Type: $content_type");
header('Accept-Ranges: bytes');

// Suporte a Range requests (para permitir arrastar o vídeo)
if (isset($_SERVER['HTTP_RANGE'])) {
    // Fazer requisição com range para o Telegram
    $ch = curl_init($file_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_HTTPHEADER => ['Range: ' . $_SERVER['HTTP_RANGE']]
    ]);
    
    $content = curl_exec($ch);
    $info = curl_getinfo($ch);
    $httpCode = $info['http_code'];
    curl_close($ch);
    
    if ($httpCode == 206) {
        header('HTTP/1.1 206 Partial Content');
        // Extrair Content-Range do response do Telegram
        // Nota: Isso é simplificado - em produção você precisaria capturar os headers
        header('Content-Range: bytes ' . 
               $_SERVER['HTTP_RANGE'] . '/' . 
               ($info['size_download'] + $info['header_size']));
    }
} else {
    // Download normal
    $ch = curl_init($file_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 0
    ]);
    $content = curl_exec($ch);
    curl_close($ch);
}

// Se for solicitado download, força o nome do arquivo
if (isset($_GET['download'])) {
    $filename = basename($file_path);
    header("Content-Disposition: attachment; filename=\"" . addslashes($filename) . "\"");
}

// Enviar o conteúdo
echo $content;
?>
