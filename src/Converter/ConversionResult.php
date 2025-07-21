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
        public float $duration,
        public ?string $thumbnailUrl = null,
        public ?string $uploader = null,
        public ?string $uploadDate = null,
        public array $availableFormats = []
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
     * Get the thumbnail URL
     */
    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    /**
     * Get the uploader name
     */
    public function getUploader(): ?string
    {
        return $this->uploader;
    }

    /**
     * Get the upload date
     */
    public function getUploadDate(): ?string
    {
        return $this->uploadDate;
    }

    /**
     * Get available formats
     */
    public function getAvailableFormats(): array
    {
        return $this->availableFormats;
    }

    /**
     * Validate the conversion result data
     */
    public function validate(): bool
    {
        // Output path should exist and be readable
        if (empty($this->outputPath) || !file_exists($this->outputPath)) {
            return false;
        }

        // Title should not be empty
        if (empty(trim($this->title))) {
            return false;
        }

        // Video ID should not be empty
        if (empty(trim($this->videoId))) {
            return false;
        }

        // Format should be a valid audio format
        $validFormats = ['mp3', 'aac', 'ogg', 'wav', 'm4a', 'flac'];
        if (!in_array(strtolower($this->format), $validFormats)) {
            return false;
        }

        // Size should be positive
        if ($this->size <= 0) {
            return false;
        }

        // Duration should be positive
        if ($this->duration <= 0) {
            return false;
        }

        // Validate thumbnail URL if provided
        if ($this->thumbnailUrl !== null && !filter_var($this->thumbnailUrl, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Validate upload date format if provided (YYYY-MM-DD or YYYYMMDD)
        if ($this->uploadDate !== null && !$this->isValidDateFormat($this->uploadDate)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the output file exists and is readable
     */
    public function isFileAccessible(): bool
    {
        return file_exists($this->outputPath) && is_readable($this->outputPath);
    }

    /**
     * Get file size in human readable format
     */
    public function getFormattedSize(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get duration in human readable format (MM:SS)
     */
    public function getFormattedDuration(): string
    {
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Validate date format
     */
    private function isValidDateFormat(string $date): bool
    {
        // Check YYYY-MM-DD format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return (bool) strtotime($date);
        }
        
        // Check YYYYMMDD format
        if (preg_match('/^\d{8}$/', $date)) {
            $year = substr($date, 0, 4);
            $month = substr($date, 4, 2);
            $day = substr($date, 6, 2);
            return checkdate((int)$month, (int)$day, (int)$year);
        }
        
        return false;
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
            'duration' => $this->duration,
            'thumbnailUrl' => $this->thumbnailUrl,
            'uploader' => $this->uploader,
            'uploadDate' => $this->uploadDate,
            'availableFormats' => $this->availableFormats
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
            $data['duration'] ?? 0.0,
            $data['thumbnailUrl'] ?? $data['thumbnail_url'] ?? null,
            $data['uploader'] ?? null,
            $data['uploadDate'] ?? $data['upload_date'] ?? null,
            $data['availableFormats'] ?? $data['available_formats'] ?? []
        );
    }
} 