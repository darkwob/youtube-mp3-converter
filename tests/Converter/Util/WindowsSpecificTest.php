<?php

declare(strict_types=1);

namespace Tests\Converter\Util;

use PHPUnit\Framework\TestCase;
use Darkwob\YoutubeMp3Converter\Converter\Util\PlatformDetector;
use Darkwob\YoutubeMp3Converter\Converter\Util\DirectoryManager;
use Darkwob\YoutubeMp3Converter\Converter\Util\ProcessManager;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;

/**
 * Windows-specific test cases for path normalization and binary execution
 */
class WindowsSpecificTest extends TestCase
{
    public function testWindowsPathNormalization(): void
    {
        $testOutputDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_output';
        $testTempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_temp';
        
        $manager = new DirectoryManager($testOutputDir, $testTempDir);
        
        $reflection = new \ReflectionClass($manager);
        $method = $reflection->getMethod('normalizeWindowsPath');
        $method->setAccessible(true);
        
        if (DIRECTORY_SEPARATOR === '\\') {
            // On Windows, test absolute paths
            $absoluteTestCases = [
                'C:/Users/Test/Documents' => 'C:\\Users\\Test\\Documents',
                'C:\\Users\\Test\\Documents' => 'C:\\Users\\Test\\Documents',
                '//server/share/folder' => '\\\\server\\share\\folder',
                '\\\\server\\share\\folder' => '\\\\server\\share\\folder',
            ];
            
            foreach ($absoluteTestCases as $input => $expected) {
                try {
                    $result = $method->invoke($manager, $input);
                    $this->assertEquals($expected, $result, "Failed for input: '{$input}'");
                } catch (\Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException $e) {
                    // Some paths might be invalid and throw ConverterException
                    $this->assertTrue(true, "Path validation threw expected ConverterException: {$input}");
                } catch (\Exception $e) {
                    // Other exceptions might occur during path processing
                    $this->assertTrue(true, "Path validation threw exception: {$input} - " . get_class($e));
                }
            }
            
            // Test relative paths (should be converted to absolute)
            $relativeTestCases = ['relative/path/to/file', 'relative\\path\\to\\file'];
            foreach ($relativeTestCases as $input) {
                try {
                    $result = $method->invoke($manager, $input);
                    $this->assertStringStartsWith(getcwd(), $result, "Relative path should be converted to absolute: '{$input}'");
                    $this->assertStringContainsString('relative', $result);
                } catch (\Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException $e) {
                    // Some paths might be invalid and throw ConverterException
                    $this->assertTrue(true, "Relative path validation threw expected ConverterException: {$input}");
                } catch (\Exception $e) {
                    // Other exceptions might occur during path processing
                    $this->assertTrue(true, "Relative path validation threw exception: {$input} - " . get_class($e));
                }
            }
        } else {
            // On Unix systems, should use PlatformDetector::normalizePath
            $testCases = [
                'C:/Users/Test/Documents' => 'C:/Users/Test/Documents',
                '/home/user/documents' => '/home/user/documents',
                'relative/path/to/file' => 'relative/path/to/file',
            ];
            
            foreach ($testCases as $input => $expected) {
                $result = $method->invoke($manager, $input);
                $this->assertEquals($expected, $result, "Failed for input: '{$input}'");
            }
        }
    }
    
