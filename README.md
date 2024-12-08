# 🎵 YouTube to MP3 Converter

![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-blue)
![Status](https://img.shields.io/badge/Status-Stable-green)
![License](https://img.shields.io/badge/License-MIT-green)

A powerful and feature-rich YouTube to MP3 converter library that supports both YouTube and YouTube Music, including playlist functionality, remote conversion, and extensive customization options.

## ✨ Key Features

- 🎵 Convert YouTube videos to multiple audio formats
- 📑 Full playlist support with customizable filters
- 🎧 YouTube Music support
- 📊 Real-time progress tracking (File-based or Redis)
- 🌐 Remote server conversion support
- 🔒 Token-based security
- 🎯 Clean and modern API
- 🔄 Automatic file cleanup
- 🛠️ Extensive configuration options
- 🚀 Asynchronous processing support

## 🚀 Installation

```bash
composer require darkwob/youtube-mp3-converter
```

### Requirements

- PHP >= 7.4
- JSON extension
- FFmpeg (optional, for advanced audio processing)
- Redis (optional, for Redis-based progress tracking)

## 💻 Basic Usage

### Simple Video Conversion

```php
use Darkwob\YoutubeMp3Converter\Converter\YouTubeConverter;
use Darkwob\YoutubeMp3Converter\Progress\FileProgress;

// Initialize progress tracker
$progress = new FileProgress(__DIR__ . '/progress');

// Initialize converter
$converter = new YouTubeConverter(
    __DIR__ . '/bin',           // Binary path (yt-dlp, ffmpeg)
    __DIR__ . '/downloads',     // Output directory
    __DIR__ . '/temp',         // Temporary directory
    $progress                   // Progress tracker
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

### Advanced Configuration

```php
use Darkwob\YoutubeMp3Converter\Converter\Options\ConverterOptions;

$options = new ConverterOptions();
$options
    ->setAudioFormat('mp3')                    // mp3, wav, aac, m4a, opus, vorbis, flac
    ->setAudioQuality(0)                       // 0 (best) to 9 (worst)
    ->setVideoFormat('bestaudio/best')         // Video format selection
    ->enableSponsorBlock()                     // Skip sponsored segments
    ->setPlaylistItems('1-10')                 // Process specific items
    ->setDateFilter('20220101', '20231231')    // Date range filter
    ->setFileSizeLimit('100M')                 // Maximum file size
    ->setOutputTemplate('%(title)s.%(ext)s')   // Custom output template
    ->setProxy('socks5://127.0.0.1:1080')      // Proxy configuration
    ->setRateLimit(3)                          // Downloads per minute
    ->enableThumbnail()                        // Embed thumbnail
    ->setMetadata([                            // Custom metadata
        'artist' => '%(uploader)s',
        'title' => '%(title)s'
    ]);

$converter = new YouTubeConverter($binPath, $outputDir, $tempDir, $progress, $options);
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

- `processVideo(string $url): array` - Process a single video or playlist
- `getVideoInfo(string $url): array` - Get video metadata
- `downloadVideo(string $url, string $id): string` - Download video file

### ConverterOptions Class

Configuration options for the converter.

#### Methods

- `setAudioFormat(string $format): self` - Set output audio format
- `setAudioQuality(int $quality): self` - Set audio quality (0-9)
- `setVideoFormat(string $format): self` - Set video format selection
- `enableSponsorBlock(): self` - Enable SponsorBlock integration
- `setPlaylistItems(string $items): self` - Set playlist items to process
- `setDateFilter(string $start, string $end): self` - Set date range filter
- `setFileSizeLimit(string $limit): self` - Set maximum file size
- `setOutputTemplate(string $template): self` - Set output filename template
- `setProxy(string $proxy): self` - Set proxy server
- `setRateLimit(int $limit): self` - Set rate limit
- `enableThumbnail(): self` - Enable thumbnail embedding
- `setMetadata(array $metadata): self` - Set audio metadata

### RemoteConverter Class

Handle remote conversion operations.

#### Methods

- `startConversion(string $url, ConverterOptions $options): string` - Start remote conversion
- `getProgress(string $jobId): array` - Get conversion progress
- `downloadFile(string $jobId, string $output): bool` - Download converted file

### Progress Tracking

Both `FileProgress` and `RedisProgress` implement `ProgressInterface`:

#### Methods

- `update(string $id, string $status, float $progress, string $message): void`
- `get(string $id): ?array`
- `delete(string $id): void`
- `getAll(): array`
- `cleanup(int $maxAge = 3600): void`

## 🛠️ Error Handling

The package uses custom exceptions for different error scenarios:

```php
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;

try {
    $result = $converter->processVideo($url);
} catch (ConverterException $e) {
    switch (true) {
        case $e instanceof ConverterException:
            // Handle conversion errors
            break;
        // Handle other specific exceptions
    }
}
```

## 🔒 Security

- Token-based authentication for remote conversion
- Rate limiting support
- Proxy support for restricted networks
- Input validation and sanitization
- Secure file handling

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📚 Documentation

For more detailed documentation and examples, visit our [Wiki](https://github.com/darkwob/youtube-mp3-converter/wiki).

## ⚠️ Disclaimer

This package is for educational purposes only. Please respect YouTube's terms of service and copyright laws when using this package. 