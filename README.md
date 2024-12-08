# 🎵 YouTube to MP3 Converter

![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-blue)
![Status](https://img.shields.io/badge/Status-Beta-yellow)
![License](https://img.shields.io/badge/License-MIT-green)

A powerful YouTube to MP3 converter that supports both YouTube and YouTube Music, including playlist functionality.

## ✨ Features

- 🎵 Convert YouTube videos to MP3
- 📑 Playlist support
- 🎧 YouTube Music support
- 📊 Real-time progress tracking
- 🎯 Clean and modern UI
- 🔄 Automatic file cleanup

## 🚀 Installation

```bash
composer require darkwob/youtube-mp3-converter
```

## 💻 Usage

### Basic Usage

```php
use Darkwob\YoutubeMp3Converter\Converter\YouTubeConverter;
use Darkwob\YoutubeMp3Converter\Progress\FileProgress;

// Initialize progress tracker
$progress = new FileProgress(__DIR__ . '/progress');

// Initialize converter
$converter = new YouTubeConverter(
    __DIR__ . '/bin',           // Path to yt-dlp and ffmpeg binaries
    __DIR__ . '/downloads',     // Output directory for MP3 files
    __DIR__ . '/temp',          // Temporary directory for downloads
    $progress                   // Progress tracker instance
);

// Convert a video
try {
    $result = $converter->processVideo('https://www.youtube.com/watch?v=VIDEO_ID');
    
    if ($result['success']) {
        foreach ($result['results'] as $video) {
            echo "Converted: {$video['title']}\n";
            echo "File: {$video['file']}\n";
        }
    }
} catch (ConverterException $e) {
    echo "Error: " . $e->getMessage();
}
```

### Progress Tracking

```php
use Darkwob\YoutubeMp3Converter\Progress\FileProgress;

$progress = new FileProgress(__DIR__ . '/progress');

// Update progress
$progress->update('video123', 'downloading', 50, 'Downloading video...');

// Get progress
$status = $progress->get('video123');
echo $status['progress']; // 50
echo $status['message']; // "Downloading video..."

// Delete progress
$progress->delete('video123');

// Cleanup old progress files (older than 1 hour)
$progress->cleanup(3600);
```

### Web Interface Demo

The package includes a complete web interface demo in the `demo` directory. To use it:

1. Copy the `demo` directory to your web server
2. Install dependencies:
   ```bash
   composer install
   ```
3. Download required binaries:
   - Download [yt-dlp](https://github.com/yt-dlp/yt-dlp) and place it in `demo/bin`
   - Download [FFmpeg](https://ffmpeg.org/) and place it in `demo/bin`
4. Make sure these directories are writable:
   - `demo/downloads`
   - `demo/temp`
   - `demo/progress`
5. Access the demo through your web browser

## 🔧 Requirements

- PHP >= 7.4
- JSON extension
- [yt-dlp](https://github.com/yt-dlp/yt-dlp)
- [FFmpeg](https://ffmpeg.org/)
- Write permissions for output directories

## 🛠️ Configuration

The converter accepts additional options in its constructor:

```php
$options = [
    'format' => 'bestaudio/best',
    'audio-quality' => 0, // 0 (best) to 9 (worst)
    'embed-thumbnail' => true,
    'add-metadata' => true
];

$converter = new YouTubeConverter($binPath, $outputDir, $tempDir, $progress, $options);
```

## 🔒 Error Handling

The package uses custom exceptions for different error cases:

```php
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;

try {
    $result = $converter->processVideo($url);
} catch (ConverterException $e) {
    switch (true) {
        case $e instanceof ConverterException:
            echo "Converter error: " . $e->getMessage();
            break;
        default:
            echo "Unexpected error: " . $e->getMessage();
    }
}
```

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request. 