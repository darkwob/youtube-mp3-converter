<?php

namespace Darkwob\YoutubeMp3Converter\Progress;

use Darkwob\YoutubeMp3Converter\Progress\Interfaces\ProgressInterface;

class FileProgress implements ProgressInterface
{
    private string $directory;
    private string $extension = '.progress';

    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, '/');
        
        if (!is_dir($directory) && !mkdir($directory, 0777, true)) {
            throw new \RuntimeException("Cannot create directory: $directory");
        }
    }

    public function update(string $id, string $status, float $progress, string $message): void
    {
        $data = [
            'id' => $id,
            'status' => $status,
            'progress' => $progress,
            'message' => $message,
            'updated_at' => time()
        ];

        $file = $this->getFilePath($id);
        if (file_put_contents($file, json_encode($data)) === false) {
            throw new \RuntimeException("Cannot write progress file: $file");
        }
    }

    public function get(string $id): ?array
    {
        $file = $this->getFilePath($id);
        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    public function delete(string $id): void
    {
        $file = $this->getFilePath($id);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function getAll(): array
    {
        $pattern = $this->directory . '/*' . $this->extension;
        $files = glob($pattern);
        
        $progress = [];
        foreach ($files as $file) {
            $id = basename($file, $this->extension);
            $data = $this->get($id);
            if ($data !== null) {
                $progress[] = $data;
            }
        }

        return $progress;
    }

    public function cleanup(int $maxAge = 3600): void
    {
        $now = time();
        $pattern = $this->directory . '/*' . $this->extension;
        $files = glob($pattern);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }

            $age = $now - ($data['updated_at'] ?? 0);
            if ($age > $maxAge) {
                unlink($file);
            }
        }
    }

    private function getFilePath(string $id): string
    {
        return $this->directory . '/' . $id . $this->extension;
    }
} 