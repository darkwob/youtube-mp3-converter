<?php
// Hata raporlamayı aktifleştir
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Timeout süresini artır (10 saat)
ini_set('max_execution_time', 36000);     // 10 saat = 36000 saniye
ini_set('max_input_time', 36000);         // Input işleme süresi
set_time_limit(36000);                    // Script çalışma süresi
ini_set('memory_limit', '1024M');         // Bellek limitini artır

// CORS başlıklarını ekle
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Gelen istekleri logla
error_log('REQUEST: ' . print_r($_REQUEST, true));
error_log('POST: ' . print_r($_POST, true));
error_log('GET: ' . print_r($_GET, true));

class VideoProcessor {
    private $outputDir;
    private $ytdlPath;
    private $ffmpegPath;
    private $tempDir;
    private $progressFile;
    
    public function __construct() {
        try {
            // Yol ayarlarını ileri slash ile düzelt
            $baseDir = str_replace('\\', '/', __DIR__);
            $this->outputDir = $baseDir . '/downloads';
            $this->tempDir = $baseDir . '/temp';
            $this->ytdlPath = $baseDir . '/bin/yt-dlp.exe';
            $this->ffmpegPath = $baseDir . '/bin/ffmpeg.exe';
            $this->progressFile = $this->tempDir . '/progress';
            
            // Gerekli dosyaların varlığını ve çalıştırılabilirliğini kontrol et
            if (!file_exists($this->ytdlPath)) {
                throw new Exception('yt-dlp.exe bulunamadı: ' . $this->ytdlPath);
            }
            if (!file_exists($this->ffmpegPath)) {
                throw new Exception('ffmpeg.exe bulunamadı: ' . $this->ffmpegPath);
            }
            
            // FFmpeg sürümünü kontrol et
            exec(sprintf('"%s" -version', $this->ffmpegPath), $ffmpegOutput, $ffmpegReturn);
            if ($ffmpegReturn !== 0) {
                throw new Exception('FFmpeg çalıştırılamıyor: ' . implode("\n", $ffmpegOutput));
            }
            error_log('FFmpeg sürüm bilgisi: ' . print_r($ffmpegOutput, true));
            
            // yt-dlp sürümünü kontrol et
            exec(sprintf('"%s" --version', $this->ytdlPath), $ytdlpOutput, $ytdlpReturn);
            if ($ytdlpReturn !== 0) {
                throw new Exception('yt-dlp çalıştırılamıyor: ' . implode("\n", $ytdlpOutput));
            }
            error_log('yt-dlp sürüm bilgisi: ' . print_r($ytdlpOutput, true));
            
            // Klasörleri oluştur ve izinleri kontrol et
            foreach ([$this->outputDir, $this->tempDir, $this->progressFile] as $dir) {
                if (!file_exists($dir)) {
                    error_log('Klasör oluşturuluyor: ' . $dir);
                    if (!mkdir($dir, 0777, true)) {
                        throw new Exception('Klasör oluşturulamadı: ' . $dir);
                    }
                }
                if (!is_writable($dir)) {
                    throw new Exception('Yazma izni yok: ' . $dir);
                }
                error_log('Klasör hazır ve yazılabilir: ' . $dir);
            }
        } catch (Exception $e) {
            error_log('Constructor Hatası: ' . $e->getMessage());
            throw $e;
        }
    }
    
    private function updateProgress($id, $stage, $progress, $message = '') {
        try {
            static $lastUpdate = [];
            $now = microtime(true);
            
            // Her 0.1 saniyede bir güncelle
            if (!isset($lastUpdate[$id]) || ($now - $lastUpdate[$id]) >= 0.1) {
                $progressFile = $this->progressFile . '/' . $id . '.json';
                
                // Eğer completed ise ve dosya varsa, progress'i 100 yap
                if ($stage === 'completed') {
                    $progress = 100;
                }
                
                $progressData = [
                    'stage' => $stage,
                    'progress' => round($progress, 1),
                    'message' => $message
                ];
                
                // Progress dosyasını güncelle
                file_put_contents($progressFile, json_encode($progressData));
                $lastUpdate[$id] = $now;
                
                error_log("Progress güncellendi - ID: $id, Stage: $stage, Progress: $progress, Message: $message");
            }
        } catch (Exception $e) {
            error_log("Progress güncelleme hatası: " . $e->getMessage());
        }
    }
    
