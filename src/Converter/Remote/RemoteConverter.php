<?php

declare(strict_types=1);

namespace Darkwob\YoutubeMp3Converter\Converter\Remote;

use Darkwob\YoutubeMp3Converter\Converter\Interfaces\ConverterInterface;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;
use Darkwob\YoutubeMp3Converter\Progress\Interfaces\ProgressInterface;
use Darkwob\YoutubeMp3Converter\Converter\ConversionResult;

/**
 * @requires PHP >=8.4
 */

class RemoteConverter implements ConverterInterface
{
    private string $serverUrl;
    private string $authToken;
    private ProgressInterface $progress;
    private array $options;
    private int $timeout;
    private int $connectTimeout;

    public function __construct(
        string $serverUrl,
        string $authToken,
        ProgressInterface $progress,
        array $options = [],
        int $timeout = 3600,
        int $connectTimeout = 10
    ) {
        $this->serverUrl = rtrim($serverUrl, '/');
        $this->authToken = $authToken;
        $this->progress = $progress;
        $this->options = $options;
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
    }

    public function processVideo(string $url): ConversionResult
    {
        try {
            $response = $this->makeRequest('POST', '/process', [
                'url' => $url,
                'options' => $this->options
            ]);

            if (!isset($response['success'])) {
                throw new ConverterException('Invalid response from remote server');
            }

            if (!$response['success']) {
                throw new ConverterException($response['error'] ?? 'Unknown error from remote server');
            }

            // İlerleme takibi
            if (isset($response['job_id'])) {
                $result = $this->trackProgress($response['job_id']);
                return ConversionResult::fromArray($result);
            }

            return ConversionResult::fromArray($response);

        } catch (\Exception $e) {
            throw new ConverterException('Remote server error: ' . $e->getMessage());
        }
    }

    public function getVideoInfo(string $url): array
    {
        try {
            $response = $this->makeRequest('GET', '/info', ['url' => $url]);

            if (!isset($response['success'])) {
                throw new ConverterException('Invalid response from remote server');
            }

            if (!$response['success']) {
                throw new ConverterException($response['error'] ?? 'Unknown error from remote server');
            }

            return $response['data'];

        } catch (\Exception $e) {
            throw new ConverterException('Remote server error: ' . $e->getMessage());
        }
    }

    public function downloadVideo(string $url, string $id): string
    {
        try {
            $response = $this->makeRequest('POST', '/download', [
                'url' => $url,
                'id' => $id,
                'options' => $this->options
            ]);

            if (!isset($response['success'])) {
                throw new ConverterException('Invalid response from remote server');
            }

            if (!$response['success']) {
                throw new ConverterException($response['error'] ?? 'Unknown error from remote server');
            }

            // Dosyayı indir
            if (isset($response['file_url'])) {
                return $this->downloadFile($response['file_url'], $id);
            }

            throw new ConverterException('No file URL in response');

        } catch (\Exception $e) {
            throw new ConverterException('Remote server error: ' . $e->getMessage());
        }
    }

    private function trackProgress(string $jobId): array
    {
        $maxAttempts = 600; // 10 dakika (1 saniye aralıklarla)
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                $response = $this->makeRequest('GET', '/status', ['job_id' => $jobId]);

                if (isset($response['progress'])) {
                    $this->progress->update(
                        $jobId,
                        $response['status'] ?? 'processing',
                        $response['progress'],
                        $response['message'] ?? 'Processing...'
                    );

                    if ($response['status'] === 'completed') {
                        return $response['result'];
                    }

                    if ($response['status'] === 'error') {
                        throw new ConverterException($response['error'] ?? 'Unknown error');
                    }
                }

            } catch (\Exception $e) {
                throw new ConverterException('Progress tracking error: ' . $e->getMessage());
            }

            $attempt++;
            sleep(1);
        }

        throw new ConverterException('Operation timed out');
    }

    private function downloadFile(string $url, string $id): string
    {
        $ch = curl_init($url);
        $fp = fopen($this->options['downloadPath'] . '/' . $id . '.mp3', 'wb');

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);

        curl_exec($ch);
        $error = curl_error($ch);
        
        curl_close($ch);
        fclose($fp);

        if ($error) {
            throw new ConverterException('File download error: ' . $error);
        }

        return $id . '.mp3';
    }

    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $ch = curl_init($this->serverUrl . $endpoint);

        $headers = [
            'Authorization: Bearer ' . $this->authToken,
            'Content-Type: application/json'
        ];

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif (!empty($data)) {
            curl_setopt($ch, CURLOPT_URL, $this->serverUrl . $endpoint . '?' . http_build_query($data));
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);

        if ($error) {
            throw new ConverterException('CURL error: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new ConverterException('HTTP error: ' . $httpCode);
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ConverterException('Invalid JSON response');
        }

        return $decoded;
    }
} 