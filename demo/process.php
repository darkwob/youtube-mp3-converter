<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Darkwob\YoutubeMp3Converter\Converter\YouTubeConverter;
use Darkwob\YoutubeMp3Converter\Converter\Options\ConverterOptions;
use Darkwob\YoutubeMp3Converter\Progress\FileProgress;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;

header('Content-Type: application/json');

try {
    if (empty($_POST['url'])) {
        throw new ConverterException('URL is required');
    }

    $progress = new FileProgress(__DIR__ . '/progress');
    $options = new ConverterOptions();
    $options->setAudioFormat('mp3')->setAudioQuality(0);
    
    $converter = new YouTubeConverter(
        __DIR__ . '/downloads',
        __DIR__ . '/temp',
        $progress,
        $options
    );

    $result = $converter->processVideo($_POST['url']);
    
    // Return ConversionResult data as JSON
    echo json_encode([
        'success' => true,
        'title' => $result->getTitle(),
        'outputPath' => $result->getOutputPath(),
        'videoId' => $result->getVideoId(),
        'format' => $result->getFormat(),
        'size' => $result->getSize(),
        'duration' => $result->getDuration()
    ]);

} catch (ConverterException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (\Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'An unexpected error occurred'
    ]);
} 