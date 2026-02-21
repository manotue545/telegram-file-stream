// api/video.js
export default async function handler(req, res) {
    // Configurar CORS
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Range');
    
    // Responder a requisições OPTIONS
    if (req.method === 'OPTIONS') {
        return res.status(200).end();
    }
    
    // Pegar file_id da URL
    const { file_id } = req.query;
    const token = "8274275591:AAHDIz1Z7dpGQDesNQBES3Q7FoA_vxhG1eQ";
    
    if (!file_id) {
        return res.status(400).json({ error: 'File ID não fornecido' });
    }
    
    try {
        // Buscar informações do arquivo no Telegram
        const getFileUrl = `https://api.telegram.org/bot${token}/getFile?file_id=${encodeURIComponent(file_id)}`;
        const getFileRes = await fetch(getFileUrl);
        const getFileData = await getFileRes.json();
        
        if (!getFileData.ok) {
            return res.status(404).json({ error: 'Arquivo não encontrado' });
        }
        
        const file_path = getFileData.result.file_path;
        const file_url = `https://api.telegram.org/file/bot${token}/${file_path}`;
        
        // Determinar o tipo do arquivo
        const ext = file_path.split('.').pop().toLowerCase();
        const contentType = ext === 'mp4' ? 'video/mp4' : 
                           ext === 'jpg' || ext === 'jpeg' ? 'image/jpeg' :
                           ext === 'png' ? 'image/png' :
                           ext === 'gif' ? 'image/gif' : 'video/mp4';
        
        // Headers para vídeo
        res.setHeader('Content-Type', contentType);
        res.setHeader('Accept-Ranges', 'bytes');
        res.setHeader('Cache-Control', 'public, max-age=3600');
        
        // Suporte a Range Requests (para poder arrastar o vídeo)
        const range = req.headers.range;
        
        if (range) {
            // Buscar apenas o trecho solicitado
            const videoRes = await fetch(file_url, {
                headers: { 'Range': range }
            });
            
            const videoBuffer = await videoRes.arrayBuffer();
            const contentRange = videoRes.headers.get('content-range');
            
            if (contentRange) {
                res.setHeader('Content-Range', contentRange);
            }
            res.setHeader('Content-Length', videoBuffer.byteLength);
            
            return res.status(206).send(Buffer.from(videoBuffer));
        } else {
            // Download completo
            const videoRes = await fetch(file_url);
            const videoBuffer = await videoRes.arrayBuffer();
            
            // Se for download, forçar nome do arquivo
            if (req.query.download === '1') {
                const filename = file_path.split('/').pop() || 'video.mp4';
                res.setHeader('Content-Disposition', `attachment; filename="${filename}"`);
            }
            
            res.setHeader('Content-Length', videoBuffer.byteLength);
            return res.send(Buffer.from(videoBuffer));
        }
        
    } catch (error) {
        console.error('Erro:', error);
        res.status(500).json({ error: 'Erro interno no servidor' });
    }
}
