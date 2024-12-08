<?php

namespace Darkwob\YoutubeMp3Converter\Converter;

use Darkwob\YoutubeMp3Converter\Converter\Interfaces\ConverterInterface;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;
use Darkwob\YoutubeMp3Converter\Progress\Interfaces\ProgressInterface;
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

    public function __construct(
        string $binPath,
        string $outputDir,
        string $tempDir,
        ProgressInterface $progress,
        array $options = []
    ) {
        $this->validatePaths($binPath, $outputDir, $tempDir);
        
        $this->youtubeDl = new YoutubeDl();
        $this->youtubeDl->setBinPath($binPath . '/yt-dlp');
        $this->progress = $progress;
        $this->outputDir = rtrim($outputDir, '/\\');
        $this->tempDir = rtrim($tempDir, '/\\');
        $this->options = array_merge($this->getDefaultOptions(), $options);
        
        $this->youtubeDl->setDownloadPath($this->tempDir);
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
                    
                    if (!file_exists($this->outputDir . '/' . $mp3Path)) {
                        throw ConverterException::conversionFailed();
                    }
                    
                    $results[] = [
                        'id' => $id,
                        'title' => $video['title'],
                        'status' => 'success',
                        'file' => $mp3Path
                    ];
                    
                    $this->progress->update($id, 'completed', 100, "Video başarıyla dönüştürüldü");
                    
                } catch (\Exception $e) {
                    $this->progress->update($id, 'error', -1, $e->getMessage());
                    $results[] = [
                        'id' => $id,
                        'title' => $video['title'],
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
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
            $this->youtubeDl->setOptions($this->options);
            $collection = $this->youtubeDl->download($url);
            
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
            
            $this->youtubeDl->setOptions(array_merge($this->options, [
                'output' => $this->tempDir . '/' . $id . '.%(ext)s',
                'extract-audio' => true,
                'audio-format' => 'mp3',
                'audio-quality' => 0
            ]));
            
            $collection = $this->youtubeDl->download($url);
            $downloadedVideo = $collection->getVideos()[0];
            
            if ($downloadedVideo->getError()) {
                throw ConverterException::downloadFailed($downloadedVideo->getError());
            }
            
            $mp3File = $title . '.mp3';
            $finalPath = $this->outputDir . '/' . $mp3File;
            
            if (!rename($this->tempDir . '/' . $id . '.mp3', $finalPath)) {
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
    }

    private function getDefaultOptions(): array
    {
        return [
            'format' => 'bestaudio/best',
            'extract-audio' => true,
            'audio-format' => 'mp3',
            'audio-quality' => 0,
            'no-playlist' => false,
            'yes-playlist' => true,
            'ignore-errors' => true,
            'no-warnings' => true,
            'quiet' => true,
            'extract-audio' => true,
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
} 