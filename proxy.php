<?php
// Ativar exibição de erros (remover em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuração do token
$token = "8274275591:AAHDIz1Z7dpGQDesNQBES3Q7FoA_vxhG1eQ";

// Pega o file_id da requisição
$file_id = $_GET['file_id'] ?? '';

// Validação básica
if (empty($file_id)) {
    header('HTTP/1.0 400 Bad Request');
    die('File ID não fornecido');
}

// Cache de 1 hora para o arquivo
header('Cache-Control: public, max-age=3600');

// Função para fazer requisições cURL
function curlRequest($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Telegram-Proxy/1.0');
    
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'response' => $response,
        'info' => $info,
        'error' => $error
    ];
}

// Primeiro, obtém o file_path
$getFile_url = "https://api.telegram.org/bot{$token}/getFile?file_id=" . urlencode($file_id);
$result = curlRequest($getFile_url);

if ($result['error']) {
    header('HTTP/1.0 500 Internal Server Error');
    die('Erro de conexão: ' . $result['error']);
}

if ($result['info']['http_code'] !== 200) {
    header('HTTP/1.0 502 Bad Gateway');
    die('API do Telegram retornou código ' . $result['info']['http_code']);
}

$data = json_decode($result['response'], true);
if (!$data || !$data['ok']) {
    header('HTTP/1.0 404 Not Found');
    die('Arquivo não encontrado na API');
}

$file_path = $data['result']['file_path'];
$file_url = "https://api.telegram.org/file/bot{$token}/{$file_path}";

// Agora faz o proxy do arquivo
$result = curlRequest($file_url);

if ($result['error'] || $result['info']['http_code'] !== 200) {
    header('HTTP/1.0 502 Bad Gateway');
    die('Erro ao baixar arquivo do Telegram');
}

// Configura os headers apropriados
$contentType = $result['info']['content_type'];
$contentLength = $result['info']['size_download'];

header("Content-Type: $contentType");
header("Content-Length: $contentLength");

// Se for solicitado download, força o nome do arquivo
if (isset($_GET['download'])) {
    $filename = basename($file_path);
    header("Content-Disposition: attachment; filename=\"" . addslashes($filename) . "\"");
} else {
    // Para streaming de vídeo, permite range requests
    header('Accept-Ranges: bytes');
}

// Envia o arquivo
echo $result['response'];
?>
