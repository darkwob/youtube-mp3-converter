<?php

declare(strict_types=1);

namespace Darkwob\YoutubeMp3Converter\Converter;

/**
 * Video conversion result data object
 * 
 * @package Darkwob\YoutubeMp3Converter
 * @requires PHP >=8.4
 */
readonly class ConversionResult
{
    public function __construct(
        public string $outputPath,
        public string $title,
        public string $videoId,
        public string $format,
        public int $size,
        public float $duration
    ) {}

    /**
     * Get the output file path
     */
    public function getOutputPath(): string
    {
        return $this->outputPath;
    }

    /**
     * Get the video title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get the video ID
     */
    public function getVideoId(): string
    {
        return $this->videoId;
    }

    /**
     * Get the audio format
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Get the file size in bytes
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Get the duration in seconds
     */
    public function getDuration(): float
    {
        return $this->duration;
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'outputPath' => $this->outputPath,
            'title' => $this->title,
            'videoId' => $this->videoId,
            'format' => $this->format,
            'size' => $this->size,
            'duration' => $this->duration
        ];
    }

    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['outputPath'] ?? $data['output_path'] ?? '',
            $data['title'] ?? '',
            $data['videoId'] ?? $data['video_id'] ?? '',
            $data['format'] ?? '',
            $data['size'] ?? 0,
            $data['duration'] ?? 0.0
        );
    }
} 