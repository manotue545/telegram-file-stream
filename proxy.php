<?php
// proxy.php
$token = "8274275591:AAHDIz1Z7dpGQDesNQBES3Q7FoA_vxhG1eQ";
$file_id = $_GET['file_id'] ?? '';

if (empty($file_id)) {
    die('File ID não fornecido');
}

// Headers CORS para permitir acesso de qualquer origem
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Range');

// Headers para streaming de vídeo
header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=3600');

// Obter file_path
$getFile_url = "https://api.telegram.org/bot{$token}/getFile?file_id=" . urlencode($file_id);
$ch = curl_init($getFile_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if (!$data['ok']) {
    http_response_code(404);
    die('Arquivo não encontrado');
}

$file_path = $data['result']['file_path'];
$file_url = "https://api.telegram.org/file/bot{$token}/{$file_path}";

// Configurar headers baseado no tipo de arquivo
$extensao = pathinfo($file_path, PATHINFO_EXTENSION);
switch(strtolower($extensao)) {
    case 'mp4':
        header('Content-Type: video/mp4');
        break;
    case 'webm':
        header('Content-Type: video/webm');
        break;
    case 'ogg':
        header('Content-Type: video/ogg');
        break;
    default:
        header('Content-Type: video/mp4');
}

// Suporte a range requests (para poder arrastar o vídeo)
if (isset($_SERVER['HTTP_RANGE'])) {
    // Faz requisição com range para o Telegram
    $ch = curl_init($file_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Range: ' . $_SERVER['HTTP_RANGE']]);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $content = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($info['http_code'] == 206) {
        header('HTTP/1.1 206 Partial Content');
        header('Content-Range: ' . $info['content_type']); // Ajuste conforme necessário
    }
    
    echo $content;
} else {
    // Download normal
    $ch = curl_init($file_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $content = curl_exec($ch);
    curl_close($ch);
    
    echo $content;
}
?>
