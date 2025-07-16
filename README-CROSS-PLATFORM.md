# Cross-Platform Binary Management

Bu proje artık otomatik platform algılama ve binary yönetimi ile birlikte geliyor! Artık farklı işletim sistemleri için manuel olarak yol belirtmenize gerek yok.

## 🚀 Nasıl Çalışır

### Otomatik Platform Algılama
Sistem şunları otomatik olarak algılar:
- **Windows**: `.exe` uzantılı dosyaları arar
- **Linux**: Uzantısız binary dosyaları arar  
- **macOS**: Uzantısız binary dosyaları arar

### Binary Dosya Yapısı

Projenizin kök dizininde `bin/` klasörü oluşturun ve gerekli binary'leri koyun:

```
your-project/
├── composer.json
├── bin/
│   ├── ffmpeg.exe     (Windows)
│   ├── ffmpeg         (Linux/macOS)
│   ├── yt-dlp.exe     (Windows)
│   └── yt-dlp         (Linux/macOS)
└── src/
```

## 📥 Binary Kurulumu

### Windows için
```bash
# ffmpeg
# https://www.gyan.dev/ffmpeg/builds/ffmpeg-release-essentials.zip adresinden indirin
# Zip içinden ffmpeg.exe'yi çıkarıp bin/ klasörüne koyun

# yt-dlp
curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp.exe -o bin/yt-dlp.exe
```

### Linux için
```bash
# ffmpeg
wget https://johnvansickle.com/ffmpeg/releases/ffmpeg-release-amd64-static.tar.xz
tar -xf ffmpeg-release-amd64-static.tar.xz
cp ffmpeg-*-amd64-static/ffmpeg bin/

# yt-dlp
curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o bin/yt-dlp
chmod +x bin/yt-dlp
```

### macOS için
```bash
# ffmpeg (Homebrew ile)
brew install ffmpeg
cp $(which ffmpeg) bin/

# veya direkt indirin
curl -L https://evermeet.cx/ffmpeg/ffmpeg-latest.zip -o ffmpeg.zip
unzip ffmpeg.zip
mv ffmpeg bin/

# yt-dlp
curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp_macos -o bin/yt-dlp
chmod +x bin/yt-dlp
```

## 💻 Kod Kullanımı

### Basit Kullanım

```php
<?php

use Darkwob\YoutubeMp3Converter\Converter\YouTubeConverter;
use Darkwob\YoutubeMp3Converter\Converter\Util\PlatformDetector;
use Darkwob\YoutubeMp3Converter\Progress\FileProgress;

// Platform bilgilerini kontrol edin
echo "Platform: " . PlatformDetector::detect() . "\n";
echo "Bin klasörü: " . PlatformDetector::getBinPath() . "\n";

// Gereksinimler kontrolü
$requirements = PlatformDetector::checkRequirements();
foreach ($requirements as $binary => $info) {
    if (!$info['exists']) {
        echo "❌ {$binary} bulunamadı!\n";
        echo $info['instructions'] . "\n\n";
        exit(1);
    } else {
        echo "✅ {$binary} hazır: {$info['path']}\n";
    }
}

// Converter kullanımı (artık binPath parametresi yok!)
$converter = new YouTubeConverter(
    outputPath: './downloads',
    tempPath: './temp', 
    progress: new FileProgress('./progress')
);

$result = $converter->processVideo('https://www.youtube.com/watch?v=VIDEO_ID');

echo "Dönüştürüldü: {$result->getOutputPath()}\n";
echo "Başlık: {$result->getTitle()}\n";
echo "Boyut: " . number_format($result->getSize() / 1024 / 1024, 2) . " MB\n";
```

### Platform Detector Özellikleri

```php
<?php

use Darkwob\YoutubeMp3Converter\Converter\Util\PlatformDetector;

// Platform algılama
$platform = PlatformDetector::detect(); // 'windows', 'linux', 'macos'
$isWindows = PlatformDetector::isWindows();
$isLinux = PlatformDetector::isLinux();

// Binary dosya isimleri
$ffmpegName = PlatformDetector::getBinaryFilename('ffmpeg');
// Windows: 'ffmpeg.exe', Linux/macOS: 'ffmpeg'

// Binary yolları
$ffmpegPath = PlatformDetector::getBinaryPath('ffmpeg');
// Windows: 'C:\path\to\project\bin\ffmpeg.exe'
// Linux: '/path/to/project/bin/ffmpeg'

// Binary varlığı kontrolü
if (PlatformDetector::binaryExists('yt-dlp')) {
    echo "yt-dlp kullanılabilir!";
}

// Binary çalıştırma
$result = PlatformDetector::executeBinary('yt-dlp', ['--version']);
if ($result['success']) {
    echo "Versiyon: " . trim($result['output'][0]);
}

// Symfony Process entegrasyonu
$command = PlatformDetector::createCommand('ffmpeg', ['-version']);
$process = new \Symfony\Component\Process\Process($command);
$process->run();
```

## 🔧 Kurulum Kontrolü

Platform detector otomatik kurulum talimatları verir:

```php
<?php

$requirements = PlatformDetector::checkRequirements(['ffmpeg', 'yt-dlp']);

foreach ($requirements as $binary => $info) {
    if (!$info['exists']) {
        echo $info['instructions'] . "\n";
        // Platform-specific download linklerini ve kurulum adımlarını gösterir
    }
}
```

## 🎯 Avantajlar

1. **Tek Kod Tabanı**: Tüm platformlarda aynı kod çalışır
2. **Otomatik Binary Algılama**: Platform ve dosya uzantıları otomatik algılanır  
3. **Akıllı Kurulum**: Kurulum talimatları platform-specific
4. **Kolay Hata Ayıklama**: Açık hata mesajları ve kurulum yönergeleri
5. **Güvenli**: Komut parametreleri otomatik escape edilir

## 🛠️ Örnek Çalıştırma

```bash
# Önce test edin
cd your-project
php examples/PlatformBinaryExample.php

# Çıktı örneği:
# Platform Information:
#   platform: windows
#   is_windows: true
#   bin_path: C:\path\to\project\bin
# 
# Checking Required Binaries:
#   ffmpeg: FOUND
#   yt-dlp: FOUND
```

## 🚨 Sorun Giderme

### Binary Bulunamıyor
```
Missing required binaries: yt-dlp

To install yt-dlp:
1. Create bin directory: /path/to/project/bin
2. Download from: https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp
3. Extract and place the executable as: /path/to/project/bin/yt-dlp
4. Make sure the file is executable (chmod +x on Unix systems)
```

### İzin Sorunları (Linux/macOS)
```bash
chmod +x bin/ffmpeg
chmod +x bin/yt-dlp
```

### Windows'ta PATH Sorunları
- Binary'leri doğrudan `bin/` klasörüne koyun
- Antivirus yazılımları binary'leri engelleyebilir

Artık cross-platform uyumlu bir YouTube MP3 converter'a sahipsiniz! 🎉 