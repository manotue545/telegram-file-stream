<?php
// diagnostic.php - Diagnóstico completo
header('Content-Type: text/plain; charset=utf-8');

echo "🔍 DIAGNÓSTICO DO PROXY\n";
echo "=======================\n\n";

// Informações do PHP
echo "PHP Version: " . phpversion() . "\n";
echo "Extensions: curl=" . (extension_loaded('curl') ? '✅' : '❌') . "\n";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? '✅' : '❌') . "\n\n";

// Testar conexão com Telegram
$token = "8274275591:AAHDIz1Z7dpGQDesNQBES3Q7FoA_vxhG1eQ";
$file_id = "BAACAgEAAxkBAAMGaZnkR4Dvk4TUAAEhRCLZT4Q0dH0xAALuBQACF83QRAR7In6WfoMOOgQ";

echo "Testando conexão com API do Telegram...\n";
$url = "https://api.telegram.org/bot{$token}/getMe";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($httpCode == 200) {
    echo "✅ Conexão com Telegram OK\n\n";
} else {
    echo "❌ Erro de conexão: $response\n\n";
    exit;
}

// Testar getFile
echo "Testando getFile...\n";
$url = "https://api.telegram.org/bot{$token}/getFile?file_id={$file_id}";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$data = json_decode($response, true);
if ($data['ok']) {
    echo "✅ File encontrado: " . $data['result']['file_path'] . "\n";
    echo "Tamanho: " . ($data['result']['file_size'] ?? 'desconhecido') . " bytes\n\n";
} else {
    echo "❌ Erro: " . ($data['description'] ?? 'desconhecido') . "\n\n";
}

// Testar download do arquivo
echo "Testando download do arquivo...\n";
$file_path = $data['result']['file_path'];
$file_url = "https://api.telegram.org/file/bot{$token}/{$file_path}";

$ch = curl_init($file_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Content-Type: $contentType\n";

if ($httpCode == 200) {
    echo "✅ Arquivo acessível!\n";
} else {
    echo "❌ Arquivo não acessível\n";
}

echo "\n📝 INSTRUÇÕES:\n";
echo "1. Copie todo este output\n";
echo "2. Cole aqui para análise\n";
?>
