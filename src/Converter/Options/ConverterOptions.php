<?php

namespace Darkwob\YoutubeMp3Converter\Converter\Options;

class ConverterOptions
{
    private array $options = [];
    private array $allowedFormats = ['mp3', 'wav', 'aac', 'm4a', 'opus', 'vorbis', 'flac'];
    private array $allowedQualities = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
    private array $allowedVideoFormats = ['best', 'bestaudio', 'bestaudio[ext=webm]', 'bestaudio[ext=m4a]'];

    public function __construct(array $options = [])
    {
        $this->setDefaults();
        $this->merge($options);
    }

    public function setDefaults(): self
    {
        $this->options = [
            'format' => 'bestaudio[ext=webm]/bestaudio[ext=m4a]/bestaudio',
            'extractAudio' => true,
            'audioFormat' => 'mp3',
            'audioQuality' => 0,
            'addMetadata' => true,
            'embedThumbnail' => true,
            'noPlaylist' => false,
            'yesPlaylist' => true,
            'ignoreErrors' => true,
            'noWarnings' => true,
            'quiet' => true,
            'noMtime' => true,
            'retries' => 10,
            'fragmentRetries' => 10,
            'keepVideo' => false,
            'preferFfmpeg' => true,
            'geoBypass' => true,
            'bufferSize' => '16K',
            'concurrent' => 3,
            'rateLimit' => '50K',
            'proxy' => '',
            'remoteServer' => '',
            'remoteAuth' => '',
            'outputTemplate' => '%(title)s.%(ext)s',
            'downloadArchive' => '',
            'cookiesFile' => '',
            'sponsorblockRemove' => ['sponsor', 'intro', 'outro', 'selfpromo'],
            'chaptersAsFiles' => false,
            'splitByChapters' => false,
            'maxFilesize' => null,
            'minFilesize' => null,
            'maxDuration' => null,
            'minDuration' => null,
            'dateRange' => null,
            'playlistStart' => 1,
            'playlistEnd' => null,
            'playlistItems' => null,
            'matchTitle' => null,
            'rejectTitle' => null,
            'sleepInterval' => [1, 5],
            'maxSleepInterval' => 10,
            'downloadPath' => null,
            'tempPath' => null,
            'ffmpegLocation' => null,
            'ytdlpLocation' => null,
            'customCommand' => '',
            'headers' => [],
            'postProcessors' => []
        ];
        return $this;
    }

