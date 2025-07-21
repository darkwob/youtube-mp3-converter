<?php

declare(strict_types=1);

namespace Darkwob\YoutubeMp3Converter\Converter;

use Darkwob\YoutubeMp3Converter\Converter\Interfaces\ConverterInterface;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\InvalidUrlException;
use Darkwob\YoutubeMp3Converter\Progress\Interfaces\ProgressInterface;
use Darkwob\YoutubeMp3Converter\Converter\Options\ConverterOptions;
use Darkwob\YoutubeMp3Converter\Converter\ConversionResult;
use Darkwob\YoutubeMp3Converter\Converter\Util\DirectoryManager;
use Darkwob\YoutubeMp3Converter\Converter\Util\ProcessManager;

/**
 * YouTube to MP3 Converter with Cross-Platform Binary Management
 * 
 * Main converter class that orchestrates download and conversion flow using
 * DirectoryManager for folder management and ProcessManager for binary execution.
 * 
 * @package Darkwob\YoutubeMp3Converter
 * @requires PHP >=8.4
 */
class YouTubeConverter implements ConverterInterface
{
    private DirectoryManager $directoryManager;
    private ProcessManager $processManager;
    private ProgressInterface $progress;
    private ConverterOptions $options;

    /**
     * Constructor accepting paths and options
     * 
     * @param string $outputPath Directory for final MP3 files
     * @param string $tempPath Directory for temporary files during conversion
     * @param ProgressInterface $progress Progress tracking implementation
     * @param ConverterOptions|null $options Conversion options
     */
    public function __construct(
        string $outputPath,
        string $tempPath,
        ProgressInterface $progress,
        ?ConverterOptions $options = null
    ) {
        $this->directoryManager = new DirectoryManager($outputPath, $tempPath);
        $this->processManager = new ProcessManager($tempPath);
        $this->progress = $progress;
        $this->options = $options ?? new ConverterOptions();
        
        // Ensure directories exist
        $this->directoryManager->ensureDirectoriesExist();
    }

    /**
     * Process video orchestrating download and conversion flow
     * 
     * @param string $url YouTube video URL
     * @return ConversionResult Result of the conversion process
     * @throws InvalidUrlException If URL is invalid
     * @throws ConverterException If processing fails
     */
    public function processVideo(string $url): ConversionResult
    {
        // Validate URL first
        $this->validateUrl($url);
        
        // Extract video ID for tracking
        $videoId = $this->extractVideoId($url);
        $startTime = time();
        
        try {
            // Update progress - starting
            $this->trackProgress($videoId, 'starting', 0, 'Initializing conversion', [
                'start_time' => $startTime,
                'url' => $url
            ]);
            
            // Get video information
            $videoInfo = $this->getVideoInfo($url);
            
            // Update progress - downloading with video info
            $this->trackProgress($videoId, 'downloading', 10, 'Starting download', [
                'start_time' => $startTime,
                'video_title' => $videoInfo['title'],
                'duration' => $videoInfo['duration']
            ]);
            
            // Download video with progress callbacks
            $tempFile = $this->downloadVideo($url, $videoId, $startTime);
            
            // Update progress - converting
            $this->trackProgress($videoId, 'converting', 70, 'Starting conversion', [
                'start_time' => $startTime,
                'input_file' => basename($tempFile),
                'output_format' => $this->options->getAudioFormat()
            ]);
            
            // Convert to MP3 with progress callbacks
            $outputFile = $this->convertToMp3($tempFile, $videoInfo, $videoId, $startTime);
            
            // Cleanup temporary files
            $this->directoryManager->cleanupTempFiles();
            
            // Calculate final file size and duration
            $fileSize = filesize($outputFile);
            $fileSizeFormatted = $this->formatFileSize($fileSize);
            $durationFormatted = $this->formatDuration($videoInfo['duration']);
            
            // Update progress - completed
            $this->trackProgress($videoId, 'completed', 100, 'Conversion completed', [
                'start_time' => $startTime,
                'output_file' => $outputFile,
                'file_size' => $fileSizeFormatted,
                'duration' => $durationFormatted,
                'total_time' => time() - $startTime
            ]);

            return new ConversionResult(
                outputPath: $outputFile,
                title: $videoInfo['title'],
                videoId: $videoId,
                format: $this->options->getAudioFormat(),
                size: $fileSize,
                duration: $videoInfo['duration'],
                thumbnailUrl: $videoInfo['thumbnail'] ?? null,
                uploader: $videoInfo['uploader'] ?? null,
                uploadDate: $videoInfo['upload_date'] ?? null
            );

        } catch (ConverterException $e) {
            $this->trackProgress($videoId, 'error', 0, $e->getMessage(), [
                'start_time' => $startTime,
                'error_type' => get_class($e),
                'total_time' => time() - $startTime
            ]);
            throw $e;
        } catch (\Exception $e) {
            $this->trackProgress($videoId, 'error', 0, $e->getMessage(), [
                'start_time' => $startTime,
                'error_type' => get_class($e),
                'total_time' => time() - $startTime
            ]);
            throw ConverterException::processingFailed($e->getMessage());
        }
    }

