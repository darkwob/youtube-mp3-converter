<?php

namespace Darkwob\YoutubeMp3Converter\Converter\Interfaces;

interface ConverterInterface
{
    /**
     * Process a video or playlist URL
     *
     * @param string $url YouTube video or playlist URL
     * @return array Response with status and results
     */
    public function processVideo(string $url): array;

    /**
     * Get video information
     *
     * @param string $url YouTube URL
     * @return array Video information
     */
    public function getVideoInfo(string $url): array;

    /**
     * Download and convert video to MP3
     *
     * @param string $url Video URL
     * @param string $id Video ID
     * @return string MP3 file path
     */
    public function downloadVideo(string $url, string $id): string;
} 