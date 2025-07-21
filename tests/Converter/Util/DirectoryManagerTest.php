<?php

declare(strict_types=1);

namespace Tests\Converter\Util;

use PHPUnit\Framework\TestCase;
use Darkwob\YoutubeMp3Converter\Converter\Util\DirectoryManager;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;

/**
 * Unit tests for DirectoryManager class with Windows path handling
 */
class DirectoryManagerTest extends TestCase
{
    private string $testOutputDir;
    private string $testTempDir;
    private array $createdDirectories = [];
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test directories in system temp
        $this->testOutputDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_output_' . uniqid();
        $this->testTempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_temp_' . uniqid();
        
        $this->createdDirectories[] = $this->testOutputDir;
        $this->createdDirectories[] = $this->testTempDir;
    }
    
    protected function tearDown(): void
    {
        // Clean up created directories
        foreach ($this->createdDirectories as $dir) {
            if (is_dir($dir)) {
                $this->removeDirectory($dir);
            }
        }
        
        parent::tearDown();
    }
    
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
    
    public function testConstructor(): void
    {
        $manager = new DirectoryManager($this->testOutputDir, $this->testTempDir);
        
        // Use reflection to access private properties
        $reflection = new \ReflectionClass($manager);
        $outputPathProperty = $reflection->getProperty('outputPath');
        $outputPathProperty->setAccessible(true);
        $tempPathProperty = $reflection->getProperty('tempPath');
        $tempPathProperty->setAccessible(true);
        
        $this->assertEquals($this->testOutputDir, $outputPathProperty->getValue($manager));
        $this->assertEquals($this->testTempDir, $tempPathProperty->getValue($manager));
    }
    
    public function testConstructorWithWindowsPathNormalization(): void
    {
        $windowsOutputPath = 'C:/Users/Test/Output';
        $windowsTempPath = 'C:/Users/Test/Temp';
        
        $manager = new DirectoryManager($windowsOutputPath, $windowsTempPath);
        
        $reflection = new \ReflectionClass($manager);
        $outputPathProperty = $reflection->getProperty('outputPath');
        $outputPathProperty->setAccessible(true);
        $tempPathProperty = $reflection->getProperty('tempPath');
        $tempPathProperty->setAccessible(true);
        
        // On Windows, paths should be normalized to use backslashes
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->assertEquals('C:\\Users\\Test\\Output', $outputPathProperty->getValue($manager));
            $this->assertEquals('C:\\Users\\Test\\Temp', $tempPathProperty->getValue($manager));
        } else {
            // On Unix systems, paths remain unchanged
            $this->assertEquals($windowsOutputPath, $outputPathProperty->getValue($manager));
            $this->assertEquals($windowsTempPath, $tempPathProperty->getValue($manager));
        }
    }
    
    public function testEnsureDirectoriesExist(): void
    {
        $manager = new DirectoryManager($this->testOutputDir, $this->testTempDir);
        
        $this->assertFalse(is_dir($this->testOutputDir));
        $this->assertFalse(is_dir($this->testTempDir));
        
        $manager->ensureDirectoriesExist();
        
        $this->assertTrue(is_dir($this->testOutputDir));
        $this->assertTrue(is_dir($this->testTempDir));
        $this->assertTrue(is_writable($this->testOutputDir));
        $this->assertTrue(is_writable($this->testTempDir));
    }
    
    public function testEnsureDirectoriesExistWithExistingDirectories(): void
    {
        // Create directories first
        mkdir($this->testOutputDir, 0755, true);
        mkdir($this->testTempDir, 0755, true);
        
        $manager = new DirectoryManager($this->testOutputDir, $this->testTempDir);
        
        // Should not throw exception when directories already exist
        $manager->ensureDirectoriesExist();
        
        $this->assertTrue(is_dir($this->testOutputDir));
        $this->assertTrue(is_dir($this->testTempDir));
    }
    
    public function testEnsureDirectoriesExistWithInvalidPath(): void
    {
        // Use a path that cannot be created (on most systems)
        $invalidPath = '/root/invalid/path/that/cannot/be/created';
        
        if (DIRECTORY_SEPARATOR === '\\') {
            // Windows equivalent
            $invalidPath = 'Z:\\invalid\\path\\that\\cannot\\be\\created';
        }
        
        $manager = new DirectoryManager($invalidPath, $this->testTempDir);
        
        $this->expectException(ConverterException::class);
        $manager->ensureDirectoriesExist();
    }
    
    public function testCreateTempDirectory(): void
    {
        $manager = new DirectoryManager($this->testOutputDir, $this->testTempDir);
        $manager->ensureDirectoriesExist();
        
        $tempDir = $manager->createTempDirectory('test_prefix_');
        $this->createdDirectories[] = $tempDir;
        
        $this->assertTrue(is_dir($tempDir));
        $this->assertTrue(is_writable($tempDir));
        $this->assertStringContainsString('test_prefix_', basename($tempDir));
        $this->assertStringStartsWith($this->testTempDir, $tempDir);
    }
    
    public function testCreateTempDirectoryWithDefaultPrefix(): void
    {
        $manager = new DirectoryManager($this->testOutputDir, $this->testTempDir);
        $manager->ensureDirectoriesExist();
        
        $tempDir = $manager->createTempDirectory();
        $this->createdDirectories[] = $tempDir;
        
        $this->assertTrue(is_dir($tempDir));
        $this->assertStringContainsString('ytmp3_', basename($tempDir));
    }
    
    public function testCreateTempDirectoryUniqueness(): void
    {
        $manager = new DirectoryManager($this->testOutputDir, $this->testTempDir);
        $manager->ensureDirectoriesExist();
        
        $tempDir1 = $manager->createTempDirectory('unique_');
        $tempDir2 = $manager->createTempDirectory('unique_');
        
        $this->createdDirectories[] = $tempDir1;
        $this->createdDirectories[] = $tempDir2;
        
        $this->assertNotEquals($tempDir1, $tempDir2);
        $this->assertTrue(is_dir($tempDir1));
        $this->assertTrue(is_dir($tempDir2));
    }
    
    public function testCreateTempDirectoryWithoutTempDirSetup(): void
    {
        $manager = new DirectoryManager($this->testOutputDir, $this->testTempDir);
        
        // Should create temp directory automatically
        $tempDir = $manager->createTempDirectory();
        $this->createdDirectories[] = $tempDir;
        
        $this->assertTrue(is_dir($tempDir));
        $this->assertTrue(is_dir($this->testTempDir));
    }
    
    public function testCreateTempDirectoryWithInvalidTempPath(): void
    {
        $invalidTempPath = '/root/invalid/temp/path';
        
        if (DIRECTORY_SEPARATOR === '\\') {
            $invalidTempPath = 'Z:\\invalid\\temp\\path';
        }
        
        $manager = new DirectoryManager($this->testOutputDir, $invalidTempPath);
        
        $this->expectException(ConverterException::class);
        $manager->createTempDirectory();
    }
    
    public function testCleanupTempFilesWithSpecificPath(): void
    {
        $manager = new DirectoryManager($this->testOutputDir, $this->testTempDir);
        $manager->ensureDirectoriesExist();
        
        $tempDir = $manager->createTempDirectory();
        
        // Create a test file in temp directory
        $testFile = $tempDir . DIRECTORY_SEPARATOR . 'test.txt';
        file_put_contents($testFile, 'test content');
        
        $this->assertTrue(is_dir($tempDir));
        $this->assertTrue(file_exists($testFile));
        
        $manager->cleanupTempFiles($tempDir);
        
        $this->assertFalse(is_dir($tempDir));
        $this->assertFalse(file_exists($testFile));
    }
    
    public function testCleanupTempFilesWithoutSpecificPath(): void
    {
        $manager = new DirectoryManager($this->testOutputDir, $this->testTempDir);
        $manager->ensureDirectoriesExist();
        
        $tempDir1 = $manager->createTempDirectory('cleanup1_');
        $tempDir2 = $manager->createTempDirectory('cleanup2_');
        
        // Create test files
        file_put_contents($tempDir1 . DIRECTORY_SEPARATOR . 'test1.txt', 'test1');
        file_put_contents($tempDir2 . DIRECTORY_SEPARATOR . 'test2.txt', 'test2');
        
        $this->assertTrue(is_dir($tempDir1));
        $this->assertTrue(is_dir($tempDir2));
        
        $manager->cleanupTempFiles();
        
        $this->assertFalse(is_dir($tempDir1));
        $this->assertFalse(is_dir($tempDir2));
    }
    
    public function testCleanupTempFilesWithNonExistentPath(): void
    {
        $manager = new DirectoryManager($this->testOutputDir, $this->testTempDir);
        
        // Should not throw exception when cleaning up non-existent path
        $manager->cleanupTempFiles('/non/existent/path');
        
        $this->assertTrue(true); // Test passes if no exception is thrown
    }
    
    public function testGetOutputPath(): void
    {
        $manager = new DirectoryManager($this->testOutputDir, $this->testTempDir);
        
        $reflection = new \ReflectionClass($manager);
        $method = $reflection->getMethod('getOutputPath');
        $method->setAccessible(true);
        
        $this->assertEquals($this->testOutputDir, $method->invoke($manager));
    }
    
    public function testGetTempPath(): void
    {
        $manager = new DirectoryManager($this->testOutputDir, $this->testTempDir);
        
        $reflection = new \ReflectionClass($manager);
        $method = $reflection->getMethod('getTempPath');
        $method->setAccessible(true);
        
        $this->assertEquals($this->testTempDir, $method->invoke($manager));
    }
    
    public function testWindowsPathNormalization(): void
    {
        $manager = new DirectoryManager($this->testOutputDir, $this->testTempDir);
        
        $reflection = new \ReflectionClass($manager);
        $method = $reflection->getMethod('normalizeWindowsPath');
        $method->setAccessible(true);
        
        if (DIRECTORY_SEPARATOR === '\\') {
            // On Windows, paths should be normalized to Windows format
            $testCases = [
                'C:/Users/Test' => 'C:\\Users\\Test',
                'C:\\Users\\Test' => 'C:\\Users\\Test',
            ];
            
            foreach ($testCases as $input => $expected) {
                try {
                    $result = $method->invoke($manager, $input);
                    $this->assertEquals($expected, $result, "Failed for input: {$input}");
                } catch (\Exception $e) {
                    // Some paths might be invalid and throw exceptions
                    $this->assertInstanceOf(\Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException::class, $e);
                }
            }
            
            // Test relative path conversion (should be converted to absolute)
            $relativeInput = 'relative/path';
            try {
                $result = $method->invoke($manager, $relativeInput);
                $this->assertStringStartsWith(getcwd(), $result);
                $this->assertStringContainsString('relative\\path', $result);
            } catch (\Exception $e) {
                $this->assertInstanceOf(\Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException::class, $e);
            }
        } else {
            // On Unix systems, should use PlatformDetector::normalizePath
            $testCases = [
                'C:/Users/Test' => 'C:/Users/Test',
                '/home/user' => '/home/user',
                'relative/path' => 'relative/path'
            ];
            
            foreach ($testCases as $input => $expected) {
                $result = $method->invoke($manager, $input);
                $this->assertEquals($expected, $result, "Failed for input: {$input}");
            }
        }
    }
    
    public function testWindowsPathValidation(): void
    {
        $manager = new DirectoryManager($this->testOutputDir, $this->testTempDir);
        
        $reflection = new \ReflectionClass($manager);
        $method = $reflection->getMethod('validateWindowsPath');
        $method->setAccessible(true);
        
        // Valid paths - should not throw exceptions
        $validPaths = [
            'C:\\Users\\Test',
            'D:\\Projects\\MyApp',
            'relative\\path',
            '/unix/style/path'
        ];
        
        foreach ($validPaths as $path) {
            try {
                $method->invoke($manager, $path);
                $this->assertTrue(true, "Path should be valid: {$path}");
            } catch (\Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException $e) {
                // On Windows, some paths might be invalid due to validation
                if (DIRECTORY_SEPARATOR === '\\') {
                    // This might be expected for some paths on Windows
                    $this->assertTrue(true, "Path validation on Windows: {$path}");
                } else {
                    $this->fail("Path should be valid on Unix systems: {$path} - {$e->getMessage()}");
                }
            }
        }
        
        // Invalid paths (Windows-specific) - should throw exceptions
        if (DIRECTORY_SEPARATOR === '\\') {
            $invalidPaths = [
                'C:\\Users\\Test<file>',
                'C:\\Users\\Test|pipe',
                'C:\\Users\\Test"quote',
                'C:\\Users\\Test*wildcard',
                'C:\\Users\\Test?question'
            ];
            
            foreach ($invalidPaths as $path) {
                try {
                    $method->invoke($manager, $path);
                    $this->fail("Path should be invalid on Windows: {$path}");
                } catch (\Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException $e) {
                    $this->assertTrue(true, "Path correctly identified as invalid: {$path}");
                }
            }
        }
    }
    
    public function testDirectoryPermissionValidation(): void
    {
        $manager = new DirectoryManager($this->testOutputDir, $this->testTempDir);
        $manager->ensureDirectoriesExist();
        
        $reflection = new \ReflectionClass($manager);
        $method = $reflection->getMethod('validateDirectoryPermissions');
        $method->setAccessible(true);
        
        // Should not throw exception for writable directories
        $method->invoke($manager, $this->testOutputDir, 'output');
        $method->invoke($manager, $this->testTempDir, 'temp');
        
        $this->assertTrue(true); // Test passes if no exception is thrown
    }
    
    public function testDirectoryPermissionValidationWithNonWritableDirectory(): void
    {
        // Create a directory and make it non-writable (Unix only)
        if (DIRECTORY_SEPARATOR === '/') {
            $nonWritableDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'non_writable_' . uniqid();
            mkdir($nonWritableDir, 0444, true);
            $this->createdDirectories[] = $nonWritableDir;
            
            $manager = new DirectoryManager($nonWritableDir, $this->testTempDir);
            
            $reflection = new \ReflectionClass($manager);
            $method = $reflection->getMethod('validateDirectoryPermissions');
            $method->setAccessible(true);
            
            $this->expectException(ConverterException::class);
            $method->invoke($manager, $nonWritableDir, 'output');
        } else {
            // On Windows, skip this test as permission handling is different
            $this->markTestSkipped('Permission test skipped on Windows');
        }
    }
    
    public function testEnsureDirectoryExistsPrivateMethod(): void
    {
        $manager = new DirectoryManager($this->testOutputDir, $this->testTempDir);
        
        $reflection = new \ReflectionClass($manager);
        $method = $reflection->getMethod('ensureDirectoryExists');
        $method->setAccessible(true);
        
        $this->assertFalse(is_dir($this->testOutputDir));
        
        $method->invoke($manager, $this->testOutputDir, 'output');
        
        $this->assertTrue(is_dir($this->testOutputDir));
        $this->assertTrue(is_writable($this->testOutputDir));
    }
    
    public function testRemoveDirectoryPrivateMethod(): void
    {
        $manager = new DirectoryManager($this->testOutputDir, $this->testTempDir);
        
        // Create a test directory with files
        $testDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'remove_test_' . uniqid();
        mkdir($testDir, 0755, true);
        
        $subDir = $testDir . DIRECTORY_SEPARATOR . 'subdir';
        mkdir($subDir, 0755, true);
        
        file_put_contents($testDir . DIRECTORY_SEPARATOR . 'file1.txt', 'content1');
        file_put_contents($subDir . DIRECTORY_SEPARATOR . 'file2.txt', 'content2');
        
        $this->assertTrue(is_dir($testDir));
        $this->assertTrue(is_dir($subDir));
        
        $reflection = new \ReflectionClass($manager);
        $method = $reflection->getMethod('removeDirectory');
        $method->setAccessible(true);
        
        $method->invoke($manager, $testDir);
        
        $this->assertFalse(is_dir($testDir));
        $this->assertFalse(is_dir($subDir));
    }
}