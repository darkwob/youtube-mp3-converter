# 🎵 YouTube to MP3 Converter

![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.3-blue)
![Status](https://img.shields.io/badge/Status-Stable-green)
![License](https://img.shields.io/badge/License-MIT-green)

A powerful and feature-rich YouTube to MP3 converter library for PHP 8.3+ that supports YouTube video conversion with extensive customization options, progress tracking, and Windows compatibility with enhanced binary management.

## ✨ Key Features

- 🎵 Convert YouTube videos to multiple audio formats (MP3, AAC, FLAC, WAV, etc.)
- 📊 Real-time progress tracking (File-based or Redis)
- 🌐 Remote server conversion support
- 🔒 Clean and modern PHP 8.3+ API with readonly properties
- 🛠️ Extensive configuration options (quality, metadata, thumbnails)
- 🎯 ConversionResult objects for type-safe results
- 🔄 Cross-platform compatibility (Windows, Linux, macOS)
- 🚀 Robust error handling with specific exception types
- 🪟 Enhanced Windows support with path normalization
- 🔧 Intelligent binary detection and management
- 📁 Advanced directory and process management

## 🚀 Installation

```bash
composer require darkwob/youtube-mp3-converter
```

### Requirements

- PHP >= 8.3 (required)
- JSON extension
- FFmpeg (required for audio conversion)
- yt-dlp (required for video downloading)
- Redis (optional, for Redis-based progress tracking)
- Windows: Proper PATH environment or binary placement in project directory

## 💻 Basic Usage

### Simple Video Conversion

```php
use Darkwob\YoutubeMp3Converter\Converter\YouTubeConverter;
use Darkwob\YoutubeMp3Converter\Converter\Options\ConverterOptions;
use Darkwob\YoutubeMp3Converter\Progress\FileProgress;

// Initialize progress tracker
$progress = new FileProgress(__DIR__ . '/progress');

// Configure conversion options
$options = new ConverterOptions();
$options->setAudioFormat('mp3')->setAudioQuality(0); // Highest quality

// Initialize converter (binaries auto-detected or specify paths)
$converter = new YouTubeConverter(
    __DIR__ . '/downloads',     // Output directory
    __DIR__ . '/temp',          // Temporary directory
    $progress,                  // Progress tracker
    $options,                   // Converter options (optional)
    __DIR__ . '/bin'            // Binary path (optional, auto-detected if not provided)
);

// Convert a video
try {
    $result = $converter->processVideo('https://www.youtube.com/watch?v=VIDEO_ID');
    
    echo "Converted: " . $result->getTitle() . "\n";
    echo "File: " . $result->getOutputPath() . "\n";
    echo "Format: " . $result->getFormat() . "\n";
    echo "Size: " . round($result->getSize() / 1024 / 1024, 2) . " MB\n";
    echo "Duration: " . round($result->getDuration() / 60, 2) . " minutes\n";
    
} catch (ConverterException $e) {
    echo "Error: " . $e->getMessage();
}
```

### Advanced Configuration

```php
use Darkwob\YoutubeMp3Converter\Converter\Options\ConverterOptions;

$options = new ConverterOptions();
$options
    ->setAudioFormat('mp3')                    // mp3, wav, aac, m4a, opus, vorbis, flac
    ->setAudioQuality(0)                       // 0 (highest) to 9 (lowest) quality
    ->setPlaylistItems('1-10')                 // Process specific playlist items
    ->setDateAfter('20240101')                 // Videos after this date
    ->setDateBefore('20241231')                // Videos before this date
    ->setFileSizeLimit('100M')                 // Maximum file size
    ->setOutputTemplate('%(title)s.%(ext)s')   // Custom output template
    ->setProxy('socks5://127.0.0.1:1080')      // Proxy configuration
    ->setRateLimit(500)                        // Download speed limit (KB/s)
    ->enableThumbnail(true)                    // Embed thumbnail
    ->setMetadata([                            // Custom metadata
        'artist' => 'Artist Name',
        'album' => 'Album Name'
    ]);

$converter = new YouTubeConverter($outputDir, $tempDir, $progress, $options);
```

### Remote Conversion

```php
use Darkwob\YoutubeMp3Converter\Converter\Remote\RemoteConverter;

$remote = new RemoteConverter(
    'https://api.converter.com',
    'your-api-token'
);

// Async conversion
$jobId = $remote->startConversion($url, $options);

// Check progress
$status = $remote->getProgress($jobId);

// Download when ready
if ($status['status'] === 'completed') {
    $remote->downloadFile($jobId, 'output.mp3');
}
```

### Progress Tracking with Redis

```php
use Darkwob\YoutubeMp3Converter\Progress\RedisProgress;

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$progress = new RedisProgress($redis, 'converter:', 3600);

// Track progress
$progress->update('video123', 'downloading', 50, 'Downloading video...');

// Get progress
$status = $progress->get('video123');
echo "Progress: {$status['progress']}%\n";
echo "Status: {$status['status']}\n";
echo "Message: {$status['message']}\n";
```

## 🔧 API Reference

### YouTubeConverter Class

Main class for video conversion operations.

#### Methods

- `processVideo(string $url): ConversionResult` - Convert single video
- `getVideoInfo(string $url): array` - Get video metadata
- `downloadVideo(string $url, string $id): string` - Download video file (internal)

### ConversionResult Class

Readonly result object returned by `processVideo()`:

- `getOutputPath(): string` - Full path to converted file
- `getTitle(): string` - Video title
- `getVideoId(): string` - Internal process ID
- `getFormat(): string` - Audio format (mp3, aac, etc.)
- `getSize(): int` - File size in bytes
- `getDuration(): float` - Duration in seconds
- `toArray(): array` - Convert to array

### ConverterOptions Class

Configuration options for the converter.

#### Methods

- `setAudioFormat(string $format): self` - Set output audio format
- `setAudioQuality(int $quality): self` - Set audio quality (0-9)
- `setPlaylistItems(string $items): self` - Set playlist items to process
- `setDateAfter(string $date): self` - Set start date filter (YYYYMMDD)
- `setDateBefore(string $date): self` - Set end date filter (YYYYMMDD)
- `setFileSizeLimit(string $limit): self` - Set maximum file size
- `setOutputTemplate(string $template): self` - Set output filename template
- `setProxy(string $proxy): self` - Set proxy server
- `setRateLimit(int $limit): self` - Set download speed limit (KB/s)
- `enableThumbnail(bool $enable): self` - Enable thumbnail embedding
- `setMetadata(array $metadata): self` - Set audio metadata

### RemoteConverter Class

Handle remote conversion operations.

#### Methods

- `processVideo(string $url): ConversionResult` - Process video on remote server
- `getVideoInfo(string $url): array` - Get video info from remote server
- `downloadVideo(string $url, string $id): string` - Download from remote server

### Progress Tracking

Both `FileProgress` and `RedisProgress` implement `ProgressInterface`:

#### Methods

- `update(string $id, string $status, float $progress, string $message): void`
- `get(string $id): ?array`
- `remove(string $id): void`

## 🛠️ Error Handling

The package uses specific exception types for better error handling:

```php
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\{
    ConverterException,
    InvalidUrlException,
    BinaryNotFoundException,
    DirectoryException,
    ProcessException,
    NetworkException
};

try {
    $result = $converter->processVideo($url);
} catch (InvalidUrlException $e) {
    // Handle URL validation errors
    echo "Invalid YouTube URL: " . $e->getMessage();
} catch (BinaryNotFoundException $e) {
    // Handle missing binary errors with installation instructions
    echo "Missing software: " . $e->getMessage();
} catch (DirectoryException $e) {
    // Handle directory creation/permission errors
    echo "Directory error: " . $e->getMessage();
} catch (ProcessException $e) {
    // Handle binary execution errors
    echo "Process error: " . $e->getMessage();
} catch (NetworkException $e) {
    // Handle network/connection errors
    echo "Network error: " . $e->getMessage();
} catch (ConverterException $e) {
    // Handle general conversion errors
    echo "Conversion error: " . $e->getMessage();
}
```

## 🔒 Security

- Input validation and URL sanitization
- Safe file handling with proper permissions
- Proxy support for restricted networks
- Cross-platform path handling with Windows normalization
- Secure temporary file management with automatic cleanup
- Binary path validation and security checks
- Windows environment variable handling

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📚 Documentation

For more detailed documentation and examples, visit our [Wiki](https://github.com/darkwob/youtube-mp3-converter/wiki).

## ⚠️ Disclaimer

This package is for educational purposes only. Please respect YouTube's terms of service and copyright laws when using this package. 