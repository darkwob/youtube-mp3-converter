<?php
require_once 'vendor/autoload.php';

use YoutubeDl\Options;
use YoutubeDl\YoutubeDl;

class YouTubeDownloader {
    private $outputDir;
    private $ytdlPath;
    private $ffmpegPath;
    
    public function __construct() {
        // Tam yolları ayarla
        $this->outputDir = __DIR__ . DIRECTORY_SEPARATOR . 'downloads';
        $this->ytdlPath = __DIR__ . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'yt-dlp.exe';
        $this->ffmpegPath = __DIR__ . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'ffmpeg.exe';
        
        // Klasör oluştur
        if (!file_exists($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }
        
        // Dosyaları kontrol et
        if (!file_exists($this->ytdlPath)) {
            throw new Exception('yt-dlp.exe bulunamadı! Dosya yolu: ' . $this->ytdlPath);
        }
        
        if (!file_exists($this->ffmpegPath)) {
            throw new Exception('ffmpeg.exe bulunamadı! Dosya yolu: ' . $this->ffmpegPath);
        }
        
        error_log('Çıktı klasörü: ' . $this->outputDir);
        error_log('yt-dlp yolu: ' . $this->ytdlPath);
        error_log('ffmpeg yolu: ' . $this->ffmpegPath);
    }
    
    private function convertMusicUrl($url) {
        if (strpos($url, 'music.youtube.com') !== false) {
            $url = str_replace('music.youtube.com', 'www.youtube.com', $url);
            error_log('URL dönüştürüldü: ' . $url);
        }
        return $url;
    }
    
    public function download($url, $isPlaylist = false) {
        try {
            // URL'yi dönüştür
            $url = $this->convertMusicUrl($url);
            
            // Çalışma dizinini değiştir
            chdir(dirname($this->ytdlPath));
            
            // Temel komut parametreleri
            $baseCmd = 'yt-dlp.exe --ignore-errors --extract-audio --audio-format mp3 --audio-quality 0';
            
            // Playlist için ek parametreler
            if ($isPlaylist) {
                $baseCmd .= ' --yes-playlist --playlist-random';
                // Playlist için özel çıktı formatı
                $outputTemplate = ' -o "' . $this->outputDir . DIRECTORY_SEPARATOR . '%(playlist_index)s-%(title)s.%(ext)s"';
            } else {
                $baseCmd .= ' --no-playlist';
                // Tekli video için çıktı formatı
                $outputTemplate = ' -o "' . $this->outputDir . DIRECTORY_SEPARATOR . '%(title)s.%(ext)s"';
            }
            
            // Tam komutu oluştur
            $cmd = $baseCmd . $outputTemplate . ' "' . $url . '"';
            
            error_log('Çalıştırılacak komut: ' . $cmd);
            
            // Komutu çalıştır
            $output = [];
            $returnVar = 0;
            
            exec($cmd . ' 2>&1', $output, $returnVar);
            
            error_log('Komut çıktısı: ' . implode("\n", $output));
            error_log('Dönüş kodu: ' . $returnVar);
            
            // Çıktıyı kontrol et
            $outputStr = implode("\n", $output);
            if (strpos($outputStr, 'ERROR') !== false && strpos($outputStr, 'WARNING') === false) {
                throw new Exception('İndirme başarısız! Çıktı: ' . $outputStr);
            }
            
            if ($isPlaylist) {
                return "Playlist indirme işlemi tamamlandı! Dosyalar 'downloads' klasöründe.";
            } else {
                return "Video başarıyla MP3'e dönüştürüldü! Dosya 'downloads' klasöründe.";
            }
            
        } catch (Exception $e) {
            error_log('Hata: ' . $e->getMessage());
            error_log('Hata detayı: ' . $e->getTraceAsString());
            return 'Hata oluştu: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $url = $_POST['url'] ?? '';
        $isPlaylist = isset($_POST['isPlaylist']);
        
        if (empty($url)) {
            throw new Exception('URL gerekli!');
        }
        
        // URL kontrolü
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Geçersiz URL formatı!');
        }
        
        // YouTube URL kontrolü
        if (strpos($url, 'youtube.com') === false && strpos($url, 'youtu.be') === false 
            && strpos($url, 'music.youtube.com') === false) {
            throw new Exception('Sadece YouTube URLleri desteklenmektedir!');
        }
        
        $downloader = new YouTubeDownloader();
        $result = $downloader->download($url, $isPlaylist);
        
        echo json_encode(['message' => $result], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} 