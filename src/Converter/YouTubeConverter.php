<?php

namespace Darkwob\YoutubeMp3Converter\Converter;

use Darkwob\YoutubeMp3Converter\Converter\Interfaces\ConverterInterface;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;
use Darkwob\YoutubeMp3Converter\Progress\Interfaces\ProgressInterface;
use YoutubeDl\Options;
use YoutubeDl\YoutubeDl;
use YoutubeDl\Exception\ExecutableNotFoundException;
use YoutubeDl\Exception\YoutubeDlException;

class YouTubeConverter implements ConverterInterface
{
    private YoutubeDl $youtubeDl;
    private ProgressInterface $progress;
    private string $outputDir;
    private string $tempDir;
    private array $options;
    private string $ffmpegPath;

    public function __construct(
        string $binPath,
        string $outputDir,
        string $tempDir,
        ProgressInterface $progress,
        array $options = []
    ) {
        $this->validatePaths($binPath, $outputDir, $tempDir);
        
        $this->youtubeDl = new YoutubeDl();
        $this->youtubeDl->setBinPath($binPath . DIRECTORY_SEPARATOR . 'yt-dlp');
        $this->ffmpegPath = $binPath . DIRECTORY_SEPARATOR . 'ffmpeg';
        $this->progress = $progress;
        $this->outputDir = rtrim($outputDir, '/\\');
        $this->tempDir = rtrim($tempDir, '/\\');
        $this->options = array_merge($this->getDefaultOptions(), $options);
    }