    public function merge(array $options): self
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            } else {
                $this->options[$key] = $value;
            }
        }
        return $this;
    }

    public function setAudioFormat(string $format): self
    {
        if (!in_array($format, $this->allowedFormats)) {
            throw new \InvalidArgumentException(
                "Invalid audio format. Allowed formats: " . implode(', ', $this->allowedFormats)
            );
        }
        $this->options['audioFormat'] = $format;
        return $this;
    }

    public function setAudioQuality(int $quality): self
    {
        if (!in_array($quality, $this->allowedQualities)) {
            throw new \InvalidArgumentException(
                "Invalid audio quality. Allowed values: 0 (best) to 9 (worst)"
            );
        }
        $this->options['audioQuality'] = $quality;
        return $this;
    }

    public function setVideoFormat(string $format): self
    {
        if (!in_array($format, $this->allowedVideoFormats)) {
            throw new \InvalidArgumentException(
                "Invalid video format. Allowed formats: " . implode(', ', $this->allowedVideoFormats)
            );
        }
        $this->options['format'] = $format;
        return $this;
    }

    public function setRemoteServer(string $url, string $auth = ''): self
    {
        $this->options['remoteServer'] = $url;
        $this->options['remoteAuth'] = $auth;
        return $this;
    }

    public function setProxy(string $proxy): self
    {
        $this->options['proxy'] = $proxy;
        return $this;
    }

    public function setRateLimit(string $limit): self
    {
        $this->options['rateLimit'] = $limit;
        return $this;
    }

    public function setConcurrent(int $count): self
    {
        $this->options['concurrent'] = max(1, min($count, 10));
        return $this;
    }

    public function setSponsorblockRemove(array $categories): self
    {
        $allowed = ['sponsor', 'intro', 'outro', 'selfpromo', 'preview', 'interaction', 'music_offtopic'];
        $categories = array_intersect($categories, $allowed);
        $this->options['sponsorblockRemove'] = $categories;
        return $this;
    }

    public function setPlaylistRange(int $start, ?int $end = null): self
    {
        $this->options['playlistStart'] = max(1, $start);
        $this->options['playlistEnd'] = $end;
        return $this;
    }

    public function setPlaylistItems(string $items): self
    {
        $this->options['playlistItems'] = $items;
        return $this;
    }

    public function setDateRange(string $start, ?string $end = null): self
    {
        $this->options['dateRange'] = $end ? "$start:$end" : $start;
        return $this;
    }

    public function setDurationRange(?int $min = null, ?int $max = null): self
    {
        $this->options['minDuration'] = $min;
        $this->options['maxDuration'] = $max;
        return $this;
    }

    public function setFilesizeRange(?string $min = null, ?string $max = null): self
    {
        $this->options['minFilesize'] = $min;
        $this->options['maxFilesize'] = $max;
        return $this;
    }

    public function setOutputTemplate(string $template): self
    {
        $this->options['outputTemplate'] = $template;
        return $this;
    }

    public function setDownloadArchive(string $file): self
    {
        $this->options['downloadArchive'] = $file;
        return $this;
    }

    public function setCookiesFile(string $file): self
    {
        $this->options['cookiesFile'] = $file;
        return $this;
    }

    public function setCustomCommand(string $command): self
    {
        $this->options['customCommand'] = $command;
        return $this;
    }

    public function addHeader(string $name, string $value): self
    {
        $this->options['headers'][$name] = $value;
        return $this;
    }

    public function addPostProcessor(array $processor): self
    {
        $this->options['postProcessors'][] = $processor;
        return $this;
    }

    public function setPaths(string $download, string $temp): self
    {
        $this->options['downloadPath'] = $download;
        $this->options['tempPath'] = $temp;
        return $this;
    }

    public function setBinaries(string $ffmpeg, string $ytdlp): self
    {
        $this->options['ffmpegLocation'] = $ffmpeg;
        $this->options['ytdlpLocation'] = $ytdlp;
        return $this;
    }

    public function toArray(): array
    {
        return $this->options;
    }

    public function getYtDlpOptions(): array
    {
        $ytdlpOptions = [
            'format' => $this->options['format'],
            'extract-audio' => $this->options['extractAudio'],
            'audio-format' => $this->options['audioFormat'],
            'audio-quality' => $this->options['audioQuality'],
            'add-metadata' => $this->options['addMetadata'],
            'embed-thumbnail' => $this->options['embedThumbnail'],
            'no-playlist' => $this->options['noPlaylist'],
            'yes-playlist' => $this->options['yesPlaylist'],
            'ignore-errors' => $this->options['ignoreErrors'],
            'no-warnings' => $this->options['noWarnings'],
            'quiet' => $this->options['quiet'],
            'no-mtime' => $this->options['noMtime'],
            'retries' => $this->options['retries'],
            'fragment-retries' => $this->options['fragmentRetries'],
            'keep-video' => $this->options['keepVideo'],
            'prefer-ffmpeg' => $this->options['preferFfmpeg'],
            'geo-bypass' => $this->options['geoBypass'],
            'buffer-size' => $this->options['bufferSize'],
            'concurrent-fragments' => $this->options['concurrent']
        ];

        if ($this->options['rateLimit']) {
            $ytdlpOptions['rate-limit'] = $this->options['rateLimit'];
        }

        if ($this->options['proxy']) {
            $ytdlpOptions['proxy'] = $this->options['proxy'];
        }

        if ($this->options['outputTemplate']) {
            $ytdlpOptions['output'] = $this->options['outputTemplate'];
        }

        if ($this->options['downloadArchive']) {
            $ytdlpOptions['download-archive'] = $this->options['downloadArchive'];
        }

        if ($this->options['cookiesFile']) {
            $ytdlpOptions['cookies'] = $this->options['cookiesFile'];
        }

        if ($this->options['sponsorblockRemove']) {
            $ytdlpOptions['sponsorblock-remove'] = implode(',', $this->options['sponsorblockRemove']);
        }

        if ($this->options['playlistStart']) {
            $ytdlpOptions['playlist-start'] = $this->options['playlistStart'];
        }

        if ($this->options['playlistEnd']) {
            $ytdlpOptions['playlist-end'] = $this->options['playlistEnd'];
        }

        if ($this->options['playlistItems']) {
            $ytdlpOptions['playlist-items'] = $this->options['playlistItems'];
        }

        if ($this->options['dateRange']) {
            $ytdlpOptions['date'] = $this->options['dateRange'];
        }

        if ($this->options['minDuration']) {
            $ytdlpOptions['min-duration'] = $this->options['minDuration'];
        }

        if ($this->options['maxDuration']) {
            $ytdlpOptions['max-duration'] = $this->options['maxDuration'];
        }

        if ($this->options['minFilesize']) {
            $ytdlpOptions['min-filesize'] = $this->options['minFilesize'];
        }

        if ($this->options['maxFilesize']) {
            $ytdlpOptions['max-filesize'] = $this->options['maxFilesize'];
        }

        if ($this->options['matchTitle']) {
            $ytdlpOptions['match-title'] = $this->options['matchTitle'];
        }

        if ($this->options['rejectTitle']) {
            $ytdlpOptions['reject-title'] = $this->options['rejectTitle'];
        }

        if (!empty($this->options['headers'])) {
            $ytdlpOptions['headers'] = $this->options['headers'];
        }

        return $ytdlpOptions;
    }
} 