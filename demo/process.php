<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Darkwob\YoutubeMp3Converter\Converter\YouTubeConverter;
use Darkwob\YoutubeMp3Converter\Progress\FileProgress;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;

header('Content-Type: application/json');

try {
    if (empty($_POST['url'])) {
        throw new ConverterException('URL is required');
    }

    $progress = new FileProgress(__DIR__ . '/progress');
    
    $converter = new YouTubeConverter(
        __DIR__ . '/bin',
        __DIR__ . '/downloads',
        __DIR__ . '/temp',
        $progress
    );

    $result = $converter->processVideo($_POST['url']);
    echo json_encode($result);

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