    public function processVideo(string $url): array
    {
        try {
            $info = $this->getVideoInfo($url);
            
            if (empty($info['videos'])) {
                throw ConverterException::videoInfoFailed();
            }
            
            $results = [];
            $totalVideos = count($info['videos']);
            
            foreach ($info['videos'] as $index => $video) {
                $id = $video['id'];
                $currentVideo = $index + 1;
                
                try {
                    $this->progress->update($id, 'downloading', 0, "Video {$currentVideo}/{$totalVideos} indiriliyor...");
                    $mp3Path = $this->downloadVideo($video['url'], $id);
                    
                    if (!file_exists($this->outputDir . DIRECTORY_SEPARATOR . $mp3Path)) {
                        throw ConverterException::conversionFailed();
                    }
                    
                    $results[] = [
                        'id' => $id,
                        'title' => $video['title'],
                        'status' => 'success',
                        'file' => $mp3Path
                    ];
                    
                    $this->progress->update($id, 'completed', 100, "Video başarıyla dönüştürüldü");
                    $this->cleanupTempFiles($id);
                    
                } catch (\Exception $e) {
                    $this->progress->update($id, 'error', -1, $e->getMessage());
                    $results[] = [
                        'id' => $id,
                        'title' => $video['title'],
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                    $this->cleanupTempFiles($id);
                }
            }
            
            return [
                'success' => true,
                'is_playlist' => $info['is_playlist'],
                'playlist_title' => $info['playlist_title'] ?? '',
                'total_videos' => $totalVideos,
                'processed_videos' => count($results),
                'results' => $results
            ];
            
        } catch (\Exception $e) {
            throw ConverterException::videoInfoFailed($e->getMessage());
        }
    }

    public function getVideoInfo(string $url): array
    {
        try {
            $options = new Options();
            $options->setFFmpegLocation($this->ffmpegPath);
            foreach ($this->options as $key => $value) {
                $options->set($key, $value);
            }
            
            $collection = $this->youtubeDl->download($options->setUrl($url));
            
            $videos = [];
            $isPlaylist = false;
            $playlistTitle = '';
            
            foreach ($collection->getVideos() as $video) {
                if (!$video->getError()) {
                    $videos[] = [
                        'id' => $video->getId(),
                        'title' => $video->getTitle(),
                        'url' => $url,
                        'duration' => $video->getDuration()
                    ];
                    
                    if ($video->getPlaylist()) {
                        $isPlaylist = true;
                        $playlistTitle = $video->getPlaylist();
                    }
                }
            }
            
            if (empty($videos)) {
                // İkinci deneme: Farklı format seçenekleriyle
                $options->set('format', 'bestaudio[ext=webm]/bestaudio[ext=m4a]/bestaudio');
                $collection = $this->youtubeDl->download($options);
                
                foreach ($collection->getVideos() as $video) {
                    if (!$video->getError()) {
                        $videos[] = [
                            'id' => $video->getId(),
                            'title' => $video->getTitle(),
                            'url' => $url,
                            'duration' => $video->getDuration()
                        ];
                    }
                }
            }
            
            return [
                'is_playlist' => $isPlaylist,
                'playlist_title' => $playlistTitle,
                'videos' => $videos
            ];
            
        } catch (ExecutableNotFoundException $e) {
            throw ConverterException::missingDependency('yt-dlp executable not found');
        } catch (YoutubeDlException $e) {
            throw ConverterException::videoInfoFailed($e->getMessage());
        }
    }

    public function downloadVideo(string $url, string $id): string
    {
        try {
            $info = $this->getVideoInfo($url);
            if (empty($info['videos'])) {
                throw ConverterException::downloadFailed('No video information found');
            }
            
            $video = $info['videos'][0];
            $title = $this->sanitizeFileName($video['title']);
            
            $options = new Options();
            $options->setFFmpegLocation($this->ffmpegPath)
                   ->setUrl($url)
                   ->setExtractAudio(true)
                   ->setAudioFormat('mp3')
                   ->setAudioQuality(0)
                   ->setOutput($this->tempDir . DIRECTORY_SEPARATOR . $id . '.%(ext)s');

            foreach ($this->options as $key => $value) {
                $options->set($key, $value);
            }
            
            $collection = $this->youtubeDl->download($options);
            $downloadedVideo = $collection->getVideos()[0];
            
            if ($downloadedVideo->getError()) {
                throw ConverterException::downloadFailed($downloadedVideo->getError());
            }
            
            $mp3File = $title . '.mp3';
            $finalPath = $this->outputDir . DIRECTORY_SEPARATOR . $mp3File;
            
            if (!rename($this->tempDir . DIRECTORY_SEPARATOR . $id . '.mp3', $finalPath)) {
                throw ConverterException::conversionFailed('Failed to move MP3 file');
            }
            
            return $mp3File;
            
        } catch (\Exception $e) {
            throw ConverterException::downloadFailed($e->getMessage());
        }
    }

    private function validatePaths(string $binPath, string $outputDir, string $tempDir): void
    {
        foreach ([$binPath, $outputDir, $tempDir] as $dir) {
            if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
                throw ConverterException::invalidOutputDirectory($dir);
            }
        }

        if (!file_exists($binPath . DIRECTORY_SEPARATOR . 'yt-dlp')) {
            throw ConverterException::missingDependency('yt-dlp');
        }

        if (!file_exists($binPath . DIRECTORY_SEPARATOR . 'ffmpeg')) {
            throw ConverterException::missingDependency('ffmpeg');
        }
    }

    private function getDefaultOptions(): array
    {
        return [
            'format' => 'bestaudio[ext=webm]/bestaudio[ext=m4a]/bestaudio',
            'extract-audio' => true,
            'audio-format' => 'mp3',
            'audio-quality' => 0,
            'no-playlist' => false,
            'yes-playlist' => true,
            'ignore-errors' => true,
            'no-warnings' => true,
            'quiet' => true,
            'add-metadata' => true,
            'embed-thumbnail' => true,
            'no-mtime' => true
        ];
    }

    private function sanitizeFileName(string $filename): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9\-_\s]/', '', $filename);
        $filename = trim($filename);
        $filename = preg_replace('/\s+/', '_', $filename);
        return $filename;
    }

    private function cleanupTempFiles(string $id): void
    {
        $patterns = [
            $this->tempDir . DIRECTORY_SEPARATOR . $id . '.*',
            $this->tempDir . DIRECTORY_SEPARATOR . '*.webm',
            $this->tempDir . DIRECTORY_SEPARATOR . '*.m4a',
            $this->tempDir . DIRECTORY_SEPARATOR . '*.opus',
            $this->tempDir . DIRECTORY_SEPARATOR . '*.mp4',
            $this->tempDir . DIRECTORY_SEPARATOR . '*.part',
            $this->tempDir . DIRECTORY_SEPARATOR . '*.ytdl'
        ];

        foreach ($patterns as $pattern) {
            $files = glob($pattern);
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
        }
    }
} 