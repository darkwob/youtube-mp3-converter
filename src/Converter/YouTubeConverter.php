<?php

declare(strict_types=1);

namespace Darkwob\YoutubeMp3Converter\Converter;

use Darkwob\YoutubeMp3Converter\Converter\Interfaces\ConverterInterface;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;
use Darkwob\YoutubeMp3Converter\Progress\Interfaces\ProgressInterface;
use Darkwob\YoutubeMp3Converter\Converter\Options\ConverterOptions;
use Darkwob\YoutubeMp3Converter\Converter\ConversionResult;
use Darkwob\YoutubeMp3Converter\Converter\Util\PlatformDetector;
use Symfony\Component\Process\Process;
use GuzzleHttp\Client;

/**
 * YouTube to MP3 Converter with Cross-Platform Binary Management
 * 
 * Automatically detects platform and uses appropriate binaries from project's bin/ directory.
 * 
 * @package Darkwob\YoutubeMp3Converter
 * @requires PHP >=8.4
 */
class YouTubeConverter implements ConverterInterface
{
    private string $outputPath;
    private string $tempPath;
    private ProgressInterface $progress;
    private ?ConverterOptions $options;
    private \GuzzleHttp\Client $httpClient;

    /**
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
        $this->validatePaths($outputPath, $tempPath);
        
        $this->outputPath = PlatformDetector::normalizePath(rtrim($outputPath, '/\\'));
        $this->tempPath = PlatformDetector::normalizePath(rtrim($tempPath, '/\\'));
        $this->progress = $progress;
        $this->options = $options ?? new ConverterOptions();
        
        // Initialize HTTP client
        $this->httpClient = new \GuzzleHttp\Client([
            'timeout' => 30,
            'verify' => true
        ]);

        $this->checkDependencies();
    }

    public function processVideo(string $url): ConversionResult
    {
        try {
            // Validate URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw ConverterException::invalidUrl($url);
            }

            // Generate unique ID
            $id = uniqid('video_', true);
            
            // Update progress
            $this->progress->update($id, 'starting', 0, 'Initializing conversion');
            
            // Get video info
            $info = $this->getVideoInfo($url);
            
            // Update progress
            $this->progress->update($id, 'downloading', 25, 'Downloading video');
            
            // Download video
            $outputFile = $this->downloadVideo($url, $id);
            
            // Update progress
            $this->progress->update($id, 'converting', 75, 'Converting to audio');
            
            // Process the video
            $result = $this->convertToAudio($outputFile, $info);
            
            // Cleanup temporary files
            $this->cleanup($id);
            
            // Update final progress
            $this->progress->update($id, 'completed', 100, 'Conversion completed');

            return new ConversionResult(
                outputPath: $result['file'],
                title: $result['title'],
                videoId: $id,
                format: $result['format'],
                size: $result['size'],
                duration: $result['duration']
            );

        } catch (ConverterException $e) {
            if (isset($id)) {
                $this->progress->update($id, 'error', 0, $e->getMessage());
            }
            throw $e;
        } catch (\Exception $e) {
            if (isset($id)) {
                $this->progress->update($id, 'error', 0, $e->getMessage());
            }
            throw ConverterException::processingFailed($e->getMessage());
        }
    }

    /**
     * Get yt-dlp binary path using platform detection
     */
    private function getYtDlpPath(): string
    {
        try {
            return PlatformDetector::getExecutablePath('yt-dlp');
        } catch (\RuntimeException $e) {
            throw ConverterException::missingDependency(
                'yt-dlp binary not found. ' . $e->getMessage()
            );
        }
    }

    /**
     * Get ffmpeg binary path using platform detection
     */
    private function getFfmpegPath(): string
    {
        try {
            return PlatformDetector::getExecutablePath('ffmpeg');
        } catch (\RuntimeException $e) {
            throw ConverterException::missingDependency(
                'ffmpeg binary not found. ' . $e->getMessage()
            );
        }
    }

    private function checkDependencies(): void
    {
        // Create bin directory if needed
        PlatformDetector::createBinDirectory();
        
        // Check required binaries
        $requirements = PlatformDetector::checkRequirements(['yt-dlp', 'ffmpeg']);
        
        $missing = [];
        foreach ($requirements as $binary => $info) {
            if (!$info['exists']) {
                $missing[] = $binary;
            }
        }
        
        if (!empty($missing)) {
            $message = "Missing required binaries: " . implode(', ', $missing) . "\n\n";
            $message .= "Installation instructions:\n";
            
            foreach ($missing as $binary) {
                $message .= "\n" . $requirements[$binary]['instructions'] . "\n";
            }
            
            throw ConverterException::missingDependency($message);
        }
    }

    /**
     * Validate output and temp paths
     */
    private function validatePaths(string $outputPath, string $tempPath): void
    {
        if (!is_dir($outputPath)) {
            throw ConverterException::invalidPath("Output directory does not exist: {$outputPath}");
        }

        if (!is_writable($outputPath)) {
            throw ConverterException::invalidPath("Output directory is not writable: {$outputPath}");
        }

        if (!is_dir($tempPath)) {
            throw ConverterException::invalidPath("Temp directory does not exist: {$tempPath}");
        }

        if (!is_writable($tempPath)) {
            throw ConverterException::invalidPath("Temp directory is not writable: {$tempPath}");
        }
    }

