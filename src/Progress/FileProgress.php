<?php

namespace Darkwob\YoutubeMp3Converter\Progress;

use Darkwob\YoutubeMp3Converter\Progress\Interfaces\ProgressInterface;
use Darkwob\YoutubeMp3Converter\Progress\Exceptions\ProgressException;

class FileProgress implements ProgressInterface
{
    private string $progressDir;

    public function __construct(string $progressDir)
    {
        $this->progressDir = rtrim($progressDir, '/\\');
        
        if (!is_dir($this->progressDir)) {
            if (!mkdir($this->progressDir, 0777, true)) {
                throw ProgressException::unableToWrite($this->progressDir);
            }
        }
    }

    public function update(string $id, string $status, int $progress, string $message): bool
    {
        if ($progress < -1 || $progress > 100) {
            throw ProgressException::invalidProgress("Progress must be between -1 and 100");
        }

        $data = [
            'id' => $id,
            'status' => $status,
            'progress' => $progress,
            'message' => $message,
            'timestamp' => time()
        ];

        $file = $this->getFilePath($id);
        
        if (file_put_contents($file, json_encode($data)) === false) {
            throw ProgressException::unableToWrite($file);
        }

        return true;
    }

    public function get(string $id): ?array
    {
        $file = $this->getFilePath($id);
        
        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            throw ProgressException::unableToRead($file);
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ProgressException::invalidJson($file);
        }

        return $data;
    }

    public function delete(string $id): bool
    {
        $file = $this->getFilePath($id);
        
        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

    public function cleanup(int $maxAge = 3600): bool
    {
        $now = time();
        $files = glob($this->progressDir . '/*.json');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $content = file_get_contents($file);
                if ($content !== false) {
                    $data = json_decode($content, true);
                    if (is_array($data) && isset($data['timestamp'])) {
                        if (($now - $data['timestamp']) > $maxAge) {
                            unlink($file);
                        }
                    }
                }
            }
        }

        return true;
    }

    private function getFilePath(string $id): string
    {
        return $this->progressDir . '/' . preg_replace('/[^a-zA-Z0-9]/', '', $id) . '.json';
    }
} 