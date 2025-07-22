<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Darkwob\YoutubeMp3Converter\Converter\YouTubeConverter;
use Darkwob\YoutubeMp3Converter\Converter\Options\ConverterOptions;
use Darkwob\YoutubeMp3Converter\Progress\FileProgress;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\InvalidUrlException;

/**
 * YouTube Playlist Processing Example
 * 
 * This example demonstrates how to:
 * 1. Process entire YouTube playlists
 * 2. Process specific playlist items
 * 3. Handle both single videos and playlists
 * 4. Track progress for playlist processing
 */

// Example playlist URLs
$playlistUrls = [
    'https://www.youtube.com/playlist?list=PLrAXtmRdnEQy6nuLMt9H1mu_8aQBqOTVB', // Example playlist
    'https://www.youtube.com/watch?v=VIDEO_ID&list=PLAYLIST_ID', // Video in playlist
];

// Single video URL for comparison
$singleVideoUrl = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

// Setup progress tracking
$progress = new FileProgress(__DIR__ . '/progress');

// Configure options
$options = new ConverterOptions();
$options->setAudioFormat('mp3')
        ->setAudioQuality(0); // Highest quality

// Create converter
$converter = new YouTubeConverter(
    __DIR__ . '/downloads',
    __DIR__ . '/temp',
    $progress,
    $options
);

echo "=== YouTube Playlist Processing Examples ===\n\n";

// Example 1: Check if URL is playlist
echo "1. URL Type Detection:\n";
foreach ([$singleVideoUrl, $playlistUrls[0]] as $url) {
    $type = $converter->isPlaylistUrl($url) ? 'Playlist' : 'Single Video';
    echo "   {$url} -> {$type}\n";
}
echo "\n";

// Example 2: Get playlist information
echo "2. Playlist Information:\n";
try {
    $playlistInfo = $converter->getPlaylistInfo($playlistUrls[0]);
    echo "   Playlist contains {$playlistInfo['playlist_count']} videos\n";
    
    // Show first 3 videos
    $videos = array_slice($playlistInfo['entries'], 0, 3);
    foreach ($videos as $index => $video) {
        echo "   " . ($index + 1) . ". {$video['title']}\n";
    }
    echo "   ...\n\n";
    
} catch (ConverterException $e) {
    echo "   Error getting playlist info: " . $e->getMessage() . "\n\n";
}

// Example 3: Process specific playlist items
echo "3. Processing Specific Playlist Items (1-3):\n";
try {
    $options->setPlaylistItems('1-3'); // Only first 3 videos
    
    $converter = new YouTubeConverter(
        __DIR__ . '/downloads',
        __DIR__ . '/temp',
        $progress,
        $options
    );
    
    echo "   Starting playlist processing (first 3 videos only)...\n";
    $results = $converter->processPlaylist($playlistUrls[0]);
    
    echo "   Successfully processed " . count($results) . " videos:\n";
    foreach ($results as $result) {
        echo "   - {$result->getTitle()} ({$result->getFormat()})\n";
    }
    echo "\n";
    
} catch (ConverterException $e) {
    echo "   Error processing playlist: " . $e->getMessage() . "\n\n";
}

// Example 4: Process entire playlist (commented out to avoid long processing)
/*
echo "4. Processing Entire Playlist:\n";
try {
    $options->setPlaylistItems(null); // Reset filter
    
    $converter = new YouTubeConverter(
        __DIR__ . '/downloads',
        __DIR__ . '/temp',
        $progress,
        $options
    );
    
    echo "   Starting full playlist processing...\n";
    $results = $converter->processPlaylist($playlistUrls[0]);
    
    echo "   Successfully processed " . count($results) . " videos\n";
    
} catch (ConverterException $e) {
    echo "   Error processing playlist: " . $e->getMessage() . "\n";
}
*/

// Example 5: Smart URL handling (auto-detect playlist vs single video)
echo "4. Smart URL Processing:\n";
function processUrl(YouTubeConverter $converter, string $url): void
{
    try {
        if ($converter->isPlaylistUrl($url)) {
            echo "   Detected playlist URL, processing playlist...\n";
            $results = $converter->processPlaylist($url);
            echo "   Processed " . count($results) . " videos from playlist\n";
        } else {
            echo "   Detected single video URL, processing video...\n";
            $result = $converter->processVideo($url);
            echo "   Processed: {$result->getTitle()}\n";
        }
    } catch (ConverterException $e) {
        echo "   Error: " . $e->getMessage() . "\n";
    }
}

// Reset options for clean processing
$options = new ConverterOptions();
$options->setAudioFormat('mp3')->setAudioQuality(0);

$converter = new YouTubeConverter(
    __DIR__ . '/downloads',
    __DIR__ . '/temp',
    $progress,
    $options
);

// Process single video
processUrl($converter, $singleVideoUrl);

// Process playlist (first 2 items only for demo)
$options->setPlaylistItems('1-2');
$converter = new YouTubeConverter(
    __DIR__ . '/downloads',
    __DIR__ . '/temp',
    $progress,
    $options
);
processUrl($converter, $playlistUrls[0]);

echo "\n=== Examples completed ===\n";

// Example 6: Progress monitoring for playlist
echo "\n5. Progress Monitoring Example:\n";
echo "   You can monitor playlist progress using the progress tracker:\n";
echo "   \$progressData = \$progress->get(\$playlistId);\n";
echo "   This will show current video being processed and overall progress.\n";

// Example 7: Error handling for playlists
echo "\n6. Error Handling:\n";
echo "   Playlist processing continues even if individual videos fail.\n";
echo "   Check logs for individual video errors.\n";
echo "   Final result contains only successfully processed videos.\n";