    private function cleanupTempFiles($id, $title) {
        try {
            error_log("Geçici dosyalar temizleniyor - ID: $id, Title: $title");
            
            // Tüm olası dosya formatları
            $extensions = ['webm', 'mp4', 'm4a', 'opus', 'part', 'ytdl', 'temp'];
            
            // Temp klasöründeki dosyaları temizle
            foreach ($extensions as $ext) {
                $files = glob($this->tempDir . '/*.' . $ext);
                foreach ($files as $file) {
                    error_log('Temp dosyası siliniyor: ' . $file);
                    @unlink($file);
                }
            }
            
            // Ana dizindeki dosyaları temizle
            foreach ($extensions as $ext) {
                $files = glob(__DIR__ . '/*.' . $ext);
                foreach ($files as $file) {
                    error_log('Ana dizin dosyası siliniyor: ' . $file);
                    @unlink($file);
                }
            }
            
            // Progress dosyasını temizle
            $progressFile = $this->progressFile . '/' . $id . '.json';
            if (file_exists($progressFile)) {
                error_log('Progress dosyası siliniyor: ' . $progressFile);
                @unlink($progressFile);
            }
            
            error_log("Temizleme tamamlandı");
            
        } catch (Exception $e) {
            error_log("Temizleme hatası: " . $e->getMessage());
        }
    }
    
    private function sanitizeFileName($fileName) {
        if (empty($fileName)) {
            return 'video_' . uniqid();
        }

        // Türkçe karakterleri düzgün şekilde değiştir
        $tr = array('��','Ş','ı','İ','ğ','Ğ','ü','Ü','ö','Ö','ç','Ç');
        $eng = array('s','S','i','I','g','G','u','U','o','O','c','C');
        $fileName = str_replace($tr, $eng, $fileName);
        
        // Windows için geçersiz karakterleri kaldır
        $fileName = str_replace(['<', '>', ':', '"', '/', '\\', '|', '?', '*'], '', $fileName);
        
        // Özel karakterleri ve fazla boşlukları temizle
        $fileName = preg_replace('/[^\p{L}\p{N}\s\-\(\)]/u', '', $fileName);
        $fileName = preg_replace('/\s+/', ' ', $fileName);
        $fileName = trim($fileName);
        
        // Maksimum uzunluğu sınırla
        $fileName = mb_substr($fileName, 0, 200);
        
        // Boş string kontrolü
        if (empty(trim($fileName))) {
            return 'video_' . uniqid();
        }
        
        return $fileName;
    }
    
