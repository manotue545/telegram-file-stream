<?php
// proxy.php - Versão FINAL e CORRIGIDA
ob_clean(); // Limpa qualquer saída anterior
ob_start();

$token = "8274275591:AAHDIz1Z7dpGQDesNQBES3Q7FoA_vxhG1eQ";
$file_id = $_GET['file_id'] ?? '';

if (empty($file_id)) {
    header('HTTP/1.0 400 Bad Request');
    die('File ID não fornecido');
}

// Função para log (opcional - descomente para debug)
// file_put_contents('proxy_log.txt', date('Y-m-d H:i:s') . " - File ID: $file_id\n", FILE_APPEND);

// Headers ESSENCIAIS para vídeo
header('Content-Type: video/mp4');
header('Accept-Ranges: bytes');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600');

// Obter informações do arquivo
$getFile_url = "https://api.telegram.org/bot{$token}/getFile?file_id=" . urlencode($file_id);
$ch = curl_init($getFile_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 10
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    header('HTTP/1.0 502 Bad Gateway');
    die('Erro na API do Telegram');
}

$data = json_decode($response, true);
if (!$data['ok']) {
    header('HTTP/1.0 404 Not Found');
    die('Arquivo não encontrado');
}

$file_path = $data['result']['file_path'];
$file_url = "https://api.telegram.org/file/bot{$token}/{$file_path}";

// FAZER O PROXY DE FORMA SIMPLES E DIRETA
$ch = curl_init($file_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_HEADERFUNCTION => function($curl, $header_line) {
        // Repassar headers importantes do Telegram
        if (strpos($header_line, 'Content-Length:') !== false) {
            header($header_line);
        }
        if (strpos($header_line, 'Content-Type:') !== false) {
            // Já setamos nosso próprio Content-Type
        }
        return strlen($header_line);
    }
]);

$content = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

if ($info['http_code'] !== 200) {
    header('HTTP/1.0 502 Bad Gateway');
    die('Erro ao baixar do Telegram');
}

// Se for download, força o nome
if (isset($_GET['download'])) {
    $filename = basename($file_path);
    header("Content-Disposition: attachment; filename=\"$filename\"");
}

// Enviar o conteúdo
echo $content;
ob_end_flush();
?>