    /**
     * Get video metadata using ProcessManager for video metadata
     * 
     * @param string $url YouTube video URL
     * @return array Video information including title, duration, etc.
     * @throws ConverterException If video info extraction fails
     */
    public function getVideoInfo(string $url): array
    {
        try {
            $videoInfo = $this->processManager->getVideoInfo($url);
            
            return [
                'title' => $videoInfo['title'] ?? 'Unknown',
                'duration' => (float)($videoInfo['duration'] ?? 0),
                'id' => $videoInfo['id'] ?? $this->extractVideoId($url),
                'uploader' => $videoInfo['uploader'] ?? 'Unknown',
                'thumbnail' => $videoInfo['thumbnail'] ?? null,
                'upload_date' => $videoInfo['upload_date'] ?? null,
                'view_count' => $videoInfo['view_count'] ?? null,
                'description' => $videoInfo['description'] ?? null
            ];
            
        } catch (\Exception $e) {
            throw ConverterException::videoInfoFailed($e->getMessage());
        }
    }

    /**
     * Download video with progress tracking integration
     * 
     * @param string $url YouTube video URL
     * @param string $videoId Video ID for tracking
     * @param int $startTime Process start time for ETA calculation
     * @return string Path to downloaded temporary file
     * @throws ConverterException If download fails
     */
    public function downloadVideo(string $url, string $videoId, int $startTime = null): string
    {
        $startTime = $startTime ?? time();
        
        try {
            // Create temp directory for this video
            $tempDir = $this->directoryManager->createTempDirectory("video_{$videoId}_");
            
            // Update progress - preparing download
            $this->trackProgress($videoId, 'downloading', 15, 'Preparing download', [
                'start_time' => $startTime,
                'temp_dir' => basename($tempDir)
            ]);
            
            // Define output template for downloaded file
            $outputTemplate = $tempDir . DIRECTORY_SEPARATOR . "%(title)s.%(ext)s";
            
            // Prepare yt-dlp arguments for video download
            $arguments = [
                $url,
                '--output', $outputTemplate,
                '--format', 'bestaudio/best',
                '--no-warnings',
                '--no-playlist',
                '--extract-audio',
                '--audio-format', $this->options->getAudioFormat(),
                '--newline' // For better progress parsing
            ];
            
            // Add quality settings if specified
            if ($this->options->getAudioQuality() > 0) {
                $arguments[] = '--audio-quality';
                $arguments[] = (string)$this->options->getAudioQuality();
            }
            
            // Create progress callback for download
            $progressCallback = function(string $output) use ($videoId, $startTime) {
                $this->parseDownloadProgress($output, $videoId, $startTime);
            };
            
            // Update progress - starting download
            $this->trackProgress($videoId, 'downloading', 20, 'Starting download', [
                'start_time' => $startTime
            ]);
            
            // Execute download using ProcessManager with progress callback
            $result = $this->processManager->executeYtDlpWithProgress($arguments, $tempDir, 1800, $progressCallback);
            
            if (!$result->isSuccessful()) {
                throw ConverterException::downloadFailed($url, $result->getErrorOutput());
            }
            
            // Update progress - download completed
            $this->trackProgress($videoId, 'downloading', 65, 'Download completed', [
                'start_time' => $startTime
            ]);
            
            // Find the downloaded file
            $downloadedFiles = glob($tempDir . DIRECTORY_SEPARATOR . "*." . $this->options->getAudioFormat());
            
            if (empty($downloadedFiles)) {
                throw ConverterException::processingFailed('Downloaded file not found after successful download');
            }
            
            $downloadedFile = $downloadedFiles[0];
            $fileSize = filesize($downloadedFile);
            
            // Update progress - file ready
            $this->trackProgress($videoId, 'downloading', 70, 'File ready for conversion', [
                'start_time' => $startTime,
                'downloaded_file' => basename($downloadedFile),
                'file_size' => $this->formatFileSize($fileSize)
            ]);
            
            return $downloadedFile;
            
        } catch (\Exception $e) {
            throw ConverterException::downloadFailed($url, $e->getMessage());
        }
    }

