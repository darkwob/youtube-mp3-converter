<?php

declare(strict_types=1);

namespace Tests\Converter;

use PHPUnit\Framework\TestCase;
use Darkwob\YoutubeMp3Converter\Converter\ConversionResult;

/**
 * Unit tests for ConversionResult class
 */
class ConversionResultTest extends TestCase
{
    private string $testOutputPath;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a temporary test file
        $this->testOutputPath = tempnam(sys_get_temp_dir(), 'test_output_');
        file_put_contents($this->testOutputPath, 'test content');
    }
    
    protected function tearDown(): void
    {
        if (file_exists($this->testOutputPath)) {
            unlink($this->testOutputPath);
        }
        
        parent::tearDown();
    }
    
    public function testConstructorAndGetters(): void
    {
        $result = new ConversionResult(
            outputPath: $this->testOutputPath,
            title: 'Test Video',
            videoId: 'abc123',
            format: 'mp3',
            size: 1024,
            duration: 180.5,
            thumbnailUrl: 'https://example.com/thumb.jpg',
            uploader: 'Test Channel',
            uploadDate: '2024-01-15',
            availableFormats: ['mp3', 'aac']
        );
        
        $this->assertEquals($this->testOutputPath, $result->getOutputPath());
        $this->assertEquals('Test Video', $result->getTitle());
        $this->assertEquals('abc123', $result->getVideoId());
        $this->assertEquals('mp3', $result->getFormat());
        $this->assertEquals(1024, $result->getSize());
        $this->assertEquals(180.5, $result->getDuration());
        $this->assertEquals('https://example.com/thumb.jpg', $result->getThumbnailUrl());
        $this->assertEquals('Test Channel', $result->getUploader());
        $this->assertEquals('2024-01-15', $result->getUploadDate());
        $this->assertEquals(['mp3', 'aac'], $result->getAvailableFormats());
    }
    
    public function testConstructorWithNullOptionalValues(): void
    {
        $result = new ConversionResult(
            outputPath: $this->testOutputPath,
            title: 'Test Video',
            videoId: 'abc123',
            format: 'mp3',
            size: 1024,
            duration: 180.5
        );
        
        $this->assertNull($result->getThumbnailUrl());
        $this->assertNull($result->getUploader());
        $this->assertNull($result->getUploadDate());
        $this->assertEquals([], $result->getAvailableFormats());
    }
    
    public function testValidateWithValidData(): void
    {
        $result = new ConversionResult(
            outputPath: $this->testOutputPath,
            title: 'Test Video',
            videoId: 'abc123',
            format: 'mp3',
            size: 1024,
            duration: 180.5,
            thumbnailUrl: 'https://example.com/thumb.jpg',
            uploadDate: '2024-01-15'
        );
        
        $this->assertTrue($result->validate());
    }
    
    public function testValidateWithNonExistentFile(): void
    {
        $result = new ConversionResult(
            outputPath: '/non/existent/file.mp3',
            title: 'Test Video',
            videoId: 'abc123',
            format: 'mp3',
            size: 1024,
            duration: 180.5
        );
        
        $this->assertFalse($result->validate());
    }
    
    public function testValidateWithEmptyTitle(): void
    {
        $result = new ConversionResult(
            outputPath: $this->testOutputPath,
            title: '   ',
            videoId: 'abc123',
            format: 'mp3',
            size: 1024,
            duration: 180.5
        );
        
        $this->assertFalse($result->validate());
    }
    
    public function testValidateWithEmptyVideoId(): void
    {
        $result = new ConversionResult(
            outputPath: $this->testOutputPath,
            title: 'Test Video',
            videoId: '',
            format: 'mp3',
            size: 1024,
            duration: 180.5
        );
        
        $this->assertFalse($result->validate());
    }
    
    public function testValidateWithInvalidFormat(): void
    {
        $result = new ConversionResult(
            outputPath: $this->testOutputPath,
            title: 'Test Video',
            videoId: 'abc123',
            format: 'invalid',
            size: 1024,
            duration: 180.5
        );
        
        $this->assertFalse($result->validate());
    }
    
    public function testValidateWithValidFormats(): void
    {
        $validFormats = ['mp3', 'aac', 'ogg', 'wav', 'm4a', 'flac'];
        
        foreach ($validFormats as $format) {
            $result = new ConversionResult(
                outputPath: $this->testOutputPath,
                title: 'Test Video',
                videoId: 'abc123',
                format: $format,
                size: 1024,
                duration: 180.5
            );
            
            $this->assertTrue($result->validate(), "Format {$format} should be valid");
        }
    }
    
    public function testValidateWithZeroSize(): void
    {
        $result = new ConversionResult(
            outputPath: $this->testOutputPath,
            title: 'Test Video',
            videoId: 'abc123',
            format: 'mp3',
            size: 0,
            duration: 180.5
        );
        
        $this->assertFalse($result->validate());
    }
    
    public function testValidateWithNegativeSize(): void
    {
        $result = new ConversionResult(
            outputPath: $this->testOutputPath,
            title: 'Test Video',
            videoId: 'abc123',
            format: 'mp3',
            size: -100,
            duration: 180.5
        );
        
        $this->assertFalse($result->validate());
    }
    
    public function testValidateWithZeroDuration(): void
    {
        $result = new ConversionResult(
            outputPath: $this->testOutputPath,
            title: 'Test Video',
            videoId: 'abc123',
            format: 'mp3',
            size: 1024,
            duration: 0.0
        );
        
        $this->assertFalse($result->validate());
    }
    
    public function testValidateWithNegativeDuration(): void
    {
        $result = new ConversionResult(
            outputPath: $this->testOutputPath,
            title: 'Test Video',
            videoId: 'abc123',
            format: 'mp3',
            size: 1024,
            duration: -10.5
        );
        
        $this->assertFalse($result->validate());
    }
    
    public function testValidateWithInvalidThumbnailUrl(): void
    {
        $result = new ConversionResult(
            outputPath: $this->testOutputPath,
            title: 'Test Video',
            videoId: 'abc123',
            format: 'mp3',
            size: 1024,
            duration: 180.5,
            thumbnailUrl: 'not-a-valid-url'
        );
        
        $this->assertFalse($result->validate());
    }
    
    public function testValidateWithValidDateFormats(): void
    {
        $validDates = ['2024-01-15', '20240115'];
        
        foreach ($validDates as $date) {
            $result = new ConversionResult(
                outputPath: $this->testOutputPath,
                title: 'Test Video',
                videoId: 'abc123',
                format: 'mp3',
                size: 1024,
                duration: 180.5,
                uploadDate: $date
            );
            
            $this->assertTrue($result->validate(), "Date format {$date} should be valid");
        }
    }
    
    public function testValidateWithInvalidDateFormats(): void
    {
        $invalidDates = ['2024-13-01', '20241301', '2024/01/15', 'invalid-date'];
        
        foreach ($invalidDates as $date) {
            $result = new ConversionResult(
                outputPath: $this->testOutputPath,
                title: 'Test Video',
                videoId: 'abc123',
                format: 'mp3',
                size: 1024,
                duration: 180.5,
                uploadDate: $date
            );
            
            $this->assertFalse($result->validate(), "Date format {$date} should be invalid");
        }
    }
    
    public function testIsFileAccessible(): void
    {
        $result = new ConversionResult(
            outputPath: $this->testOutputPath,
            title: 'Test Video',
            videoId: 'abc123',
            format: 'mp3',
            size: 1024,
            duration: 180.5
        );
        
        $this->assertTrue($result->isFileAccessible());
        
        // Test with non-existent file
        $result2 = new ConversionResult(
            outputPath: '/non/existent/file.mp3',
            title: 'Test Video',
            videoId: 'abc123',
            format: 'mp3',
            size: 1024,
            duration: 180.5
        );
        
        $this->assertFalse($result2->isFileAccessible());
    }
    
    public function testGetFormattedSize(): void
    {
        $testCases = [
            [512, '512 B'],
            [1024, '1024 B'],  // Since condition is > 1024, not >= 1024
            [1536, '1.5 KB'],
            [1048576, '1024 KB'],  // Since condition is > 1024, not >= 1024
            [1073741824, '1024 MB']  // Since condition is > 1024, not >= 1024
        ];
        
        foreach ($testCases as [$size, $expected]) {
            $result = new ConversionResult(
                outputPath: $this->testOutputPath,
                title: 'Test Video',
                videoId: 'abc123',
                format: 'mp3',
                size: $size,
                duration: 180.5
            );
            
            $this->assertEquals($expected, $result->getFormattedSize());
        }
    }
    
    public function testGetFormattedDuration(): void
    {
        $testCases = [
            [30.0, '00:30'],
            [90.5, '01:30'],
            [3661.0, '61:01'],
            [180.0, '03:00']
        ];
        
        foreach ($testCases as [$duration, $expected]) {
            $result = new ConversionResult(
                outputPath: $this->testOutputPath,
                title: 'Test Video',
                videoId: 'abc123',
                format: 'mp3',
                size: 1024,
                duration: $duration
            );
            
            $this->assertEquals($expected, $result->getFormattedDuration());
        }
    }
    
    public function testToArray(): void
    {
        $result = new ConversionResult(
            outputPath: $this->testOutputPath,
            title: 'Test Video',
            videoId: 'abc123',
            format: 'mp3',
            size: 1024,
            duration: 180.5,
            thumbnailUrl: 'https://example.com/thumb.jpg',
            uploader: 'Test Channel',
            uploadDate: '2024-01-15',
            availableFormats: ['mp3', 'aac']
        );
        
        $expected = [
            'outputPath' => $this->testOutputPath,
            'title' => 'Test Video',
            'videoId' => 'abc123',
            'format' => 'mp3',
            'size' => 1024,
            'duration' => 180.5,
            'thumbnailUrl' => 'https://example.com/thumb.jpg',
            'uploader' => 'Test Channel',
            'uploadDate' => '2024-01-15',
            'availableFormats' => ['mp3', 'aac']
        ];
        
        $this->assertEquals($expected, $result->toArray());
    }
    
    public function testFromArray(): void
    {
        $data = [
            'outputPath' => $this->testOutputPath,
            'title' => 'Test Video',
            'videoId' => 'abc123',
            'format' => 'mp3',
            'size' => 1024,
            'duration' => 180.5,
            'thumbnailUrl' => 'https://example.com/thumb.jpg',
            'uploader' => 'Test Channel',
            'uploadDate' => '2024-01-15',
            'availableFormats' => ['mp3', 'aac']
        ];
        
        $result = ConversionResult::fromArray($data);
        
        $this->assertEquals($this->testOutputPath, $result->getOutputPath());
        $this->assertEquals('Test Video', $result->getTitle());
        $this->assertEquals('abc123', $result->getVideoId());
        $this->assertEquals('mp3', $result->getFormat());
        $this->assertEquals(1024, $result->getSize());
        $this->assertEquals(180.5, $result->getDuration());
        $this->assertEquals('https://example.com/thumb.jpg', $result->getThumbnailUrl());
        $this->assertEquals('Test Channel', $result->getUploader());
        $this->assertEquals('2024-01-15', $result->getUploadDate());
        $this->assertEquals(['mp3', 'aac'], $result->getAvailableFormats());
    }
    
    public function testFromArrayWithSnakeCaseKeys(): void
    {
        $data = [
            'output_path' => $this->testOutputPath,
            'title' => 'Test Video',
            'video_id' => 'abc123',
            'format' => 'mp3',
            'size' => 1024,
            'duration' => 180.5,
            'thumbnail_url' => 'https://example.com/thumb.jpg',
            'uploader' => 'Test Channel',
            'upload_date' => '2024-01-15',
            'available_formats' => ['mp3', 'aac']
        ];
        
        $result = ConversionResult::fromArray($data);
        
        $this->assertEquals($this->testOutputPath, $result->getOutputPath());
        $this->assertEquals('Test Video', $result->getTitle());
        $this->assertEquals('abc123', $result->getVideoId());
        $this->assertEquals('mp3', $result->getFormat());
        $this->assertEquals(1024, $result->getSize());
        $this->assertEquals(180.5, $result->getDuration());
        $this->assertEquals('https://example.com/thumb.jpg', $result->getThumbnailUrl());
        $this->assertEquals('Test Channel', $result->getUploader());
        $this->assertEquals('2024-01-15', $result->getUploadDate());
        $this->assertEquals(['mp3', 'aac'], $result->getAvailableFormats());
    }
    
    public function testFromArrayWithMissingValues(): void
    {
        $data = [
            'title' => 'Test Video',
            'format' => 'mp3'
        ];
        
        $result = ConversionResult::fromArray($data);
        
        $this->assertEquals('', $result->getOutputPath());
        $this->assertEquals('Test Video', $result->getTitle());
        $this->assertEquals('', $result->getVideoId());
        $this->assertEquals('mp3', $result->getFormat());
        $this->assertEquals(0, $result->getSize());
        $this->assertEquals(0.0, $result->getDuration());
        $this->assertNull($result->getThumbnailUrl());
        $this->assertNull($result->getUploader());
        $this->assertNull($result->getUploadDate());
        $this->assertEquals([], $result->getAvailableFormats());
    }
}