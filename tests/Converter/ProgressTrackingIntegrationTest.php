<?php

declare(strict_types=1);

namespace Tests\Converter;

use PHPUnit\Framework\TestCase;
use Darkwob\YoutubeMp3Converter\Converter\YouTubeConverter;
use Darkwob\YoutubeMp3Converter\Progress\Interfaces\ProgressInterface;
use Darkwob\YoutubeMp3Converter\Converter\Options\ConverterOptions;

/**
 * Test progress tracking integration in YouTubeConverter
 */
class ProgressTrackingIntegrationTest extends TestCase
{
    private MockProgressTracker $progressTracker;
    private string $tempDir;
    private string $outputDir;

    protected function setUp(): void
    {
        $this->progressTracker = new MockProgressTracker();
        $this->tempDir = sys_get_temp_dir() . '/youtube_converter_test_' . uniqid();
        $this->outputDir = sys_get_temp_dir() . '/youtube_converter_output_' . uniqid();
        
        mkdir($this->tempDir, 0755, true);
        mkdir($this->outputDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        $this->removeDirectory($this->outputDir);
    }

    public function testProgressDataValidation(): void
    {
        $converter = new YouTubeConverter(
            $this->outputDir,
            $this->tempDir,
            $this->progressTracker
        );

        // Use reflection to test private method
        $reflection = new \ReflectionClass($converter);
        $validateMethod = $reflection->getMethod('validateProgressData');
        $validateMethod->setAccessible(true);

        // Test valid data
        $result = $validateMethod->invoke($converter, 'test_video', 'downloading', 50, 'Test message');
        
        $this->assertEquals('test_video', $result['id']);
        $this->assertEquals('downloading', $result['stage']);
        $this->assertEquals(50, $result['percentage']);
        $this->assertEquals('Test message', $result['message']);
        $this->assertIsArray($result['additional_data']);
        $this->assertIsInt($result['timestamp']);
    }

    public function testProgressDataValidationWithInvalidStage(): void
    {
        $converter = new YouTubeConverter(
            $this->outputDir,
            $this->tempDir,
            $this->progressTracker
        );

        $reflection = new \ReflectionClass($converter);
        $validateMethod = $reflection->getMethod('validateProgressData');
        $validateMethod->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid progress stage: invalid_stage');
        
        $validateMethod->invoke($converter, 'test_video', 'invalid_stage', 50, 'Test message');
    }

    public function testProgressDataValidationWithInvalidPercentage(): void
    {
        $converter = new YouTubeConverter(
            $this->outputDir,
            $this->tempDir,
            $this->progressTracker
        );

        $reflection = new \ReflectionClass($converter);
        $validateMethod = $reflection->getMethod('validateProgressData');
        $validateMethod->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Progress percentage must be between 0-100');
        
        $validateMethod->invoke($converter, 'test_video', 'downloading', 150, 'Test message');
    }

    public function testProgressDataFormatting(): void
    {
        $converter = new YouTubeConverter(
            $this->outputDir,
            $this->tempDir,
            $this->progressTracker
        );

        $reflection = new \ReflectionClass($converter);
        $formatMethod = $reflection->getMethod('formatProgressData');
        $formatMethod->setAccessible(true);

        $progressData = [
            'id' => 'test_video',
            'stage' => 'downloading',
            'percentage' => 50,
            'message' => 'Test message',
            'additional_data' => ['download_speed' => '1.5MB/s'],
            'timestamp' => time()
        ];

        $result = $formatMethod->invoke($converter, $progressData);
        
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('stage_display', $result);
        $this->assertArrayHasKey('progress_bar', $result);
        $this->assertEquals('Downloading', $result['stage_display']);
        $this->assertStringContainsString('1.5MB/s', $result['message']);
    }

    public function testProgressBarGeneration(): void
    {
        $converter = new YouTubeConverter(
            $this->outputDir,
            $this->tempDir,
            $this->progressTracker
        );

        $reflection = new \ReflectionClass($converter);
        $progressBarMethod = $reflection->getMethod('generateProgressBar');
        $progressBarMethod->setAccessible(true);

        // Test 0%
        $result = $progressBarMethod->invoke($converter, 0, 10);
        $this->assertEquals('[----------]', $result);

        // Test 50%
        $result = $progressBarMethod->invoke($converter, 50, 10);
        $this->assertEquals('[=====-----]', $result);

        // Test 100%
        $result = $progressBarMethod->invoke($converter, 100, 10);
        $this->assertEquals('[==========]', $result);
    }

    public function testFileSizeFormatting(): void
    {
        $converter = new YouTubeConverter(
            $this->outputDir,
            $this->tempDir,
            $this->progressTracker
        );

        $reflection = new \ReflectionClass($converter);
        $formatMethod = $reflection->getMethod('formatFileSize');
        $formatMethod->setAccessible(true);

        $this->assertEquals('1.0 KB', $formatMethod->invoke($converter, 1024));
        $this->assertEquals('1.0 MB', $formatMethod->invoke($converter, 1024 * 1024));
        $this->assertEquals('1.5 MB', $formatMethod->invoke($converter, 1024 * 1024 * 1.5));
        $this->assertEquals('500.0 B', $formatMethod->invoke($converter, 500));
    }

    public function testDurationFormatting(): void
    {
        $converter = new YouTubeConverter(
            $this->outputDir,
            $this->tempDir,
            $this->progressTracker
        );

        $reflection = new \ReflectionClass($converter);
        $formatMethod = $reflection->getMethod('formatDuration');
        $formatMethod->setAccessible(true);

        $this->assertEquals('03:25', $formatMethod->invoke($converter, 205)); // 3:25
        $this->assertEquals('01:02:30', $formatMethod->invoke($converter, 3750)); // 1:02:30
        $this->assertEquals('00:30', $formatMethod->invoke($converter, 30)); // 0:30
    }

    public function testDownloadProgressParsing(): void
    {
        $converter = new YouTubeConverter(
            $this->outputDir,
            $this->tempDir,
            $this->progressTracker
        );

        $reflection = new \ReflectionClass($converter);
        $parseMethod = $reflection->getMethod('parseDownloadProgress');
        $parseMethod->setAccessible(true);

        // Test yt-dlp progress output
        $output = "[download]  45.2% of 3.45MiB at 1.23MiB/s ETA 00:02";
        $parseMethod->invoke($converter, $output, 'test_video', time());

        // Check that progress was tracked
        $this->assertNotEmpty($this->progressTracker->getUpdates());
        $lastUpdate = end($this->progressTracker->getUpdates());
        $this->assertEquals('downloading', $lastUpdate['status']);
        $this->assertGreaterThan(20, $lastUpdate['progress']); // Should be mapped from 45.2%
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}

/**
 * Mock progress tracker for testing
 */
class MockProgressTracker implements ProgressInterface
{
    private array $updates = [];

    public function update(string $id, string $status, float $progress, string $message): void
    {
        $this->updates[] = [
            'id' => $id,
            'status' => $status,
            'progress' => $progress,
            'message' => $message,
            'timestamp' => time()
        ];
    }

    public function get(string $id): ?array
    {
        foreach (array_reverse($this->updates) as $update) {
            if ($update['id'] === $id) {
                return $update;
            }
        }
        return null;
    }

    public function delete(string $id): void
    {
        $this->updates = array_filter($this->updates, fn($update) => $update['id'] !== $id);
    }

    public function getAll(): array
    {
        return $this->updates;
    }

    public function cleanup(int $maxAge = 3600): void
    {
        $cutoff = time() - $maxAge;
        $this->updates = array_filter($this->updates, fn($update) => $update['timestamp'] > $cutoff);
    }

    public function getUpdates(): array
    {
        return $this->updates;
    }
}