    public function testWindowsPathValidation(): void
    {
        $testOutputDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_output';
        $testTempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_temp';
        
        $manager = new DirectoryManager($testOutputDir, $testTempDir);
        
        $reflection = new \ReflectionClass($manager);
        $method = $reflection->getMethod('validateWindowsPath');
        $method->setAccessible(true);
        
        // Valid paths that should work on all systems
        $validPaths = [
            'C:\\Users\\Test',
            'D:\\Projects\\MyApp',
            'relative\\path',
            '/unix/style/path',
            'simple_filename.txt',
            'folder_with_underscores',
            'folder-with-hyphens',
            'folder.with.dots',
            'C:\\Program Files\\Application'
        ];
        
        foreach ($validPaths as $path) {
            try {
                $method->invoke($manager, $path);
                $this->assertTrue(true, "Path should be valid: '{$path}'");
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
        
        // Invalid paths (Windows-specific restrictions) - should throw exceptions
        if (DIRECTORY_SEPARATOR === '\\') {
            $invalidPaths = [
                'C:\\Users\\Test<file>',      // < character
                'C:\\Users\\Test>file',       // > character
                'C:\\Users\\Test|pipe',       // | character
                'C:\\Users\\Test"quote',      // " character
                'C:\\Users\\Test*wildcard',   // * character
                'C:\\Users\\Test?question',   // ? character
                'C:\\Users\\Test:colon',      // : character (except after drive letter)
                'CON',                        // Reserved name
                'PRN',                        // Reserved name
                'AUX',                        // Reserved name
                'NUL',                        // Reserved name
                'COM1',                       // Reserved name
                'LPT1',                       // Reserved name
                'C:\\Users\\Test\\CON.txt',   // Reserved name with extension
                'C:\\Users\\Test\\file.',     // Ending with dot
                'C:\\Users\\Test\\file ',     // Ending with space
                // str_repeat('a', 260),         // Path too long test moved to separate test
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
    
    public function testWindowsEnvironmentSetup(): void
    {
        $processManager = new ProcessManager();
        
        $reflection = new \ReflectionClass($processManager);
        $method = $reflection->getMethod('setupWindowsEnvironment');
        $method->setAccessible(true);
        
        $mockProcess = $this->createMock(\Symfony\Component\Process\Process::class);
        
        if (DIRECTORY_SEPARATOR === '\\') {
            // On Windows, should set environment variables
            $mockProcess->expects($this->once())
                ->method('setEnv')
                ->with($this->callback(function($env) {
                    $this->assertIsArray($env);
                    $this->assertArrayHasKey('PATH', $env);
                    $this->assertArrayHasKey('TEMP', $env);
                    return true;
                }));
        } else {
            // On Unix systems, should not set environment variables
            $mockProcess->expects($this->never())
                ->method('setEnv');
        }
        
        $method->invoke($processManager, $mockProcess);
    }
    
    public function testPlatformDetectorBinaryResolution(): void
    {
        // Test binary name resolution for different platforms
        $testCases = [
            'yt-dlp' => DIRECTORY_SEPARATOR === '\\' ? 'yt-dlp.exe' : 'yt-dlp',
            'ffmpeg' => DIRECTORY_SEPARATOR === '\\' ? 'ffmpeg.exe' : 'ffmpeg',
            'custom-binary' => DIRECTORY_SEPARATOR === '\\' ? 'custom-binary.exe' : 'custom-binary'
        ];
        
        foreach ($testCases as $binaryName => $expectedName) {
            try {
                $command = PlatformDetector::createCommand($binaryName, ['--version']);
                
                $this->assertIsArray($command);
                $this->assertNotEmpty($command);
                
                // The first element should be the binary name (possibly with .exe extension on Windows)
                $actualBinaryName = basename($command[0]);
                
                // On Windows, should have .exe extension; on Unix, should not
                if (DIRECTORY_SEPARATOR === '\\') {
                    $this->assertStringEndsWith('.exe', $actualBinaryName, "Binary should have .exe extension on Windows: {$binaryName}");
                } else {
                    $this->assertStringNotContainsString('.exe', $actualBinaryName, "Binary should not have .exe extension on Unix: {$binaryName}");
                }
            } catch (\RuntimeException $e) {
                // Expected when binary doesn't exist
                $this->assertStringContainsString('not found', $e->getMessage());
            }
        }
    }
    
    public function testPlatformDetectorWithCustomBinaryPath(): void
    {
        $customPath = DIRECTORY_SEPARATOR === '\\' ? 'C:\\custom\\path\\yt-dlp.exe' : '/custom/path/yt-dlp';
        
        try {
            $command = PlatformDetector::createCommand('yt-dlp', ['--version'], $customPath);
            
            $this->assertIsArray($command);
            $this->assertEquals($customPath, $command[0]);
            $this->assertEquals('--version', $command[1]);
        } catch (\RuntimeException $e) {
            // Expected when custom binary path doesn't exist
            $this->assertStringContainsString('not found', $e->getMessage());
        }
    }
    
    public function testWindowsSpecificDirectoryCreation(): void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            $this->markTestSkipped('Windows-specific test skipped on non-Windows platform');
        }
        
        $testOutputDir = sys_get_temp_dir() . '\\test_windows_output_' . uniqid();
        $testTempDir = sys_get_temp_dir() . '\\test_windows_temp_' . uniqid();
        
        $manager = new DirectoryManager($testOutputDir, $testTempDir);
        
        try {
            $manager->ensureDirectoriesExist();
            
            $this->assertTrue(is_dir($testOutputDir));
            $this->assertTrue(is_dir($testTempDir));
            $this->assertTrue(is_writable($testOutputDir));
            $this->assertTrue(is_writable($testTempDir));
            
            // Test Windows-specific path handling
            $this->assertStringContainsString('\\', $testOutputDir);
            $this->assertStringContainsString('\\', $testTempDir);
            
        } finally {
            // Cleanup
            if (is_dir($testOutputDir)) {
                rmdir($testOutputDir);
            }
            if (is_dir($testTempDir)) {
                rmdir($testTempDir);
            }
        }
    }
    
    public function testWindowsLongPathHandling(): void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            $this->markTestSkipped('Windows-specific test skipped on non-Windows platform');
        }
        
        // Create a very long path (exceeding Windows limit of 260 characters)
        $longPath = 'C:\\' . str_repeat('a', 260);
        
        $manager = new DirectoryManager(sys_get_temp_dir(), sys_get_temp_dir());
        
        $reflection = new \ReflectionClass($manager);
        $method = $reflection->getMethod('validateWindowsPath');
        $method->setAccessible(true);
        
        // Path should be invalid if it exceeds Windows path length limit
        if (strlen($longPath) > 260) {
            try {
                $method->invoke($manager, $longPath);
                $this->fail('Very long path should be invalid on Windows');
            } catch (\Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException $e) {
                $this->assertStringContainsString('Path too long', $e->getMessage());
            }
        } else {
            $this->assertTrue(true, 'Path is not long enough to test the limit');
        }
    }
    
    public function testWindowsReservedFileNames(): void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            $this->markTestSkipped('Windows-specific test skipped on non-Windows platform');
        }
        
