<?php
// Configuração do token (APENAS NO SERVIDOR, NUNCA NO CLIENTE)
$token = "8274275591:AAHDIz1Z7dpGQDesNQBES3Q7FoA_vxhG1eQ";

// Pega o file_id da requisição
$file_id = $_GET['file_id'] ?? '';

if (empty($file_id)) {
    http_response_code(400);
    die('File ID não fornecido');
}

// Primeiro, obtém o file_path da API do Telegram
$getFile_url = "https://api.telegram.org/bot{$token}/getFile?file_id={$file_id}";
$ch = curl_init($getFile_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code(500);
    die('Erro ao obter informações do arquivo');
}

$data = json_decode($response, true);
if (!$data['ok']) {
    http_response_code(500);
    die('Arquivo não encontrado');
}

$file_path = $data['result']['file_path'];
$file_url = "https://api.telegram.org/file/bot{$token}/{$file_path}";

// Faz o proxy do arquivo para o cliente
$ch = curl_init($file_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, false);

// Pega os headers do arquivo original
$headers = get_headers($file_url, 1);
if (isset($headers['Content-Type'])) {
    header("Content-Type: " . $headers['Content-Type']);
}
if (isset($headers['Content-Length'])) {
    header("Content-Length: " . $headers['Content-Length']);
}

// Força download ou visualização baseado no parâmetro 'download'
if (isset($_GET['download'])) {
    $filename = basename($file_path);
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
}

// Envia o arquivo
$file_content = curl_exec($ch);
curl_close($ch);

echo $file_content;
?>
