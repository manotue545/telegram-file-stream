<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Player de Vídeo</title>
    <style>
        .video-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #1a1a1a;
            border-radius: 10px;
        }
        
        video {
            width: 100%;
            background: #000;
            border-radius: 5px;
        }
        
        .error-message {
            color: #ff6b6b;
            padding: 10px;
            background: #2a2a2a;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .debug-info {
            margin-top: 20px;
            padding: 10px;
            background: #2a2a2a;
            color: #00ff00;
            font-family: monospace;
            font-size: 12px;
            border-radius: 5px;
            max-height: 200px;
            overflow: auto;
        }
    </style>
</head>
<body>
    <div class="video-container">
        <h2 style="color: white;">Player de Vídeo</h2>
        
        <video id="meuVideo" controls preload="auto" crossorigin="anonymous">
            <source src="proxy.php?file_id=BAACAgEAAxkBAAMGaZnkR4Dvk4TUAAEhRCLZT4Q0dH0xAALuBQACF83QRAR7In6WfoMOOgQ" type="video/mp4">
            Seu navegador não suporta vídeo HTML5.
        </video>
        
        <div class="error-message" id="erroVideo" style="display: none;"></div>
        
        <div style="margin-top: 20px;">
            <button onclick="testarVideo()">Testar Vídeo</button>
            <button onclick="recarregarVideo()">Recarregar Vídeo</button>
            <a href="proxy.php?file_id=BAACAgEAAxkBAAMGaZnkR4Dvk4TUAAEhRCLZT4Q0dH0xAALuBQACF83QRAR7In6WfoMOOgQ&download=1" 
               style="color: white; background: #4CAF50; padding: 5px 10px; text-decoration: none; border-radius: 3px;">
                Baixar Vídeo
            </a>
        </div>
        
        <div class="debug-info" id="debugInfo">
            Aguardando informações...
        </div>
    </div>
    
    <script>
        const video = document.getElementById('meuVideo');
        const erroVideo = document.getElementById('erroVideo');
        const debugInfo = document.getElementById('debugInfo');
        
        function logDebug(mensagem) {
            debugInfo.innerHTML += mensagem + '<br>';
        }
        
        // Testar se o proxy está acessível
        async function testarProxy() {
            try {
                logDebug('🔍 Testando proxy...');
                const response = await fetch('proxy.php?file_id=BAACAgEAAxkBAAMGaZnkR4Dvk4TUAAEhRCLZT4Q0dH0xAALuBQACF83QRAR7In6WfoMOOgQ', {
                    method: 'HEAD'
                });
                
                logDebug(`📊 Status do proxy: ${response.status} ${response.statusText}`);
                logDebug(`📦 Headers: ${JSON.stringify([...response.headers])}`);
                
                if (!response.ok) {
                    throw new Error(`Proxy retornou status ${response.status}`);
                }
                
                return true;
            } catch (error) {
                logDebug(`❌ Erro no proxy: ${error.message}`);
                return false;
            }
        }
        
        // Testar carregamento do vídeo
        function testarVideo() {
            logDebug('🎬 Testando carregamento do vídeo...');
            
            // Verificar se o navegador suporta o formato
            const canPlay = video.canPlayType('video/mp4');
            logDebug(`🔧 Suporte a MP4: ${canPlay || 'não suportado'}`);
            
            // Tentar carregar o vídeo
            video.load();
        }
        
        // Recarregar vídeo
        function recarregarVideo() {
            logDebug('🔄 Recarregando vídeo...');
            video.load();
        }
        
        // Eventos do vídeo
        video.addEventListener('loadstart', () => {
            logDebug('📥 Iniciando carregamento...');
        });
        
        video.addEventListener('loadedmetadata', () => {
            logDebug(`✅ Metadados carregados: ${video.videoWidth}x${video.videoHeight}`);
            logDebug(`⏱️ Duração: ${video.duration} segundos`);
        });
        
        video.addEventListener('loadeddata', () => {
            logDebug('✅ Dados do vídeo carregados');
        });
        
        video.addEventListener('canplay', () => {
            logDebug('▶️ Pronto para reproduzir');
        });
        
        video.addEventListener('waiting', () => {
            logDebug('⏳ Bufferizando...');
        });
        
        video.addEventListener('error', (e) => {
            const error = video.error;
            let mensagem = '❌ Erro no vídeo: ';
            
            switch(error.code) {
                case MediaError.MEDIA_ERR_ABORTED:
                    mensagem += 'Carregamento abortado';
                    break;
                case MediaError.MEDIA_ERR_NETWORK:
                    mensagem += 'Erro de rede';
                    break;
                case MediaError.MEDIA_ERR_DECODE:
                    mensagem += 'Erro de decodificação';
                    break;
                case MediaError.MEDIA_ERR_SRC_NOT_SUPPORTED:
                    mensagem += 'Formato não suportado';
                    break;
                default:
                    mensagem += 'Erro desconhecido';
            }
            
            logDebug(mensagem);
            erroVideo.style.display = 'block';
            erroVideo.textContent = mensagem;
        });
        
        // Iniciar testes
        testarProxy().then(sucesso => {
            if (sucesso) {
                testarVideo();
            }
        });
    </script>
</body>
</html>
