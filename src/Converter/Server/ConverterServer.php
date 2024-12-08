<?php

namespace Darkwob\YoutubeMp3Converter\Converter\Server;

use Darkwob\YoutubeMp3Converter\Converter\YouTubeConverter;
use Darkwob\YoutubeMp3Converter\Progress\FileProgress;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;

class ConverterServer
{
    private string $binPath;
    private string $outputDir;
    private string $tempDir;
    private string $progressDir;
    private array $validTokens;
    private array $activeJobs = [];

    public function __construct(
        string $binPath,
        string $outputDir,
        string $tempDir,
        string $progressDir,
        array $validTokens
    ) {
        $this->binPath = $binPath;
        $this->outputDir = $outputDir;
        $this->tempDir = $tempDir;
        $this->progressDir = $progressDir;
        $this->validTokens = $validTokens;

        foreach ([$outputDir, $tempDir, $progressDir] as $dir) {
            if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
                throw new ConverterException("Cannot create directory: $dir");
            }
        }
    }

    public function handleRequest(): void
    {
        try {
            $this->validateRequest();
            
            $method = $_SERVER['REQUEST_METHOD'];
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $path = trim($path, '/');

            switch ("$method $path") {
                case 'POST process':
                    $this->handleProcess();
                    break;
                case 'GET info':
                    $this->handleInfo();
                    break;
                case 'POST download':
                    $this->handleDownload();
                    break;
                case 'GET status':
                    $this->handleStatus();
                    break;
                default:
                    $this->jsonResponse(['success' => false, 'error' => 'Invalid endpoint'], 404);
            }

        } catch (ConverterException $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Internal server error'], 500);
        }
    }

    private function handleProcess(): void
    {
        $data = $this->getJsonInput();
        
        if (empty($data['url'])) {
            throw new ConverterException('URL is required');
        }

        $jobId = uniqid('job_', true);
        $progress = new FileProgress($this->progressDir);
        
        $converter = new YouTubeConverter(
            $this->binPath,
            $this->outputDir,
            $this->tempDir,
            $progress,
            $data['options'] ?? []
        );

        // Asenkron işlem başlat
        if (function_exists('fastcgi_finish_request')) {
            $this->jsonResponse([
                'success' => true,
                'job_id' => $jobId,
                'message' => 'Processing started'
            ]);
            
            fastcgi_finish_request();
            
            try {
                $result = $converter->processVideo($data['url']);
                $this->activeJobs[$jobId] = $result;
            } catch (\Exception $e) {
                $this->activeJobs[$jobId] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        } else {
            // Senkron işlem
            $result = $converter->processVideo($data['url']);
            $this->jsonResponse(['success' => true, 'result' => $result]);
        }
    }

    private function handleInfo(): void
    {
        $url = $_GET['url'] ?? null;
        if (!$url) {
            throw new ConverterException('URL is required');
        }

        $progress = new FileProgress($this->progressDir);
        $converter = new YouTubeConverter(
            $this->binPath,
            $this->outputDir,
            $this->tempDir,
            $progress
        );

        $info = $converter->getVideoInfo($url);
        $this->jsonResponse(['success' => true, 'data' => $info]);
    }

    private function handleDownload(): void
    {
        $data = $this->getJsonInput();
        
        if (empty($data['url']) || empty($data['id'])) {
            throw new ConverterException('URL and ID are required');
        }

        $progress = new FileProgress($this->progressDir);
        $converter = new YouTubeConverter(
            $this->binPath,
            $this->outputDir,
            $this->tempDir,
            $progress,
            $data['options'] ?? []
        );

        $filePath = $converter->downloadVideo($data['url'], $data['id']);
        $fullPath = $this->outputDir . '/' . $filePath;

        if (!file_exists($fullPath)) {
            throw new ConverterException('File not found');
        }

        // Dosya URL'i döndür
        $fileUrl = $this->getFileUrl($filePath);
        $this->jsonResponse([
            'success' => true,
            'file_url' => $fileUrl
        ]);
    }

    private function handleStatus(): void
    {
        $jobId = $_GET['job_id'] ?? null;
        if (!$jobId) {
            throw new ConverterException('Job ID is required');
        }

        if (!isset($this->activeJobs[$jobId])) {
            $this->jsonResponse([
                'success' => true,
                'status' => 'processing',
                'progress' => 0,
                'message' => 'Job queued'
            ]);
            return;
        }

        $result = $this->activeJobs[$jobId];
        if (isset($result['error'])) {
            $this->jsonResponse([
                'success' => true,
                'status' => 'error',
                'error' => $result['error']
            ]);
        } else {
            $this->jsonResponse([
                'success' => true,
                'status' => 'completed',
                'result' => $result
            ]);
        }
    }

    private function validateRequest(): void
    {
        $token = $this->getBearerToken();
        if (!in_array($token, $this->validTokens)) {
            $this->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            exit;
        }
    }

    private function getBearerToken(): ?string
    {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ConverterException('Invalid JSON input');
        }

        return $data;
    }

    private function jsonResponse(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function getFileUrl(string $filePath): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = dirname($_SERVER['SCRIPT_NAME']);
        return "$protocol://$host$baseUrl/files/$filePath";
    }
} 