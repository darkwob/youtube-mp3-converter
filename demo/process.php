<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Darkwob\YoutubeMp3Converter\Converter\YouTubeConverter;
use Darkwob\YoutubeMp3Converter\Converter\Options\ConverterOptions;
use Darkwob\YoutubeMp3Converter\Progress\FileProgress;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\InvalidUrlException;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\BinaryNotFoundException;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\DirectoryException;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ProcessException;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\NetworkException;

header('Content-Type: application/json');

try {
    // Handle different actions
    $action = $_POST['action'] ?? $_GET['action'] ?? 'process';
    
    if ($action === 'progress') {
        handleProgressRequest();
    } else {
        handleProcessRequest();
    }

} catch (InvalidUrlException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_type' => 'invalid_url'
    ]);
} catch (BinaryNotFoundException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_type' => 'binary_not_found',
        'help' => 'Please ensure yt-dlp and ffmpeg are installed and available in the bin/ directory or system PATH'
    ]);
} catch (DirectoryException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_type' => 'directory_error',
        'help' => 'Please check directory permissions and ensure the application can create and write to temp and output directories'
    ]);
} catch (ProcessException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_type' => 'process_error'
    ]);
} catch (NetworkException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_type' => 'network_error',
        'help' => 'Please check your internet connection and try again'
    ]);
} catch (ConverterException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_type' => 'converter_error'
    ]);
} catch (\Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'An unexpected error occurred: ' . $e->getMessage(),
        'error_type' => 'unexpected_error'
    ]);
}

/**
 * Handle video processing request
 */
function handleProcessRequest(): void
{
    if (empty($_POST['url'])) {
        throw new InvalidUrlException('URL is required');
    }

    $progress = new FileProgress(__DIR__ . '/progress');
    $options = new ConverterOptions();
    $options->setAudioFormat('mp3')->setAudioQuality(0); // High quality
    
    // Apply playlist items filter if specified
    if (!empty($_POST['playlist_items'])) {
        $options->setPlaylistItems($_POST['playlist_items']);
    }
    
    $converter = new YouTubeConverter(
        __DIR__ . '/downloads',
        __DIR__ . '/temp',
        $progress,
        $options
    );

    $url = $_POST['url'];
    
    // Check if it's a playlist URL
    if ($converter->isPlaylistUrl($url)) {
        handlePlaylistRequest($converter, $url, $progress);
    } else {
        handleSingleVideoRequest($converter, $url, $progress);
    }
}

/**
 * Handle single video processing
 */
function handleSingleVideoRequest(YouTubeConverter $converter, string $url, FileProgress $progress): void
{
    // First get video info to create initial response
    $videoInfo = $converter->getVideoInfo($url);
    $videoId = $converter->extractVideoId($url);
    
    // Return initial video info immediately
    echo json_encode([
        'success' => true,
        'type' => 'single_video',
        'results' => [[
            'id' => $videoId,
            'title' => $videoInfo['title'],
            'duration' => $videoInfo['duration'],
            'thumbnail' => $videoInfo['thumbnail'],
            'uploader' => $videoInfo['uploader'],
            'status' => 'starting'
        ]],
        'message' => 'Video processing started'
    ]);
    
    // Flush output to send response immediately
    if (ob_get_level()) {
        ob_end_flush();
    }
    flush();
    
    // Continue processing in background
    try {
        $result = $converter->processVideo($url);
        
        // Update progress file with completion status
        $progress->update($videoId, 'completed', 100, 'Conversion completed successfully');
        
    } catch (\Exception $e) {
        // Update progress file with error status
        $progress->update($videoId, 'error', 0, $e->getMessage());
    }
}

/**
 * Handle playlist processing
 */
function handlePlaylistRequest(YouTubeConverter $converter, string $playlistUrl, FileProgress $progress): void
{
    // First get playlist info to create initial response
    $playlistInfo = $converter->getPlaylistInfo($playlistUrl);
    $playlistId = $converter->extractPlaylistId($playlistUrl);
    $videos = $playlistInfo['entries'] ?? [];
    
    // Prepare initial response with video list
    $videoList = [];
    foreach ($videos as $index => $video) {
        $videoList[] = [
            'id' => $video['id'],
            'title' => $video['title'] ?? 'Unknown Title',
            'duration' => $video['duration'] ?? 0,
            'thumbnail' => $video['thumbnail'] ?? null,
            'uploader' => $video['uploader'] ?? 'Unknown',
            'status' => 'pending',
            'index' => $index + 1
        ];
    }
    
    // Return initial playlist info immediately
    echo json_encode([
        'success' => true,
        'type' => 'playlist',
        'playlist_id' => $playlistId,
        'total_videos' => count($videos),
        'results' => $videoList,
        'message' => 'Playlist processing started'
    ]);
    
    // Flush output to send response immediately
    if (ob_get_level()) {
        ob_end_flush();
    }
    flush();
    
    // Continue processing in background
    try {
        $results = $converter->processPlaylist($playlistUrl);
        
        // Update progress file with completion status
        $progress->update($playlistId, 'completed', 100, 
            'Playlist processing completed. ' . count($results) . ' videos converted successfully');
        
    } catch (\Exception $e) {
        // Update progress file with error status
        $progress->update($playlistId, 'error', 0, $e->getMessage());
    }
}

/**
 * Handle progress check request
 */
function handleProgressRequest(): void
{
    if (empty($_GET['id'])) {
        throw new ConverterException('Video ID is required for progress check');
    }
    
    $videoId = $_GET['id'];
    $progress = new FileProgress(__DIR__ . '/progress');
    
    try {
        $progressData = $progress->get($videoId);
        
        if ($progressData === null) {
            echo json_encode([
                'success' => false,
                'error' => 'Progress data not found for video ID: ' . $videoId
            ]);
            return;
        }
        
        // Format progress data for frontend
        $formattedProgress = formatProgressForFrontend($progressData);
        
        echo json_encode([
            'success' => true,
            'data' => $formattedProgress
        ]);
        
    } catch (\Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to get progress data: ' . $e->getMessage()
        ]);
    }
}

/**
 * Format progress data for frontend consumption
 */
function formatProgressForFrontend(array $progressData): array
{
    $stage = $progressData['status'] ?? 'unknown';
    $percentage = $progressData['progress'] ?? 0;
    $message = $progressData['message'] ?? '';
    
    // Map internal stages to frontend-friendly stages
    $stageMapping = [
        'starting' => 'downloading',
        'downloading' => 'downloading',
        'converting' => 'converting',
        'completed' => 'completed',
        'error' => 'error',
        'cancelled' => 'error'
    ];
    
    $frontendStage = $stageMapping[$stage] ?? 'downloading';
    
    // Format message for better user experience
    $formattedMessage = match ($frontendStage) {
        'downloading' => $percentage < 70 ? 'İndiriliyor...' : 'İndirme tamamlandı',
        'converting' => 'MP3 formatına dönüştürülüyor...',
        'completed' => 'Dönüştürme tamamlandı!',
        'error' => 'Hata: ' . $message,
        default => $message
    };
    
    return [
        'stage' => $frontendStage,
        'progress' => max(0, min(100, (int)$percentage)),
        'message' => $formattedMessage,
        'timestamp' => $progressData['updated_at'] ?? time()
    ];
} 