# 🎵 YouTube to MP3 Converter

![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-blue)
![Status](https://img.shields.io/badge/Status-Beta-yellow)
![License](https://img.shields.io/badge/License-MIT-green)

A powerful YouTube to MP3 converter that supports both YouTube and YouTube Music, including playlist functionality. This project is currently in **BETA** stage.

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
use Darkwob\YoutubeMp3Converter\Progress\FileProgress;

// Initialize progress tracker
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

### Error Handling

```php
use Darkwob\YoutubeMp3Converter\Progress\FileProgress;
use Darkwob\YoutubeMp3Converter\Progress\Exceptions\ProgressException;

try {
    $progress = new FileProgress('/invalid/path');
} catch (ProgressException $e) {
    echo "Error: " . $e->getMessage();
}
```

## 🔧 Requirements

- PHP >= 7.4
- JSON extension
- Write permissions for progress directory

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request. 