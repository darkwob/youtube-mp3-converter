<?php

namespace Darkwob\YoutubeMp3Converter\Converter\Interfaces;

interface ConverterInterface
{
    /**
     * Process video for conversion
     * 
     * @param string $url Video URL
     * @return array Processing result
     * @throws \Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException
     */
    public function processVideo(string $url): array;

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