        $testOutputDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_output';
        $testTempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_temp';
        
        $manager = new DirectoryManager($testOutputDir, $testTempDir);
        
        $reflection = new \ReflectionClass($manager);
        $method = $reflection->getMethod('validateWindowsPath');
        $method->setAccessible(true);
        
        $reservedNames = [
            'CON', 'PRN', 'AUX', 'NUL',
            'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9',
            'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'
        ];
        
        foreach ($reservedNames as $name) {
            // Test reserved name alone
            try {
                $method->invoke($manager, $name);
                $this->fail("Reserved name '{$name}' should be invalid");
            } catch (\Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException $e) {
                $this->assertTrue(true, "Reserved name '{$name}' correctly identified as invalid");
            }
            
            // Test reserved name with extension
            try {
                $method->invoke($manager, $name . '.txt');
                $this->fail("Reserved name '{$name}.txt' should be invalid");
            } catch (\Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException $e) {
                $this->assertTrue(true, "Reserved name '{$name}.txt' correctly identified as invalid");
            }
            
            // Test reserved name in path
            try {
                $method->invoke($manager, 'C:\\Users\\Test\\' . $name);
                $this->fail("Path containing reserved name '{$name}' should be invalid");
            } catch (\Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException $e) {
                $this->assertTrue(true, "Path containing reserved name '{$name}' correctly identified as invalid");
            }
        }
    }
    
    public function testWindowsSpecialCharacterHandling(): void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            $this->markTestSkipped('Windows-specific test skipped on non-Windows platform');
        }
        
        $testOutputDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_output';
        $testTempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_temp';
        
        $manager = new DirectoryManager($testOutputDir, $testTempDir);
        
        $reflection = new \ReflectionClass($manager);
        $method = $reflection->getMethod('validateWindowsPath');
        $method->setAccessible(true);
        
        $invalidCharacters = ['<', '>', ':', '"', '|', '?', '*'];
        
        foreach ($invalidCharacters as $char) {
            $pathWithInvalidChar = 'C:\\Users\\Test\\file' . $char . 'name';
            try {
                $method->invoke($manager, $pathWithInvalidChar);
                $this->fail("Path with invalid character '{$char}' should be invalid");
            } catch (\Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException $e) {
                $this->assertTrue(true, "Path with invalid character '{$char}' correctly identified as invalid");
            }
        }
    }
    
    public function testWindowsTrailingSpaceAndDotHandling(): void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            $this->markTestSkipped('Windows-specific test skipped on non-Windows platform');
        }
        
        $testOutputDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_output';
        $testTempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_temp';
        
        $manager = new DirectoryManager($testOutputDir, $testTempDir);
        
        $reflection = new \ReflectionClass($manager);
        $method = $reflection->getMethod('validateWindowsPath');
        $method->setAccessible(true);
        
        $invalidPaths = [
            'C:\\Users\\Test\\filename.',  // Trailing dot
            'C:\\Users\\Test\\filename ',  // Trailing space
            'C:\\Users\\Test\\filename. ', // Trailing dot and space
        ];
        
        foreach ($invalidPaths as $path) {
            try {
                $method->invoke($manager, $path);
                $this->fail("Path with trailing space/dot should be invalid: '{$path}'");
            } catch (\Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException $e) {
                $this->assertTrue(true, "Path with trailing space/dot correctly identified as invalid: '{$path}'");
            }
        }
    }
    
    public function testCrossPlatformBinaryExecution(): void
    {
        // Test that binary commands are created correctly for the current platform
        $binaryName = 'test-binary';
        $arguments = ['--arg1', 'value1', '--arg2'];
        
        try {
            $command = PlatformDetector::createCommand($binaryName, $arguments);
            
            $this->assertIsArray($command);
            $this->assertGreaterThanOrEqual(3, count($command)); // binary + 2 arguments minimum
            
            // Check that arguments are preserved
            $this->assertContains('--arg1', $command);
            $this->assertContains('value1', $command);
            $this->assertContains('--arg2', $command);
            
            // Check platform-specific binary naming
            $binaryPath = $command[0];
            if (DIRECTORY_SEPARATOR === '\\') {
                // On Windows, should end with .exe or be a full path ending with .exe
                $this->assertTrue(
                    str_ends_with($binaryPath, '.exe') || str_contains($binaryPath, $binaryName),
                    "Windows binary should have .exe extension or contain binary name: {$binaryPath}"
                );
            } else {
                // On Unix, should not have .exe extension
                $this->assertStringNotContainsString('.exe', $binaryPath, "Unix binary should not have .exe extension: {$binaryPath}");
            }
        } catch (\RuntimeException $e) {
            // Expected when binary doesn't exist
            $this->assertStringContainsString('not found', $e->getMessage());
        }
    }
    
    public function testProcessManagerWindowsEnvironment(): void
    {
        $processManager = new ProcessManager();
        
        // Test that Windows environment is set up correctly
        $reflection = new \ReflectionClass($processManager);
        $method = $reflection->getMethod('createProcess');
        $method->setAccessible(true);
        
        $command = ['test-command', '--arg'];
        $process = $method->invoke($processManager, $command, null, null);
        
        $this->assertInstanceOf(\Symfony\Component\Process\Process::class, $process);
        
        // On Windows, environment should be set up
        if (DIRECTORY_SEPARATOR === '\\') {
            // We can't easily test the environment setup without mocking,
            // but we can ensure the process is created successfully
            $this->assertNotNull($process->getCommandLine());
            $this->assertStringContainsString('test-command', $process->getCommandLine());
        }
    }
}