    public function getVideoInfo(string $url): array
    {
        try {
            $ytDlpPath = $this->getYtDlpPath();
            
            $command = PlatformDetector::createCommand('yt-dlp', [
                '--dump-json',
                '--no-warnings',
                $url
            ]);

            $process = new \Symfony\Component\Process\Process($command);
            $process->setTimeout(60);
            $process->run();

            if (!$process->isSuccessful()) {
                throw ConverterException::processingFailed(
                    'Failed to get video info: ' . $process->getErrorOutput()
                );
            }

            $jsonOutput = $process->getOutput();
            $videoInfo = json_decode($jsonOutput, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw ConverterException::processingFailed('Invalid JSON response from yt-dlp');
            }

            return [
                'title' => $videoInfo['title'] ?? 'Unknown',
                'duration' => $videoInfo['duration'] ?? 0,
                'id' => $videoInfo['id'] ?? uniqid(),
                'uploader' => $videoInfo['uploader'] ?? 'Unknown'
            ];

        } catch (\Exception $e) {
            throw ConverterException::processingFailed('Failed to get video info: ' . $e->getMessage());
        }
    }

    public function downloadVideo(string $url, string $id): string
    {
        try {
            $tempFile = PlatformDetector::normalizePath(
                $this->tempPath . DIRECTORY_SEPARATOR . "temp_{$id}.%(ext)s"
            );

            $command = PlatformDetector::createCommand('yt-dlp', array_filter([
                $url,
                '--output', $tempFile,
                '--extract-audio',
                '--audio-format', $this->options->getAudioFormat(),
                $this->options->getAudioQuality() > 0 ? '--audio-quality' : null,
                $this->options->getAudioQuality() > 0 ? (string)$this->options->getAudioQuality() : null,
                '--no-warnings',
                '--no-playlist'
            ]));

            $process = new Process($command);
            $process->setTimeout(1800); // 30 minutes
            $process->run();

            if (!$process->isSuccessful()) {
                throw ConverterException::processingFailed(
                    'Failed to download video: ' . $process->getErrorOutput()
                );
            }

            // Find the downloaded file
            $pattern = str_replace('%(ext)s', $this->options->getAudioFormat(), $tempFile);
            $downloadedFile = glob($pattern);

            if (empty($downloadedFile)) {
                throw ConverterException::processingFailed('Downloaded file not found');
            }

            return $downloadedFile[0];

        } catch (\Exception $e) {
            throw ConverterException::processingFailed('Failed to download video: ' . $e->getMessage());
        }
    }

    private function convertToAudio(string $inputFile, array $info): array
    {
        try {
            $safeTitle = $this->sanitizeFilename($info['title']);
            $outputFile = PlatformDetector::normalizePath(
                $this->outputPath . DIRECTORY_SEPARATOR . "{$safeTitle}.{$this->options->getAudioFormat()}"
            );

            // If the downloaded file is already in the correct format and location, just move it
            if (pathinfo($inputFile, PATHINFO_EXTENSION) === $this->options->getAudioFormat()) {
                if (rename($inputFile, $outputFile)) {
                    return [
                        'file' => $outputFile,
                        'title' => $info['title'],
                        'format' => $this->options->getAudioFormat(),
                        'size' => filesize($outputFile),
                        'duration' => $info['duration']
                    ];
                }
            }

            // Use ffmpeg for conversion if needed
            $ffmpegPath = $this->getFfmpegPath();
            
            $command = PlatformDetector::createCommand('ffmpeg', array_filter([
                '-i', $inputFile,
                '-vn', // No video
                '-acodec', $this->getAudioCodec(),
                $this->options->getAudioQuality() > 0 ? '-b:a' : null,
                $this->options->getAudioQuality() > 0 ? $this->getAudioBitrate() : null,
                '-y', // Overwrite output files
                $outputFile
            ]));

            $process = new Process($command);
            $process->setTimeout(1800); // 30 minutes
            $process->run();

            if (!$process->isSuccessful()) {
                throw ConverterException::processingFailed(
                    'Failed to convert audio: ' . $process->getErrorOutput()
                );
            }

            if (!file_exists($outputFile)) {
                throw ConverterException::processingFailed('Converted file not found');
            }

            return [
                'file' => $outputFile,
                'title' => $info['title'],
                'format' => $this->options->getAudioFormat(),
                'size' => filesize($outputFile),
                'duration' => $info['duration']
            ];

        } catch (\Exception $e) {
            throw ConverterException::processingFailed('Failed to convert audio: ' . $e->getMessage());
        }
    }

    private function getAudioCodec(): string
    {
        return match ($this->options->getAudioFormat()) {
            'mp3' => 'libmp3lame',
            'aac' => 'aac',
            'ogg' => 'libvorbis',
            'wav' => 'pcm_s16le',
            default => 'libmp3lame'
        };
    }

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

    private function sanitizeFilename(string $filename): string
    {
        // Remove or replace invalid filename characters
        $invalidChars = ['<', '>', ':', '"', '/', '\\', '|', '?', '*'];
        $filename = str_replace($invalidChars, '_', $filename);
        
        // Limit length
        if (strlen($filename) > 200) {
            $filename = substr($filename, 0, 200);
        }
        
        return trim($filename);
    }

    private function cleanup(string $id): void
    {
        try {
            $pattern = $this->tempPath . DIRECTORY_SEPARATOR . "temp_{$id}.*";
            $files = glob($pattern);
            
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        } catch (\Exception $e) {
            // Cleanup errors are not critical, just log them
            error_log("Cleanup failed: " . $e->getMessage());
        }
    }
} 