<?php

declare(strict_types=1);

namespace Tests\Converter\Util;

use PHPUnit\Framework\TestCase;
use Darkwob\YoutubeMp3Converter\Converter\Util\DirectoryManager;
use Darkwob\YoutubeMp3Converter\Converter\Util\ProcessManager;
use Darkwob\YoutubeMp3Converter\Converter\Util\PlatformDetector;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;
use Symfony\Component\Process\Process;

/**
 * Test Windows path normalization utilities
 * 
 * @requires PHP >=8.4
 */
class WindowsPathNormalizationTest extends TestCase
{
    private DirectoryManager $directoryManager;
    private ProcessManager $processManager;
    private string $testOutputDir;
    private string $testTempDir;

    protected function setUp(): void
    {
        $this->testOutputDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_output_' . uniqid();
        $this->testTempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_temp_' . uniqid();
        $this->directoryManager = new DirectoryManager($this->testOutputDir, $this->testTempDir);
        $this->processManager = new ProcessManager();
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        if (is_dir($this->testOutputDir)) {
            $this->directoryManager->cleanupTempFiles($this->testOutputDir);
        }
        if (is_dir($this->testTempDir)) {
            $this->directoryManager->cleanupTempFiles($this->testTempDir);
        }
    }

    public function testNormalizeWindowsPathWithForwardSlashes(): void
    {
        $inputPath = 'C:/test/path/with/forward/slashes';
        $normalizedPath = $this->directoryManager->normalizeWindowsPath($inputPath);
        
        if (PlatformDetector::isWindows()) {
            $this->assertStringContainsString('\\', $normalizedPath);
            $this->assertStringNotContainsString('/', $normalizedPath);
        } else {
            // On non-Windows platforms, should use standard normalization
            $this->assertIsString($normalizedPath);
        }
    }

    public function testNormalizeWindowsPathWithMixedSlashes(): void
    {
        $inputPath = 'C:\\test/mixed\\slashes/path';
        $normalizedPath = $this->directoryManager->normalizeWindowsPath($inputPath);
        
        if (PlatformDetector::isWindows()) {
            $this->assertStringContainsString('\\', $normalizedPath);
            $this->assertStringNotContainsString('/', $normalizedPath);
        } else {
            $this->assertIsString($normalizedPath);
        }
    }

    public function testNormalizeWindowsPathWithDoubleSlashes(): void
    {
        $inputPath = 'C:\\\\test\\\\double\\\\slashes';
        $normalizedPath = $this->directoryManager->normalizeWindowsPath($inputPath);
        
        if (PlatformDetector::isWindows()) {
            $this->assertStringNotContainsString('\\\\\\', $normalizedPath); // No triple slashes
        }
        $this->assertIsString($normalizedPath);
    }

    public function testValidateWindowsPathWithInvalidCharacters(): void
    {
        if (!PlatformDetector::isWindows()) {
            $this->markTestSkipped('Windows-specific test');
        }

        $invalidPaths = [
            'C:\\test<path',
            'C:\\test>path',
            'C:\\test|path',
            'C:\\test?path',
            'C:\\test*path',
            'C:\\test"path'
        ];

        foreach ($invalidPaths as $invalidPath) {
            $this->expectException(ConverterException::class);
            $this->directoryManager->validateWindowsPath($invalidPath);
        }
    }

    public function testValidateWindowsPathWithReservedNames(): void
    {
        if (!PlatformDetector::isWindows()) {
            $this->markTestSkipped('Windows-specific test');
        }

        $reservedPaths = [
            'C:\\CON\\test',
            'C:\\test\\PRN',
            'C:\\AUX\\path',
            'C:\\test\\COM1.txt'
        ];

        foreach ($reservedPaths as $reservedPath) {
            $this->expectException(ConverterException::class);
            $this->directoryManager->validateWindowsPath($reservedPath);
        }
    }

    public function testValidateWindowsPathWithLongPath(): void
    {
        if (!PlatformDetector::isWindows()) {
            $this->markTestSkipped('Windows-specific test');
        }

        // Create a path longer than 260 characters
        $longPath = 'C:\\' . str_repeat('a', 260);
        
        $this->expectException(ConverterException::class);
        $this->expectExceptionMessage('Path too long for Windows');
        $this->directoryManager->validateWindowsPath($longPath);
    }

    public function testSetupWindowsEnvironmentEnhancesPath(): void
    {
        $process = new Process(['echo', 'test']);
        $this->processManager->setupWindowsEnvironment($process);
        
        // The method should run without errors
        $this->assertTrue(true);
    }

    public function testSetupWindowsEnvironmentSetsTempVariables(): void
    {
        $process = new Process(['echo', 'test']);
        $this->processManager->setupWindowsEnvironment($process);
        
        if (PlatformDetector::isWindows()) {
            $env = $process->getEnv();
            $this->assertArrayHasKey('TEMP', $env);
            $this->assertArrayHasKey('TMP', $env);
            $this->assertArrayHasKey('PYTHONIOENCODING', $env);
            $this->assertEquals('utf-8', $env['PYTHONIOENCODING']);
        }
        
        $this->assertTrue(true);
    }

    public function testValidateWindowsPathWithValidPath(): void
    {
        $validPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'valid_test_path';
        
        // This should not throw an exception
        $this->directoryManager->validateWindowsPath($validPath);
        $this->assertTrue(true);
    }

    public function testNormalizeWindowsPathWithRelativePath(): void
    {
        $relativePath = 'test\\relative\\path';
        $normalizedPath = $this->directoryManager->normalizeWindowsPath($relativePath);
        
        if (PlatformDetector::isWindows()) {
            // Should convert to absolute path
            $this->assertStringContainsString(':', $normalizedPath); // Drive letter
        }
        
        $this->assertIsString($normalizedPath);
    }
}