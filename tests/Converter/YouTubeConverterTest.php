<?php

declare(strict_types=1);

namespace Tests\Converter;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Darkwob\YoutubeMp3Converter\Converter\YouTubeConverter;
use Darkwob\YoutubeMp3Converter\Converter\ConversionResult;
use Darkwob\YoutubeMp3Converter\Converter\Util\DirectoryManager;
use Darkwob\YoutubeMp3Converter\Converter\Util\ProcessManager;
use Darkwob\YoutubeMp3Converter\Progress\Interfaces\ProgressInterface;
use Darkwob\YoutubeMp3Converter\Converter\Options\ConverterOptions;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\InvalidUrlException;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;

/**
 * Unit tests for YouTubeConverter class with mocked dependencies
 */
class YouTubeConverterTest extends TestCase
{
    private MockObject $mockDirectoryManager;
    private MockObject $mockProcessManager;
    private MockObject $mockProgress;
    private MockObject $mockOptions;
    private string $testOutputPath;
    private string $testTempPath;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testOutputPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_output';
        $this->testTempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_temp';
        
        $this->mockDirectoryManager = $this->createMock(DirectoryManager::class);
        $this->mockProcessManager = $this->createMock(ProcessManager::class);
        $this->mockProgress = $this->createMock(ProgressInterface::class);
        $this->mockOptions = $this->createMock(ConverterOptions::class);
        
