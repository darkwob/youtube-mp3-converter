<?php

namespace Darkwob\YoutubeMp3Converter\Converter;

use Darkwob\YoutubeMp3Converter\Converter\Interfaces\ConverterInterface;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;
use Darkwob\YoutubeMp3Converter\Progress\Interfaces\ProgressInterface;
use Darkwob\YoutubeMp3Converter\Converter\Options\ConverterOptions;

/**
 * YouTube to MP3 Converter
 * 
 * @package Darkwob\YoutubeMp3Converter
 */
class YouTubeConverter implements ConverterInterface
{
    private string $binPath;
    private string $outputPath;
    private string $tempPath;
    private ProgressInterface $progress;
    private ?ConverterOptions $options;

    public function __construct(
        string $binPath,
        string $outputPath,
        string $tempPath,
        ProgressInterface $progress,
        ?ConverterOptions $options = null
    ) {
        $this->validatePaths($binPath, $outputPath, $tempPath);
        
        $this->binPath = rtrim($binPath, '/');
        $this->outputPath = rtrim($outputPath, '/');
        $this->tempPath = rtrim($tempPath, '/');
        $this->progress = $progress;
        $this->options = $options ?? new ConverterOptions();

        $this->checkDependencies();
    }

    public function processVideo(string $url): array
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

            return [
                'success' => true,
                'id' => $id,
                'results' => $result
            ];

        } catch (ConverterException $e) {
            if (isset($id)) {
                $this->progress->update($id, 'error', 0, $e->getMessage());
                $this->cleanup($id);
            }
            throw $e;
        }
    }

    public function getVideoInfo(string $url): array
    {
        try {
            $process = new \Symfony\Component\Process\Process([
                $this->binPath . '/yt-dlp',
                '--dump-json',
                '--no-playlist',
                $url
            ]);
            
            $process->run();
            
            if (!$process->isSuccessful()) {
                throw ConverterException::videoInfoFailed($process->getErrorOutput());
            }
            
            $info = json_decode($process->getOutput(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw ConverterException::videoInfoFailed('Invalid JSON response');
            }
            
            return $info;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function downloadVideo(string $url, string $id): string
    {
        try {
            $outputFile = $this->tempPath . '/' . $id . '.%(ext)s';
            
            $process = new \Symfony\Component\Process\Process([
                $this->binPath . '/yt-dlp',
                '--format', $this->options->getVideoFormat(),
                '--output', $outputFile,
                $url
            ]);
            
            $process->run();
            
            if (!$process->isSuccessful()) {
                throw ConverterException::downloadFailed($url, $process->getErrorOutput());
            }
            
            // Find the downloaded file
            $files = glob($this->tempPath . '/' . $id . '.*');
            if (empty($files)) {
                throw ConverterException::downloadFailed($url, 'Output file not found');
            }
            
            return $files[0];

        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function convertToAudio(string $inputFile, array $videoInfo): array
    {
        try {
            $outputFile = $this->outputPath . '/' . $this->generateFilename($videoInfo);
            
            $process = new \Symfony\Component\Process\Process([
                $this->binPath . '/ffmpeg',
                '-i', $inputFile,
                '-vn',
                '-acodec', $this->options->getAudioFormat(),
                '-q:a', (string)$this->options->getAudioQuality(),
                $outputFile
            ]);
            
            $process->run();
            
            if (!$process->isSuccessful()) {
                throw ConverterException::conversionFailed($inputFile, $process->getErrorOutput());
            }
            
            return [
                'title' => $videoInfo['title'] ?? basename($outputFile),
                'file' => basename($outputFile),
                'size' => filesize($outputFile),
                'duration' => $videoInfo['duration'] ?? 0,
                'format' => $this->options->getAudioFormat()
            ];

        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function validatePaths(string $binPath, string $outputPath, string $tempPath): void
    {
        // Validate and create directories if needed
        foreach ([$binPath, $outputPath, $tempPath] as $path) {
            if (!is_dir($path) && !mkdir($path, 0777, true)) {
                throw ConverterException::invalidOutputDirectory($path);
            }
        }
    }

    private function checkDependencies(): void
    {
        // Check yt-dlp
        $ytdlp = $this->binPath . '/yt-dlp';
        if (!file_exists($ytdlp)) {
            throw ConverterException::missingDependency('yt-dlp not found in ' . $this->binPath);
        }

        // Check ffmpeg
        $ffmpeg = $this->binPath . '/ffmpeg';
        if (!file_exists($ffmpeg)) {
            throw ConverterException::missingDependency('ffmpeg not found in ' . $this->binPath);
        }
    }

    private function generateFilename(array $videoInfo): string
    {
        $filename = $videoInfo['title'] ?? uniqid('audio_', true);
        $filename = preg_replace('/[^a-zA-Z0-9]+/', '_', $filename);
        $filename = trim($filename, '_');
        return $filename . '.' . $this->options->getAudioFormat();
    }

    private function cleanup(string $id): void
    {
        try {
            $pattern = $this->tempPath . '/' . $id . '.*';
            $files = glob($pattern);
            
            foreach ($files as $file) {
                @unlink($file);
            }
        } catch (\Exception $e) {
            // Cleanup errors can be ignored
        }
    }
} 