    /**
     * Convert downloaded file to MP3 using ffmpeg through ProcessManager
     * 
     * @param string $inputFile Path to input file
     * @param array $videoInfo Video information
     * @param string $videoId Video ID for progress tracking
     * @param int $startTime Process start time for ETA calculation
     * @return string Path to output MP3 file
     * @throws ConverterException If conversion fails
     */
    private function convertToMp3(string $inputFile, array $videoInfo, string $videoId, int $startTime): string
    {
        try {
            // Sanitize filename for output
            $safeTitle = $this->sanitizeFilename($videoInfo['title']);
            $outputFile = $this->directoryManager->getOutputPath() . DIRECTORY_SEPARATOR . 
                         "{$safeTitle}.{$this->options->getAudioFormat()}";
            
            // Update progress - preparing conversion
            $this->trackProgress($videoId, 'converting', 75, 'Preparing conversion', [
                'start_time' => $startTime,
                'input_file' => basename($inputFile),
                'output_file' => basename($outputFile)
            ]);
            
            // If file is already in correct format and yt-dlp handled conversion, just move it
            if (pathinfo($inputFile, PATHINFO_EXTENSION) === $this->options->getAudioFormat()) {
                $this->trackProgress($videoId, 'converting', 90, 'File already in correct format, copying', [
                    'start_time' => $startTime
                ]);
                
                if (copy($inputFile, $outputFile)) {
                    return $outputFile;
                }
            }
            
            // Prepare ffmpeg arguments for conversion
            $arguments = [
                '-i', $inputFile,
                '-vn', // No video
                '-acodec', $this->getAudioCodec(),
                '-y', // Overwrite output files
                '-progress', 'pipe:1' // Enable progress output
            ];
            
            // Add quality settings if specified
            if ($this->options->getAudioQuality() > 0) {
                $arguments[] = '-b:a';
                $arguments[] = $this->getAudioBitrate();
            }
            
            // Add output file
            $arguments[] = $outputFile;
            
            // Create progress callback for conversion
            $progressCallback = function(string $output) use ($videoId, $startTime, $videoInfo) {
                $this->parseConversionProgress($output, $videoId, $startTime, $videoInfo);
            };
            
            // Update progress - starting conversion
            $this->trackProgress($videoId, 'converting', 80, 'Starting conversion', [
                'start_time' => $startTime,
                'codec' => $this->getAudioCodec(),
                'bitrate' => $this->getAudioBitrate()
            ]);
            
            // Execute conversion using ProcessManager with progress callback
            $result = $this->processManager->executeFfmpegWithProgress($arguments, null, 1800, $progressCallback);
            
            if (!$result->isSuccessful()) {
                throw ConverterException::conversionFailed($inputFile, $result->getErrorOutput());
            }
            
            if (!file_exists($outputFile)) {
                throw ConverterException::processingFailed('Converted file not found after successful conversion');
            }
            
            // Update progress - conversion completed
            $fileSize = filesize($outputFile);
            $this->trackProgress($videoId, 'converting', 95, 'Conversion completed', [
                'start_time' => $startTime,
                'output_file' => basename($outputFile),
                'file_size' => $this->formatFileSize($fileSize)
            ]);
            
            return $outputFile;
            
        } catch (\Exception $e) {
            throw ConverterException::conversionFailed($inputFile, $e->getMessage());
        }
    }

