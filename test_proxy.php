<?php
// Ativar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuração
$token = "8274275591:AAHDIz1Z7dpGQDesNQBES3Q7FoA_vxhG1eQ";
$file_id = "BAACAgEAAxkBAAMGaZnkR4Dvk4TUAAEhRCLZT4Q0dH0xAALuBQACF83QRAR7In6WfoMOOgQ";

echo "<h2>Teste do Proxy Telegram</h2>";

// Passo 1: Testar conexão com a API
echo "<h3>Passo 1: Testando getFile</h3>";
$getFile_url = "https://api.telegram.org/bot{$token}/getFile?file_id={$file_id}";

$ch = curl_init($getFile_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "URL: " . htmlspecialchars($getFile_url) . "<br>";
echo "HTTP Code: " . $httpCode . "<br>";

if ($curlError) {
    echo "Erro cURL: " . $curlError . "<br>";
}

if ($httpCode !== 200) {
    echo "<span style='color:red'>❌ Falha na conexão com a API</span><br>";
    echo "Resposta: " . htmlspecialchars($response);
    exit;
}

$data = json_decode($response, true);
echo "Resposta da API: <pre>" . htmlspecialchars(print_r($data, true)) . "</pre>";

if (!$data['ok']) {
    echo "<span style='color:red'>❌ API retornou erro: " . $data['description'] . "</span><br>";
    exit;
}

echo "<span style='color:green'>✅ getFile funcionou!</span><br>";

// Passo 2: Obter file_path
$file_path = $data['result']['file_path'];
$file_url = "https://api.telegram.org/file/bot{$token}/{$file_path}";

echo "<h3>Passo 2: Informações do arquivo</h3>";
echo "File Path: " . htmlspecialchars($file_path) . "<br>";
echo "URL completa: " . htmlspecialchars($file_url) . "<br>";

// Passo 3: Testar se o arquivo existe
echo "<h3>Passo 3: Testando acesso ao arquivo</h3>";

$ch = curl_init($file_url);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_exec($ch);
$fileHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

echo "HTTP Code do arquivo: " . $fileHttpCode . "<br>";
echo "Content-Type: " . $contentType . "<br>";

if ($fileHttpCode === 200) {
    echo "<span style='color:green'>✅ Arquivo acessível!</span><br>";
    
    // Passo 4: Testar headers
    echo "<h3>Passo 4: Headers do arquivo</h3>";
    $headers = get_headers($file_url, 1);
    echo "<pre>" . htmlspecialchars(print_r($headers, true)) . "</pre>";
} else {
    echo "<span style='color:red'>❌ Arquivo não acessível (código $fileHttpCode)</span><br>";
}

// Passo 5: Instruções
echo "<h3>Passo 5: Como usar no HTML</h3>";
echo "Use este link no seu video tag:<br>";
echo "<strong>proxy.php?file_id=" . urlencode($file_id) . "</strong>";
?>