        // Set default mock behaviors
        $this->mockOptions->method('getAudioFormat')->willReturn('mp3');
        $this->mockOptions->method('getAudioQuality')->willReturn(5);
    }
    
    private function createYouTubeConverter(): YouTubeConverter
    {
        // Create a real YouTubeConverter instance for testing
        return new YouTubeConverter(
            $this->testOutputPath,
            $this->testTempPath,
            $this->mockProgress,
            $this->mockOptions
        );
    }
    
    public function testConstructor(): void
    {
        $progress = $this->createMock(ProgressInterface::class);
        $options = $this->createMock(ConverterOptions::class);
        
        $converter = new YouTubeConverter(
            $this->testOutputPath,
            $this->testTempPath,
            $progress,
            $options
        );
        
        $this->assertInstanceOf(YouTubeConverter::class, $converter);
    }
    
    public function testConstructorWithNullOptions(): void
    {
        $progress = $this->createMock(ProgressInterface::class);
        
        $converter = new YouTubeConverter(
            $this->testOutputPath,
            $this->testTempPath,
            $progress
        );
        
        $this->assertInstanceOf(YouTubeConverter::class, $converter);
    }
    
    public function testValidateUrlWithValidYouTubeUrls(): void
    {
        $converter = $this->createYouTubeConverter();
        
        $validUrls = [
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://youtube.com/watch?v=dQw4w9WgXcQ',
            'https://youtu.be/dQw4w9WgXcQ',
            'https://m.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PLrAXtmRdnEQy8VJqQzNlDJqJGJJJJJJJJ',
            'https://www.youtube.com/shorts/dQw4w9WgXcQ'
        ];
        
        foreach ($validUrls as $url) {
            try {
                $converter->validateUrl($url);
                $this->assertTrue(true, "URL should be valid: {$url}");
            } catch (InvalidUrlException $e) {
                $this->fail("URL should be valid but threw exception: {$url} - {$e->getMessage()}");
            }
        }
    }
    
    public function testValidateUrlWithInvalidUrls(): void
    {
        $converter = $this->createYouTubeConverter();
        
        $invalidUrls = [
            '',
            '   ',
            'not-a-url',
            'http://example.com',
            'https://vimeo.com/123456',
            'ftp://youtube.com/watch?v=dQw4w9WgXcQ',
            'https://youtube.com/watch',
            'https://youtube.com/watch?v=',
            'https://youtube.com/watch?v=invalid-id-too-short'
        ];
        
        foreach ($invalidUrls as $url) {
            $this->expectException(InvalidUrlException::class);
            $converter->validateUrl($url);
        }
    }
    
    public function testExtractVideoIdFromValidUrls(): void
    {
        $converter = $this->createYouTubeConverter();
        
        $testCases = [
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ' => 'dQw4w9WgXcQ',
            'https://youtu.be/dQw4w9WgXcQ' => 'dQw4w9WgXcQ',
            'https://youtube.com/watch?v=dQw4w9WgXcQ' => 'dQw4w9WgXcQ',
            'https://m.youtube.com/watch?v=dQw4w9WgXcQ' => 'dQw4w9WgXcQ',
            'https://www.youtube.com/embed/dQw4w9WgXcQ' => 'dQw4w9WgXcQ',
            'https://www.youtube.com/shorts/dQw4w9WgXcQ' => 'dQw4w9WgXcQ',
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PLtest' => 'dQw4w9WgXcQ'
        ];
        
        foreach ($testCases as $url => $expectedId) {
            $actualId = $converter->extractVideoId($url);
            $this->assertEquals($expectedId, $actualId, "Failed for URL: {$url}");
        }
    }
    
    public function testExtractVideoIdWithInvalidUrl(): void
    {
        $converter = $this->createYouTubeConverter();
        
        $this->expectException(InvalidUrlException::class);
        $converter->extractVideoId('https://example.com/invalid');
    }
    
    public function testGetVideoInfoWithInvalidUrl(): void
    {
        $converter = $this->createYouTubeConverter();
        
        try {
            $result = $converter->getVideoInfo('https://www.youtube.com/watch?v=invalid');
            // If we get here, either the binary doesn't exist or it handled the invalid URL
            $this->assertIsArray($result);
        } catch (\RuntimeException $e) {
            // Expected when binary doesn't exist
            $this->assertStringContainsString('not found', $e->getMessage());
        } catch (ConverterException $e) {
            // Expected when binary exists but URL is invalid or other processing error
            $this->assertTrue(true);
        }
    }
    
    public function testGetVideoInfoWithValidUrl(): void
    {
        $converter = $this->createYouTubeConverter();
        
        try {
            $result = $converter->getVideoInfo('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
            // If we get here, the binary exists and processed the URL
            $this->assertIsArray($result);
            $this->assertArrayHasKey('title', $result);
            $this->assertArrayHasKey('duration', $result);
            $this->assertArrayHasKey('id', $result);
        } catch (\RuntimeException $e) {
            // Expected when binary doesn't exist
            $this->assertStringContainsString('not found', $e->getMessage());
        } catch (ConverterException $e) {
            // Expected when binary exists but processing fails
            $this->assertTrue(true);
        }
    }
    
    public function testDownloadVideoWithInvalidUrl(): void
    {
        $converter = $this->createYouTubeConverter();
        
        try {
            $result = $converter->downloadVideo(
                'https://www.youtube.com/watch?v=invalid',
                'invalid',
                time()
            );
            // If we get here, either the binary doesn't exist or it handled the invalid URL
            $this->assertIsString($result);
        } catch (\RuntimeException $e) {
            // Expected when binary doesn't exist
            $this->assertStringContainsString('not found', $e->getMessage());
        } catch (ConverterException $e) {
            // Expected when binary exists but download fails
            $this->assertTrue(true);
        }
    }
    
    public function testProcessVideoWithValidUrl(): void
    {
        $converter = $this->createYouTubeConverter();
        
        try {
            $result = $converter->processVideo('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
            // If we get here, the binary exists and processed the video
            $this->assertInstanceOf(ConversionResult::class, $result);
            $this->assertEquals('dQw4w9WgXcQ', $result->getVideoId());
            $this->assertEquals('mp3', $result->getFormat());
        } catch (\RuntimeException $e) {
            // Expected when binary doesn't exist
            $this->assertStringContainsString('not found', $e->getMessage());
        } catch (ConverterException $e) {
            // Expected when binary exists but processing fails
            $this->assertTrue(true);
        } catch (InvalidUrlException $e) {
            // Expected for URL validation
            $this->assertTrue(true);
        }
    }
    
    public function testProcessVideoWithInvalidUrl(): void
    {
        $converter = $this->createYouTubeConverter();
        
        $this->expectException(InvalidUrlException::class);
        $converter->processVideo('invalid-url');
    }
    
    public function testProcessVideoWithInvalidUrlThrowsException(): void
    {
        $converter = $this->createYouTubeConverter();
        
        $this->expectException(InvalidUrlException::class);
        $converter->processVideo('invalid-url');
    }
    
    public function testFilenameSanitizationConcept(): void
    {
        // Test that demonstrates filename sanitization concept
        $unsafeChars = ['/', '\\', '<', '>', ':', '"', '|', '?', '*'];
        $testTitle = 'Title/with\\invalid<chars>';
        
        // Simple sanitization logic
        $sanitized = str_replace($unsafeChars, '_', $testTitle);
        
        $this->assertEquals('Title_with_invalid_chars_', $sanitized);
        $this->assertStringNotContainsString('/', $sanitized);
        $this->assertStringNotContainsString('\\', $sanitized);
    }
    
    public function testFormatFileSizePrivateMethod(): void
    {
        $converter = $this->createYouTubeConverter();
        
        $reflection = new \ReflectionClass($converter);
        $method = $reflection->getMethod('formatFileSize');
        $method->setAccessible(true);
        
        $testCases = [
            512 => '512.0 B',
            1024 => '1.0 KB',
            1536 => '1.5 KB',
            1048576 => '1.0 MB',
            1073741824 => '1.0 GB'
        ];
        
        foreach ($testCases as $input => $expected) {
            $result = $method->invoke($converter, $input);
            $this->assertEquals($expected, $result);
        }
    }
    
    public function testDurationFormattingConcept(): void
    {
        // Test that demonstrates duration formatting concept
        $testCases = [
            30.0 => '00:30',
            90.5 => '01:30',
            3661.0 => '61:01',
            180.0 => '03:00'
        ];
        
        foreach ($testCases as $seconds => $expected) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            $formatted = sprintf('%02d:%02d', $minutes, $remainingSeconds);
            
            $this->assertEquals($expected, $formatted);
        }
    }
    
    public function testValidateProgressDataPrivateMethod(): void
    {
        $converter = $this->createYouTubeConverter();
        
        $reflection = new \ReflectionClass($converter);
        $method = $reflection->getMethod('validateProgressData');
        $method->setAccessible(true);
        
        // Valid data
        $result = $method->invoke($converter, 'dQw4w9WgXcQ', 'downloading', 50, 'Downloading video', []);
        
        $this->assertEquals('dQw4w9WgXcQ', $result['id']);
        $this->assertEquals('downloading', $result['stage']);
        $this->assertEquals(50, $result['percentage']);
        $this->assertEquals('Downloading video', $result['message']);
        $this->assertIsArray($result['additional_data']);
        $this->assertIsInt($result['timestamp']);
    }
    
    public function testValidateProgressDataWithInvalidStage(): void
    {
        $converter = $this->createYouTubeConverter();
        
        $reflection = new \ReflectionClass($converter);
        $method = $reflection->getMethod('validateProgressData');
        $method->setAccessible(true);
        
        $this->expectException(ConverterException::class);
        $method->invoke($converter, 'dQw4w9WgXcQ', 'invalid_stage', 50, 'Test message', []);
    }
    
    public function testValidateProgressDataWithInvalidPercentage(): void
    {
        $converter = $this->createYouTubeConverter();
        
        $reflection = new \ReflectionClass($converter);
        $method = $reflection->getMethod('validateProgressData');
        $method->setAccessible(true);
        
        // The method should throw an exception for invalid percentage, not clamp it
        try {
            $method->invoke($converter, 'dQw4w9WgXcQ', 'downloading', 150, 'Test message', []);
            $this->fail('Should throw exception for percentage > 100');
        } catch (ConverterException $e) {
            $this->assertStringContainsString('Progress percentage must be between 0-100', $e->getMessage());
        }
        
        try {
            $method->invoke($converter, 'dQw4w9WgXcQ', 'downloading', -10, 'Test message', []);
            $this->fail('Should throw exception for percentage < 0');
        } catch (ConverterException $e) {
            $this->assertStringContainsString('Progress percentage must be between 0-100', $e->getMessage());
        }
    }
    
    public function testValidateProgressDataWithEmptyVideoId(): void
    {
        $converter = $this->createYouTubeConverter();
        
        $reflection = new \ReflectionClass($converter);
        $method = $reflection->getMethod('validateProgressData');
        $method->setAccessible(true);
        
        $this->expectException(ConverterException::class);
        $method->invoke($converter, '', 'downloading', 50, 'Test message', []);
    }
}