    /**
     * Extract video ID from YouTube URL with pattern matching
     * 
     * @param string $url YouTube URL
     * @return string Video ID
     * @throws InvalidUrlException If video ID cannot be extracted
     */
    public function extractVideoId(string $url): string
    {
        // YouTube URL patterns
        $patterns = [
            // Standard youtube.com URLs
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
            // YouTube embed URLs
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
            // YouTube mobile URLs
            '/m\.youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
            // YouTube playlist URLs with video
            '/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/',
            // YouTube shorts URLs
            '/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        
        throw InvalidUrlException::invalidYouTubeUrl($url);
    }

    /**
     * Validate URL with comprehensive URL validation
     * 
     * @param string $url URL to validate
     * @throws InvalidUrlException If URL is invalid
     */
    public function validateUrl(string $url): void
    {
        // Check if URL is empty
        if (empty(trim($url))) {
            throw InvalidUrlException::emptyUrl();
        }
        
        // Basic URL format validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw InvalidUrlException::malformedUrl($url);
        }
        
        // Check protocol
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['scheme']) || !in_array($parsedUrl['scheme'], ['http', 'https'])) {
            throw InvalidUrlException::invalidProtocol($url);
        }
        
        // Check if it's a YouTube URL
        $host = $parsedUrl['host'] ?? '';
        $validHosts = [
            'youtube.com',
            'www.youtube.com',
            'm.youtube.com',
            'youtu.be',
            'www.youtu.be'
        ];
        
        if (!in_array($host, $validHosts)) {
            throw InvalidUrlException::unsupportedPlatform($url);
        }
        
        // Try to extract video ID to ensure it's a valid video URL
        try {
            $this->extractVideoId($url);
        } catch (InvalidUrlException $e) {
            throw $e;
        }
    }

    /**
     * Track progress with stage updates and validation
     * 
     * @param string $videoId Video ID for tracking
     * @param string $stage Current stage
     * @param int $percentage Progress percentage (0-100)
     * @param string $message Status message
     * @param array $additionalData Additional progress data
     */
    private function trackProgress(string $videoId, string $stage, int $percentage, string $message, array $additionalData = []): void
    {
        // Validate progress data structure
        $validatedData = $this->validateProgressData($videoId, $stage, $percentage, $message, $additionalData);
        
        // Format progress data
        $formattedData = $this->formatProgressData($validatedData);
        
        // Update progress with formatted data
        $this->progress->update(
            $validatedData['id'],
            $validatedData['stage'],
            $validatedData['percentage'],
            $formattedData['message']
        );
    }

    /**
     * Validate progress data structure
     * 
     * @param string $videoId Video ID
     * @param string $stage Current stage
     * @param int $percentage Progress percentage
     * @param string $message Status message
     * @param array $additionalData Additional data
     * @return array Validated progress data
     * @throws ConverterException If validation fails
     */
    private function validateProgressData(string $videoId, string $stage, int $percentage, string $message, array $additionalData = []): array
    {
        // Validate video ID
        if (empty(trim($videoId))) {
            throw ConverterException::invalidConfiguration('Video ID cannot be empty for progress tracking');
        }
        
        // Validate stage
        $validStages = ['starting', 'downloading', 'converting', 'completed', 'error', 'cancelled'];
        if (!in_array($stage, $validStages)) {
            throw ConverterException::invalidConfiguration("Invalid progress stage: {$stage}. Valid stages: " . implode(', ', $validStages));
        }
        
        // Validate percentage
        if ($percentage < 0 || $percentage > 100) {
            throw ConverterException::invalidConfiguration("Progress percentage must be between 0-100, got: {$percentage}");
        }
        
        // Validate message
        if (empty(trim($message))) {
            $message = ucfirst($stage);
        }
        
        return [
            'id' => trim($videoId),
            'stage' => $stage,
            'percentage' => max(0, min(100, (int)$percentage)),
            'message' => trim($message),
            'additional_data' => $additionalData,
            'timestamp' => time()
        ];
    }

    /**
     * Format progress data for consistent display
     * 
     * @param array $progressData Validated progress data
     * @return array Formatted progress data
     */
    private function formatProgressData(array $progressData): array
    {
        $stage = $progressData['stage'];
        $percentage = $progressData['percentage'];
        $message = $progressData['message'];
        $additionalData = $progressData['additional_data'];
        
        // Format message based on stage and additional data
        $formattedMessage = match ($stage) {
            'starting' => "Initializing conversion for video {$progressData['id']}",
            'downloading' => $this->formatDownloadMessage($message, $percentage, $additionalData),
            'converting' => $this->formatConversionMessage($message, $percentage, $additionalData),
            'completed' => $this->formatCompletionMessage($message, $additionalData),
            'error' => "Error: {$message}",
            'cancelled' => "Conversion cancelled: {$message}",
            default => $message
        };
        
        return [
            'message' => $formattedMessage,
            'stage_display' => ucfirst(str_replace('_', ' ', $stage)),
            'progress_bar' => $this->generateProgressBar($percentage),
            'eta' => $this->calculateETA($stage, $percentage, $additionalData)
        ];
    }

    /**
     * Format download progress message
     */
    private function formatDownloadMessage(string $message, int $percentage, array $additionalData): string
    {
        $baseMessage = "Downloading video ({$percentage}%)";
        
        if (isset($additionalData['download_speed'])) {
            $baseMessage .= " - Speed: {$additionalData['download_speed']}";
        }
        
        if (isset($additionalData['file_size'])) {
            $baseMessage .= " - Size: {$additionalData['file_size']}";
        }
        
        if (isset($additionalData['eta'])) {
            $baseMessage .= " - ETA: {$additionalData['eta']}";
        }
        
        return $baseMessage;
    }

    /**
     * Format conversion progress message
     */
    private function formatConversionMessage(string $message, int $percentage, array $additionalData): string
    {
        $baseMessage = "Converting to {$this->options->getAudioFormat()} ({$percentage}%)";
        
        if (isset($additionalData['current_time'])) {
            $baseMessage .= " - Time: {$additionalData['current_time']}";
        }
        
        if (isset($additionalData['bitrate'])) {
            $baseMessage .= " - Bitrate: {$additionalData['bitrate']}";
        }
        
        return $baseMessage;
    }

    /**
     * Format completion message
     */
    private function formatCompletionMessage(string $message, array $additionalData): string
    {
        $baseMessage = "Conversion completed successfully";
        
        if (isset($additionalData['output_file'])) {
            $filename = basename($additionalData['output_file']);
            $baseMessage .= " - File: {$filename}";
        }
        
        if (isset($additionalData['file_size'])) {
            $baseMessage .= " - Size: {$additionalData['file_size']}";
        }
        
        if (isset($additionalData['duration'])) {
            $baseMessage .= " - Duration: {$additionalData['duration']}";
        }
        
        return $baseMessage;
    }

    /**
     * Generate ASCII progress bar
     */
    private function generateProgressBar(int $percentage, int $width = 20): string
    {
        $filled = (int)($percentage / 100 * $width);
        $empty = $width - $filled;
        
        return '[' . str_repeat('=', $filled) . str_repeat('-', $empty) . ']';
    }

    /**
     * Calculate estimated time of arrival
     */
    private function calculateETA(string $stage, int $percentage, array $additionalData): ?string
    {
        if ($percentage <= 0 || $percentage >= 100) {
            return null;
        }
        
        if (isset($additionalData['start_time'])) {
            $elapsed = time() - $additionalData['start_time'];
            $remaining = ($elapsed / $percentage) * (100 - $percentage);
            
            if ($remaining > 60) {
                return sprintf('%d min %d sec', $remaining / 60, $remaining % 60);
            } else {
                return sprintf('%d sec', $remaining);
            }
        }
        
        return null;
    }

    /**
     * Get audio codec for ffmpeg based on format
     */
    private function getAudioCodec(): string
    {
        return match ($this->options->getAudioFormat()) {
            'mp3' => 'libmp3lame',
            'aac' => 'aac',
            'ogg' => 'libvorbis',
            'wav' => 'pcm_s16le',
            'm4a' => 'aac',
            'flac' => 'flac',
            default => 'libmp3lame'
        };
    }

    /**
     * Get audio bitrate based on quality setting
     */
    private function getAudioBitrate(): string
    {
        $quality = $this->options->getAudioQuality();
        
        // Convert quality scale (0-9) to bitrate
        return match (true) {
            $quality >= 9 => '320k',
            $quality >= 7 => '256k',
            $quality >= 5 => '192k',
            $quality >= 3 => '128k',
            default => '96k'
        };
    }

    /**
     * Parse download progress from yt-dlp output
     */
    private function parseDownloadProgress(string $output, string $videoId, int $startTime): void
    {
        $lines = explode("\n", $output);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Parse yt-dlp progress line: [download]  45.2% of 3.45MiB at 1.23MiB/s ETA 00:02
            if (preg_match('/\[download\]\s+(\d+\.?\d*)%\s+of\s+([^\s]+)\s+at\s+([^\s]+)\s+ETA\s+([^\s]+)/', $line, $matches)) {
                $percentage = (float)$matches[1];
                $totalSize = $matches[2];
                $speed = $matches[3];
                $eta = $matches[4];
                
                // Map download percentage to our progress range (20-65%)
                $mappedPercentage = 20 + ($percentage * 0.45);
                
                $this->trackProgress($videoId, 'downloading', (int)$mappedPercentage, 'Downloading', [
                    'start_time' => $startTime,
                    'download_speed' => $speed,
                    'file_size' => $totalSize,
                    'eta' => $eta
                ]);
                break;
            }
            
            // Parse simple percentage: [download] 45.2%
            elseif (preg_match('/\[download\]\s+(\d+\.?\d*)%/', $line, $matches)) {
                $percentage = (float)$matches[1];
                $mappedPercentage = 20 + ($percentage * 0.45);
                
                $this->trackProgress($videoId, 'downloading', (int)$mappedPercentage, 'Downloading', [
                    'start_time' => $startTime
                ]);
                break;
            }
        }
    }

    /**
     * Parse conversion progress from ffmpeg output
     */
    private function parseConversionProgress(string $output, string $videoId, int $startTime, array $videoInfo): void
    {
        $lines = explode("\n", $output);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Parse ffmpeg progress: out_time_ms=123456789
            if (preg_match('/out_time_ms=(\d+)/', $line, $matches)) {
                $currentTimeMs = (int)$matches[1];
                $currentTime = $currentTimeMs / 1000000; // Convert to seconds
                
                $totalDuration = $videoInfo['duration'] ?? 0;
                if ($totalDuration > 0) {
                    $percentage = min(95, ($currentTime / $totalDuration) * 100);
                    // Map to our conversion range (80-95%)
                    $mappedPercentage = 80 + ($percentage * 0.15);
                    
                    $this->trackProgress($videoId, 'converting', (int)$mappedPercentage, 'Converting', [
                        'start_time' => $startTime,
                        'current_time' => $this->formatDuration($currentTime),
                        'total_time' => $this->formatDuration($totalDuration)
                    ]);
                }
                break;
            }
            
            // Parse ffmpeg time progress: time=00:01:23.45
            elseif (preg_match('/time=(\d{2}):(\d{2}):(\d{2})\.(\d{2})/', $line, $matches)) {
                $hours = (int)$matches[1];
                $minutes = (int)$matches[2];
                $seconds = (int)$matches[3];
                $currentTime = $hours * 3600 + $minutes * 60 + $seconds;
                
                $totalDuration = $videoInfo['duration'] ?? 0;
                if ($totalDuration > 0) {
                    $percentage = min(95, ($currentTime / $totalDuration) * 100);
                    $mappedPercentage = 80 + ($percentage * 0.15);
                    
                    $this->trackProgress($videoId, 'converting', (int)$mappedPercentage, 'Converting', [
                        'start_time' => $startTime,
                        'current_time' => sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds)
                    ]);
                }
                break;
            }
        }
    }

    /**
     * Format file size in human readable format
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        $size = $bytes;
        
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        
        return sprintf('%.1f %s', $size, $units[$unitIndex]);
    }

    /**
     * Format duration in human readable format
     */
    private function formatDuration(float $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        } else {
            return sprintf('%02d:%02d', $minutes, $seconds);
        }
    }

    /**
     * Sanitize filename for safe file system usage
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove or replace invalid filename characters
        $invalidChars = ['<', '>', ':', '"', '/', '\\', '|', '?', '*'];
        $filename = str_replace($invalidChars, '_', $filename);
        
        // Remove multiple consecutive underscores
        $filename = preg_replace('/_+/', '_', $filename);
        
        // Trim whitespace and underscores
        $filename = trim($filename, " \t\n\r\0\x0B_");
        
        // Limit length (leave room for extension)
        if (strlen($filename) > 200) {
            $filename = substr($filename, 0, 200);
        }
        
        // Ensure filename is not empty
        if (empty($filename)) {
            $filename = 'untitled';
        }
        
        return $filename;
    }
}