    private function downloadVideo($url, $id) {
        try {
            // Video bilgilerini al
            $videoInfo = $this->getVideoInfo($url);
            if (empty($videoInfo['videos'])) {
                throw new Exception('Video bilgileri alınamadı');
            }
            
            // Video başlığını bul
            $videoTitle = '';
            foreach ($videoInfo['videos'] as $video) {
                if ($video['id'] === $id) {
                    $videoTitle = $video['title'];
                    break;
                }
            }
            
            if (empty($videoTitle)) {
                throw new Exception('Video başlığı alınamadı');
            }
            
            // Başlığı dosya adı için uygun hale getir
            $safeTitle = $this->sanitizeFileName($videoTitle);
            error_log('Orijinal başlık: ' . $videoTitle);
            error_log('Güvenli başlık: ' . $safeTitle);
            
            // Log dosyası yolu
            $logFile = $this->tempDir . '/' . $id . '.log';
            
            try {
                // Çalışma dizinini temp klasörüne değiştir
                $originalDir = getcwd();
                chdir($this->tempDir);
                error_log('Çalışma dizini değiştirildi: ' . getcwd());
                
                // İndirme komutu - webm formatını tercih et
                $downloadCmd = sprintf(
                    '"%s" --newline --progress-template "download:%%(progress)s" ' .
                    '-f "bestaudio[ext=webm]/bestaudio[ext=m4a]/bestaudio" ' .
                    '--no-playlist --no-colors ' .
                    '--paths "%s" ' .
                    '--output "%s.%%(ext)s" ' .
                    '%s 2>&1',
                    $this->ytdlPath,
                    $this->tempDir,
                    $safeTitle,
                    escapeshellarg($url)
                );
                
                // Komutu logla
                error_log("İndirme komutu: " . $downloadCmd);
                file_put_contents($logFile, "İndirme komutu: " . $downloadCmd . "\n", FILE_APPEND);
                
                // İndirme işlemini başlat
                $process = popen($downloadCmd, 'r');
                $downloadOutput = '';
                
                if ($process) {
                    while (!feof($process)) {
                        $line = fgets($process);
                        if ($line === false) continue;
                        
                        $downloadOutput .= $line;
                        file_put_contents($logFile, $line, FILE_APPEND);
                        
                        if (strpos($line, 'download:') !== false) {
                            if (preg_match('/download:([0-9.]+)/', $line, $matches)) {
                                $progress = floatval($matches[1]) * 100;
                                $this->updateProgress($id, 'downloading', $progress, 'Ses indiriliyor...');
                            }
                        }
                    }
                    
                    $returnValue = pclose($process);
                    if ($returnValue !== 0) {
                        error_log("İndirme çıktısı: " . $downloadOutput);
                        throw new Exception('İndirme başarısız oldu. Çıktı: ' . $downloadOutput);
                    }
                } else {
                    throw new Exception('İndirme işlemi başlatılamadı');
                }
                
                // İndirilen dosyayı bul
                $audioFiles = array_merge(
                    glob($this->tempDir . '/' . $safeTitle . '.webm'),
                    glob($this->tempDir . '/' . $safeTitle . '.m4a'),
                    glob($this->tempDir . '/' . $safeTitle . '.opus')
                );
                
                if (empty($audioFiles)) {
                    error_log("Temp klasöründe dosya bulunamadı, ana dizinde aranıyor...");
                    // Ana dizinde de ara
                    $audioFiles = array_merge(
                        glob(__DIR__ . '/' . $safeTitle . '.webm'),
                        glob(__DIR__ . '/' . $safeTitle . '.m4a'),
                        glob(__DIR__ . '/' . $safeTitle . '.opus')
                    );
                }
                
                if (empty($audioFiles)) {
                    error_log("Hiçbir ses dosyası bulunamadı!");
                    throw new Exception('İndirilen ses dosyası bulunamadı');
                }
                
                $audioFile = $audioFiles[0];
                error_log('Bulunan ses dosyası: ' . $audioFile);
                
                if (!file_exists($audioFile)) {
                    throw new Exception('Ses dosyası mevcut değil: ' . $audioFile);
                }
                
                // Ana dizindeki dosyayı temp'e taşı
                if (dirname($audioFile) !== $this->tempDir) {
                    $newAudioFile = $this->tempDir . '/' . basename($audioFile);
                    if (!rename($audioFile, $newAudioFile)) {
                        throw new Exception('Ses dosyası temp klasörüne taşınamadı');
                    }
                    $audioFile = $newAudioFile;
                }
                
                $this->updateProgress($id, 'converting', 50, 'MP3\'e dönüştürülüyor...');
                
                // MP3'e dönüştürme komutu
                $mp3File = $this->outputDir . '/' . $safeTitle . '.mp3';
                $convertCmd = sprintf(
                    '"%s" -i "%s" -vn -ar 44100 -ac 2 -b:a 192k "%s" 2>&1',
                    $this->ffmpegPath,
                    $audioFile,
                    $mp3File
                );
                
                // Dönüştürme komutunu logla
                error_log("Dönüştürme komutu: " . $convertCmd);
                file_put_contents($logFile, "Dönüştürme komutu: " . $convertCmd . "\n", FILE_APPEND);
                
                // Dönüştürme işlemini başlat
                exec($convertCmd, $convertOutput, $convertReturn);
                error_log("FFmpeg çıktısı: " . print_r($convertOutput, true));
                
                if ($convertReturn !== 0) {
                    throw new Exception('MP3 dönüştürme başarısız oldu: ' . implode("\n", $convertOutput));
                }
                
                // MP3 dosyasının varlığını kontrol et
                if (!file_exists($mp3File)) {
                    throw new Exception('MP3 dosyası oluşturulamadı');
                }
                
                // Başarılı dönüşüm, geçici dosyaları temizle
                if (file_exists($audioFile)) {
                    @unlink($audioFile);
                }
                
                // Ana dizindeki olası dosyaları da temizle
                $mainDirFiles = array_merge(
                    glob(__DIR__ . '/' . $safeTitle . '.webm'),
                    glob(__DIR__ . '/' . $safeTitle . '.m4a'),
                    glob(__DIR__ . '/' . $safeTitle . '.opus')
                );
                
                foreach ($mainDirFiles as $file) {
                    @unlink($file);
                }
                
                // Progress'i completed olarak güncelle
                $this->updateProgress($id, 'completed', 100, 'Dönüştürme tamamlandı!');
                
                return basename($mp3File);
                
            } catch (Exception $e) {
                error_log("İşlem hatası: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                // Hata durumunda geçici dosyaları temizle
                $this->cleanupTempFiles($id, $safeTitle);
                throw $e;
            } finally {
                // Çalışma dizinini geri al
                chdir($originalDir);
            }
            
        } catch (Exception $e) {
            error_log("Video işleme hatası: " . $e->getMessage());
            $this->updateProgress($id, 'error', -1, $e->getMessage());
            throw $e;
        }
    }
    
    public function processVideo($url) {
        try {
            // Video bilgilerini al
            $info = $this->getVideoInfo($url);
            
            if (empty($info['videos'])) {
                throw new Exception('Video bilgileri alınamadı');
            }
            
            $results = [];
            $totalVideos = count($info['videos']);
            error_log("Toplam işlenecek video sayısı: " . $totalVideos);
            
            foreach ($info['videos'] as $index => $video) {
                $id = $video['id'];
                $currentVideo = $index + 1;
                
                try {
                    error_log("Video işleniyor {$currentVideo}/{$totalVideos}: {$video['title']}");
                    $this->updateProgress($id, 'downloading', 0, "Video {$currentVideo}/{$totalVideos} indiriliyor...");
                    $mp3Path = $this->downloadVideo($video['url'], $id);
                    
                    if (!file_exists($this->outputDir . '/' . $mp3Path)) {
                        throw new Exception('MP3 dosyası oluşturulamadı');
                    }
                    
                    $results[] = [
                        'id' => $id,
                        'title' => $video['title'],
                        'status' => 'success',
                        'file' => $mp3Path
                    ];
                    
                    error_log("Video başarıyla işlendi: {$video['title']}");
                    
                    // Her video sonrası temizlik yap
                    $this->cleanupTempFiles($id, $this->sanitizeFileName($video['title']));
                    
                } catch (Exception $e) {
                    error_log("Video işleme hatası ({$video['title']}): " . $e->getMessage());
                    $this->updateProgress($id, 'error', -1, $e->getMessage());
                    $results[] = [
                        'id' => $id,
                        'title' => $video['title'],
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                    
                    // Hata durumunda da temizlik yap
                    $this->cleanupTempFiles($id, $this->sanitizeFileName($video['title']));
                }
                
                // Her video sonrası belleği temizle
                gc_collect_cycles();
            }
            
            // Tüm işlemler bittikten sonra son bir temizlik daha yap
            $this->cleanupAllFiles();
            
            return [
                'success' => true,
                'is_playlist' => $info['is_playlist'],
                'playlist_title' => $info['playlist_title'] ?? '',
                'total_videos' => $totalVideos,
                'processed_videos' => count($results),
                'results' => $results
            ];
            
        } catch (Exception $e) {
            error_log("Genel hata: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function getVideoInfo($url) {
        try {
            // music.youtube.com linklerini normal youtube.com'a çevir
            if (strpos($url, 'music.youtube.com') !== false) {
                $url = str_replace('music.youtube.com', 'www.youtube.com', $url);
                error_log('URL dönüştürüldü: ' . $url);
            }
            
            // Önce video bilgilerini al
            $cmd = sprintf(
                '"%s" --no-warnings --ignore-errors --print-json --yes-playlist --no-check-certificates --extract-audio --format bestaudio --no-playlist-reverse "%s" 2>&1',
                $this->ytdlPath,
                escapeshellarg($url)
            );
            
            error_log('Video bilgisi alınıyor. Komut: ' . $cmd);
            
            exec($cmd, $output, $returnVar);
            error_log('Komut çıktısı satır sayısı: ' . count($output));
            
            $videos = [];
            $isPlaylist = false;
            $playlistTitle = '';
            
            foreach ($output as $index => $line) {
                if (empty(trim($line))) continue;
                
                error_log('JSON çözümleniyor [' . $index . ']: ' . substr($line, 0, 100) . '...');
                
                try {
                    $info = json_decode($line, true);
                    if (!$info) continue;
                    
                    // Playlist veya mix kontrolü
                    if (isset($info['_type']) && ($info['_type'] === 'playlist' || strpos($url, 'list=') !== false)) {
                        $isPlaylist = true;
                        $playlistTitle = $info['title'] ?? '';
                        error_log('Playlist/Mix tespit edildi: ' . $playlistTitle);
                        
                        // Entries varsa işle
                        if (isset($info['entries'])) {
                            foreach ($info['entries'] as $entry) {
                                if (isset($entry['id'])) {
                                    $videos[] = [
                                        'id' => $entry['id'],
                                        'title' => $entry['title'] ?? 'Video ' . count($videos),
                                        'url' => "https://www.youtube.com/watch?v=" . $entry['id']
                                    ];
                                }
                            }
                        }
                        continue;
                    }
                    
                    // Tekil video
                    if (isset($info['id'])) {
                        $videos[] = [
                            'id' => $info['id'],
                            'title' => $info['title'] ?? 'Video ' . count($videos),
                            'url' => isset($info['webpage_url']) ? $info['webpage_url'] : "https://www.youtube.com/watch?v=" . $info['id']
                        ];
                        error_log('Video eklendi: ' . $info['id'] . ' - ' . ($info['title'] ?? 'Başlıksız'));
                    }
                } catch (Exception $e) {
                    error_log('JSON parse hatası: ' . $e->getMessage());
                    continue;
                }
            }
            
            // Hiç video bulunamadıysa tekrar dene
            if (empty($videos)) {
                error_log('İlk denemede video bulunamadı, tekrar deneniyor...');
                
                // Tekrar dene - daha basit komutla
                $cmd = sprintf(
                    '"%s" --no-warnings --ignore-errors --print-json --yes-playlist --flat-playlist "%s" 2>&1',
                    $this->ytdlPath,
                    escapeshellarg($url)
                );
                
                exec($cmd, $output, $returnVar);
                
                foreach ($output as $line) {
                    if (empty(trim($line))) continue;
                    
                    try {
                        $info = json_decode($line, true);
                        if (!$info) continue;
                        
                        if (isset($info['id'])) {
                            $videos[] = [
                                'id' => $info['id'],
                                'title' => $info['title'] ?? 'Video ' . count($videos),
                                'url' => "https://www.youtube.com/watch?v=" . $info['id']
                            ];
                        }
                    } catch (Exception $e) {
                        error_log('JSON parse hatası (2. deneme): ' . $e->getMessage());
                        continue;
                    }
                }
            }
            
            if (empty($videos)) {
                error_log('Hiç video bulunamadı!');
                throw new Exception('Video bilgileri alınamadı veya playlist boş');
            }
            
            error_log('Toplam ' . count($videos) . ' video bulundu. Playlist: ' . ($isPlaylist ? 'Evet' : 'Hayır'));
            
            return [
                'videos' => $videos,
                'is_playlist' => $isPlaylist,
                'playlist_title' => $playlistTitle
            ];
            
        } catch (Exception $e) {
            error_log('Video bilgisi alma hatası: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function getProgress($id) {
        try {
            $progressFile = $this->progressFile . '/' . $id . '.json';
            
            // Önce video başlığını bul
            $videoTitle = '';
            $videoInfo = $this->getVideoInfo("https://www.youtube.com/watch?v=" . $id);
            if ($videoInfo && !empty($videoInfo['videos'])) {
                foreach ($videoInfo['videos'] as $video) {
                    if ($video['id'] === $id) {
                        $videoTitle = $video['title'];
                        break;
                    }
                }
            }
            
            if (!empty($videoTitle)) {
                $safeTitle = $this->sanitizeFileName($videoTitle);
                error_log("Progress kontrolü için aranan dosya başlığı: " . $safeTitle);
                
                // MP3 dosyasının varlığını kontrol et
                $mp3Files = glob($this->outputDir . '/*.mp3');
                foreach ($mp3Files as $mp3File) {
                    $baseName = basename($mp3File, '.mp3');
                    error_log("Karşılaştırılıyor - Dosya: " . $baseName . " ile Başlık: " . $safeTitle);
                    
                    // Tam eşleşme veya başlık dosya adının içinde var mı kontrol et
                    if ($baseName === $safeTitle || strpos($baseName, $safeTitle) !== false) {
                        error_log("MP3 dosyası bulundu: " . $mp3File);
                        return [
                            'success' => true,
                            'data' => [
                                'stage' => 'completed',
                                'progress' => 100,
                                'message' => 'Dönüştürme tamamlandı!'
                            ]
                        ];
                    }
                }
            }
            
            // Progress dosyası kontrolü
            if (file_exists($progressFile)) {
                $data = json_decode(file_get_contents($progressFile), true);
                if ($data) {
                    // Eğer converting durumunda ve dosya varsa completed'a çek
                    if ($data['stage'] === 'converting' && !empty($videoTitle)) {
                        $safeTitle = $this->sanitizeFileName($videoTitle);
                        $mp3Files = glob($this->outputDir . '/*.mp3');
                        foreach ($mp3Files as $mp3File) {
                            $baseName = basename($mp3File, '.mp3');
                            if ($baseName === $safeTitle || strpos($baseName, $safeTitle) !== false) {
                                return [
                                    'success' => true,
                                    'data' => [
                                        'stage' => 'completed',
                                        'progress' => 100,
                                        'message' => 'Dönüştürme tamamlandı!'
                                    ]
                                ];
                            }
                        }
                    }
                    return [
                        'success' => true,
                        'data' => $data
                    ];
                }
            }
            
            // Hala dönüştürme devam ediyor olabilir
            return [
                'success' => true,
                'data' => [
                    'stage' => 'converting',
                    'progress' => 50,
                    'message' => 'MP3\'e dönüştürülüyor...'
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Progress kontrol hatası: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Hata durumunda bile MP3 dosyası var mı kontrol et
            try {
                $mp3Files = glob($this->outputDir . '/*.mp3');
                foreach ($mp3Files as $mp3File) {
                    // Son oluşturulan MP3 dosyası mı kontrol et
                    if (time() - filemtime($mp3File) < 300) { // Son 5 dakika içinde oluşturulmuş
                        return [
                            'success' => true,
                            'data' => [
                                'stage' => 'completed',
                                'progress' => 100,
                                'message' => 'Dönüştürme tamamlandı!'
                            ]
                        ];
                    }
                }
            } catch (Exception $e2) {
                error_log("MP3 kontrol hatası: " . $e2->getMessage());
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function killDownloadProcesses() {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows için
            exec('taskkill /F /IM yt-dlp.exe 2>&1', $output, $returnCode);
            exec('taskkill /F /IM ffmpeg.exe 2>&1', $output, $returnCode);
        } else {
            // Linux/Unix için
            exec('pkill -f yt-dlp 2>&1', $output, $returnCode);
            exec('pkill -f ffmpeg 2>&1', $output, $returnCode);
        }
        error_log("İndirme işlemleri sonlandırıldı");
    }

    private function cleanupAllFiles() {
        try {
            error_log("Tüm geçici dosyalar temizleniyor...");
            
            // Önce çalışan indirme işlemlerini sonlandır
            $this->killDownloadProcesses();
            
            // Progress klasörünü temizle
            $progressFiles = glob($this->progressFile . '/*.json');
            foreach ($progressFiles as $file) {
                error_log('Progress dosyası siliniyor: ' . $file);
                @unlink($file);
            }
            
            // Temp klasörünü temizle
            $tempFiles = glob($this->tempDir . '/*.*');
            foreach ($tempFiles as $file) {
                error_log('Temp dosyası siliniyor: ' . $file);
                @unlink($file);
            }
            
            // Ana dizindeki geçici dosyaları temizle
            $extensions = ['webm', 'mp4', 'm4a', 'opus', 'part', 'ytdl', 'temp'];
            foreach ($extensions as $ext) {
                $files = glob(__DIR__ . '/*.' . $ext);
                foreach ($files as $file) {
                    error_log('Ana dizin dosyası siliniyor: ' . $file);
                    @unlink($file);
                }
            }
            
            error_log("Tüm temizleme işlemi tamamlandı");
            
        } catch (Exception $e) {
            error_log("Genel temizleme hatası: " . $e->getMessage());
        }
    }

    public function __destruct() {
        // Sınıf yok edildiğinde tüm işlemleri temizle
        $this->killDownloadProcesses();
        $this->cleanupAllFiles();
    }
}

try {
    error_log('İşlem başlatılıyor...');
    
    $action = $_REQUEST['action'] ?? '';
    error_log('Action: ' . $action);
    
    if (empty($action)) {
        throw new Exception('Action parametresi gerekli!');
    }
    
    $processor = new VideoProcessor();
    
    switch ($action) {
        case 'process':
            $url = $_POST['url'] ?? '';
            error_log('URL: ' . $url);
            
            if (empty($url)) {
                throw new Exception('URL gerekli!');
            }
            
            $result = $processor->processVideo($url);
            error_log('İşlem sonucu: ' . print_r($result, true));
            echo json_encode($result);
            break;
            
        case 'progress':
            $id = $_GET['id'] ?? '';
            error_log('Progress ID: ' . $id);
            
            if (empty($id)) {
                throw new Exception('Video ID gerekli!');
            }
            
            $result = $processor->getProgress($id);
            error_log('Progress sonucu: ' . print_r($result, true));
            echo json_encode($result);
            break;
            
        default:
            throw new Exception('Geçersiz işlem: ' . $action);
    }
    
} catch (Exception $e) {
    error_log('HATA: ' . $e->getMessage());
    error_log('Stack Trace: ' . $e->getTraceAsString());
    
    $error = [
        'success' => false,
        'error' => $e->getMessage(),
        'details' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ];
    
    echo json_encode($error);
} 