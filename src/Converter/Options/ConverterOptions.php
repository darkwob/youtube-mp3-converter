<?php

namespace Darkwob\YoutubeMp3Converter\Converter\Options;

use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;

class ConverterOptions
{
    private string $audioFormat = 'mp3';
    private int $audioQuality = 0;
    private string $videoFormat = 'bestaudio[ext=webm]/bestaudio[ext=m4a]/bestaudio';
    private bool $embedThumbnail = true;
    private array $metadata = [];
    private ?string $playlistItems = null;
    private ?string $dateAfter = null;
    private ?string $dateBefore = null;
    private ?string $fileSizeLimit = null;
    private ?string $outputTemplate = null;
    private ?string $proxy = null;
    private ?int $rateLimit = null;

    public function setAudioFormat(string $format): self
    {
        $allowedFormats = ['mp3', 'wav', 'aac', 'm4a', 'opus', 'vorbis', 'flac'];
        if (!in_array($format, $allowedFormats)) {
            throw ConverterException::invalidFormat($format);
        }
        $this->audioFormat = $format;
        return $this;
    }

    public function setAudioQuality(int $quality): self
    {
        if ($quality < 0 || $quality > 9) {
            throw ConverterException::invalidQuality($quality);
        }
        $this->audioQuality = $quality;
        return $this;
    }

    public function setVideoFormat(string $format): self
    {
        $this->videoFormat = $format;
        return $this;
    }

    public function enableThumbnail(bool $enable = true): self
    {
        $this->embedThumbnail = $enable;
        return $this;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function setPlaylistItems(string $items): self
    {
        $this->playlistItems = $items;
        return $this;
    }

    public function setDateFilter(string $after = null, string $before = null): self
    {
        $this->dateAfter = $after;
        $this->dateBefore = $before;
        return $this;
    }

    public function setFileSizeLimit(string $limit): self
    {
        $this->fileSizeLimit = $limit;
        return $this;
    }

    public function setOutputTemplate(string $template): self
    {
        $this->outputTemplate = $template;
        return $this;
    }

    public function setProxy(string $proxy): self
    {
        $this->proxy = $proxy;
        return $this;
    }

    public function setRateLimit(int $limit): self
    {
        if ($limit < 1) {
            throw new \InvalidArgumentException('Rate limit must be greater than 0');
        }
        $this->rateLimit = $limit;
        return $this;
    }

    public function getAudioFormat(): string
    {
        return $this->audioFormat;
    }

    public function getAudioQuality(): int
    {
        return $this->audioQuality;
    }

    public function getVideoFormat(): string
    {
        return $this->videoFormat;
    }

    public function shouldEmbedThumbnail(): bool
    {
        return $this->embedThumbnail;
    }

    public function hasMetadata(): bool
    {
        return !empty($this->metadata);
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getPlaylistItems(): ?string
    {
        return $this->playlistItems;
    }

    public function getDateAfter(): ?string
    {
        return $this->dateAfter;
    }

    public function getDateBefore(): ?string
    {
        return $this->dateBefore;
    }

    public function getFileSizeLimit(): ?string
    {
        return $this->fileSizeLimit;
    }

    public function getOutputTemplate(): ?string
    {
        return $this->outputTemplate;
    }

    public function getProxy(): ?string
    {
        return $this->proxy;
    }

    public function getRateLimit(): ?int
    {
        return $this->rateLimit;
    }

    public function toArray(): array
    {
        return [
            'audio_format' => $this->audioFormat,
            'audio_quality' => $this->audioQuality,
            'video_format' => $this->videoFormat,
            'embed_thumbnail' => $this->embedThumbnail,
            'metadata' => $this->metadata,
            'playlist_items' => $this->playlistItems,
            'date_after' => $this->dateAfter,
            'date_before' => $this->dateBefore,
            'file_size_limit' => $this->fileSizeLimit,
            'output_template' => $this->outputTemplate,
            'proxy' => $this->proxy,
            'rate_limit' => $this->rateLimit
        ];
    }
} 