<?php

declare(strict_types=1);

namespace Darkwob\YoutubeMp3Converter\Converter\Interfaces;

use Darkwob\YoutubeMp3Converter\Converter\ConversionResult;

/**
 * @requires PHP >=8.4
 */
interface ConverterInterface
{
    /**
     * Process video for conversion
     * 
     * @param string $url Video URL
     * @return ConversionResult Processing result
     * @throws \Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException
     */
    public function processVideo(string $url): ConversionResult;

    /**
     * Get video information
     * 
     * @param string $url Video URL
     * @return array Video information
     * @throws \Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException
     */
    public function getVideoInfo(string $url): array;

    /**
     * Download video
     * 
     * @param string $url Video URL
     * @param string $id Video ID
     * @return string Downloaded file path
     * @throws \Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException
     */
    public function downloadVideo(string $url, string $id): string;
} 