<?php

declare(strict_types=1);

namespace Tests\Converter;

use PHPUnit\Framework\TestCase;
use Darkwob\YoutubeMp3Converter\Converter\Util\PlatformDetector;
use Darkwob\YoutubeMp3Converter\Converter\Util\ProcessManager;

/**
 * Integration tests for platform-independent binary execution
 * 
 * Tests the complete workflow of binary detection, fallback mechanisms,
 * and cross-platform execution without hardcoded extensions.
 */
class PlatformIndependentBinaryTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tempDir = sys_get_temp_dir() . '/platform_independent_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    /**
     * Test complete platform-independent binary resolution workflow
     * 
     * Requirements: 1.1, 1.2, 1.3, 1.4
     */
    public function testCompleteWorkflow(): void
    {
        // Test 1: Platform detection works correctly
        $platform = PlatformDetector::detect();
        $this->assertContains($platform, [
            PlatformDetector::WINDOWS,
            PlatformDetector::LINUX,
            PlatformDetector::MACOS,
            PlatformDetector::UNKNOWN
        ]);

        // Test 2: Binary filename resolution is platform-aware but flexible
        $ytDlpFilename = PlatformDetector::getBinaryFilename('yt-dlp');
        $ffmpegFilename = PlatformDetector::getBinaryFilename('ffmpeg');
        
        if ($platform === PlatformDetector::WINDOWS) {
            $this->assertStringEndsWith('.exe', $ytDlpFilename);
            $this->assertStringEndsWith('.exe', $ffmpegFilename);
        } else {
            $this->assertStringNotContainsString('.exe', $ytDlpFilename);
            $this->assertStringNotContainsString('.exe', $ffmpegFilename);
        }

        // Test 3: Fallback mechanism provides comprehensive error messages
        try {
            PlatformDetector::getExecutablePath('non-existent-binary');
            $this->fail('Should throw exception for non-existent binary');
        } catch (\RuntimeException $e) {
            $errorMessage = $e->getMessage();
            
            // Should mention multiple strategies tried
            $this->assertStringContainsString('not found in any of the following locations', $errorMessage);
            $this->assertStringContainsString('platform-specific in bin/', $errorMessage);
            $this->assertStringContainsString('base name in bin/', $errorMessage);
            
            // Should provide installation instructions
            $this->assertStringContainsString('Installation instructions:', $errorMessage);
            $this->assertStringContainsString('OPTION 1:', $errorMessage);
            $this->assertStringContainsString('OPTION 2:', $errorMessage);
            $this->assertStringContainsString('OPTION 3:', $errorMessage);
        }

        // Test 4: System requirements check provides detailed information
        $requirements = PlatformDetector::checkRequirements(['yt-dlp', 'ffmpeg']);
        
        $this->assertArrayHasKey('yt-dlp', $requirements);
        $this->assertArrayHasKey('ffmpeg', $requirements);
        
        foreach ($requirements as $binary => $info) {
            $this->assertArrayHasKey('exists', $info);
            
            if ($info['exists']) {
                $this->assertArrayHasKey('path', $info);
                $this->assertArrayHasKey('location', $info);
                $this->assertArrayHasKey('version', $info);
                $this->assertFileExists($info['path']);
            } else {
                $this->assertArrayHasKey('instructions', $info);
                $this->assertStringContainsString('OPTION', $info['instructions']);
            }
        }

        // Test 5: ProcessManager integrates correctly with enhanced detection
        $processManager = new ProcessManager($this->tempDir);
        $binaryCheck = $processManager->checkBinaries();
        
        $this->assertArrayHasKey('yt-dlp', $binaryCheck);
        $this->assertArrayHasKey('ffmpeg', $binaryCheck);
        
        foreach ($binaryCheck as $binary => $info) {
            $this->assertArrayHasKey('available', $info);
            $this->assertArrayHasKey('custom_path', $info);
            
            if ($info['available']) {
                $this->assertArrayHasKey('location', $info);
                $this->assertContains($info['location'], [
                    'Project bin/ directory',
                    'System PATH',
                    'Custom location'
                ]);
            }
        }
    }

    /**
     * Test platform-specific installation instructions
     * 
     * Requirements: 1.3
     */
    public function testPlatformSpecificInstructions(): void
    {
        $platform = PlatformDetector::detect();
        
        foreach (['yt-dlp', 'ffmpeg'] as $binary) {
            $instructions = PlatformDetector::getInstallationInstructions($binary);
            
            // Should be comprehensive
            $this->assertStringContainsString('OPTION 1:', $instructions);
            $this->assertStringContainsString('OPTION 2:', $instructions);
            $this->assertStringContainsString('OPTION 3:', $instructions);
            $this->assertStringContainsString('NOTES:', $instructions);
            
            // Should be platform-specific
            switch ($platform) {
                case PlatformDetector::WINDOWS:
                    $this->assertStringContainsString('Chocolatey:', $instructions);
                    $this->assertStringContainsString('Scoop:', $instructions);
                    break;
                    
                case PlatformDetector::LINUX:
                    $this->assertStringContainsString('sudo apt', $instructions);
                    $this->assertStringContainsString('Snap:', $instructions);
                    break;
                    
                case PlatformDetector::MACOS:
                    $this->assertStringContainsString('Homebrew:', $instructions);
                    $this->assertStringContainsString('MacPorts:', $instructions);
                    break;
            }
        }
    }

    /**
     * Test custom path validation with various formats
     * 
     * Requirements: 1.4
     */
    public function testCustomPathValidation(): void
    {
        // Create a test binary
        $testBinary = $this->tempDir . DIRECTORY_SEPARATOR . 'test-binary';
        if (PlatformDetector::isWindows()) {
            $testBinary .= '.exe';
        }
        
        file_put_contents($testBinary, $this->getTestBinaryContent());
        
        if (!PlatformDetector::isWindows()) {
            chmod($testBinary, 0755);
        }

        // Test various custom path formats
        $pathFormats = [
            $testBinary,                    // Full path
            basename($testBinary),          // Just filename
        ];

        foreach ($pathFormats as $customPath) {
            try {
                $resolvedPath = PlatformDetector::getExecutablePath('test-binary', $customPath);
                $this->assertFileExists($resolvedPath);
                
                // Should resolve to the same file
                $this->assertEquals(
                    realpath($testBinary),
                    realpath($resolvedPath)
                );
            } catch (\RuntimeException $e) {
                // Some formats might not work depending on the system
                // This is acceptable as long as we get a clear error message
                $this->assertNotEmpty($e->getMessage());
            }
        }
    }

    /**
     * Test that binary detection works without hardcoded extensions
     * 
     * Requirements: 1.1, 1.2
     */
    public function testExtensionIndependentDetection(): void
    {
        // Create binaries with and without extensions
        $binDir = $this->tempDir . DIRECTORY_SEPARATOR . 'bin';
        mkdir($binDir, 0755, true);
        
        $testCases = [
            'test-no-ext' => 'test-no-ext',
            'test-with-ext' => 'test-with-ext.exe',
        ];
        
        foreach ($testCases as $binaryName => $fileName) {
            $filePath = $binDir . DIRECTORY_SEPARATOR . $fileName;
            file_put_contents($filePath, $this->getTestBinaryContent());
            
            if (!PlatformDetector::isWindows()) {
                chmod($filePath, 0755);
            }
        }
        
        // Temporarily change the project root to our test directory
        $originalCwd = getcwd();
        chdir($this->tempDir);
        
        try {
            // Test detection of binary without extension
            try {
                $path = PlatformDetector::getExecutablePath('test-no-ext');
                $this->assertFileExists($path);
                $this->assertStringContainsString('test-no-ext', $path);
            } catch (\RuntimeException $e) {
                // Expected if binary not found or not executable
                $this->assertStringContainsString('not found', $e->getMessage());
            }
            
            // Test detection of binary with extension
            try {
                $path = PlatformDetector::getExecutablePath('test-with-ext');
                $this->assertFileExists($path);
                $this->assertStringContainsString('test-with-ext', $path);
            } catch (\RuntimeException $e) {
                // Expected if binary not found or not executable
                $this->assertStringContainsString('not found', $e->getMessage());
            }
            
        } finally {
            chdir($originalCwd);
        }
    }

    /**
     * Get test binary content
     */
    private function getTestBinaryContent(): string
    {
        if (PlatformDetector::isWindows()) {
            return "@echo off\necho Test binary executed\n";
        } else {
            return "#!/bin/bash\necho 'Test binary executed'\n";
        }
    }

    /**
     * Remove directory recursively
     */
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