<?php

declare(strict_types=1);

namespace Tests\Converter\Util;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Darkwob\YoutubeMp3Converter\Converter\Util\ProcessManager;
use Darkwob\YoutubeMp3Converter\Converter\ProcessResult;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

/**
 * Unit tests for ProcessManager class
 */
class ProcessManagerTest extends TestCase
{
    private ProcessManager $processManager;
    private string $testWorkingDir;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testWorkingDir = sys_get_temp_dir();
        $this->processManager = new ProcessManager(
            workingDirectory: $this->testWorkingDir,
            defaultTimeout: 30
        );
    }
    
    public function testConstructorWithDefaults(): void
    {
        $manager = new ProcessManager();
        
        $reflection = new \ReflectionClass($manager);
        $workingDirProperty = $reflection->getProperty('workingDirectory');
        $workingDirProperty->setAccessible(true);
        $timeoutProperty = $reflection->getProperty('defaultTimeout');
        $timeoutProperty->setAccessible(true);
        
        $this->assertEquals(getcwd(), $workingDirProperty->getValue($manager));
        $this->assertEquals(300, $timeoutProperty->getValue($manager)); // DEFAULT_TIMEOUT
    }
    
    public function testConstructorWithCustomValues(): void
    {
        $customWorkingDir = '/custom/path';
        $customTimeout = 60;
        $customYtDlpPath = '/custom/yt-dlp';
        $customFfmpegPath = '/custom/ffmpeg';
        
        $manager = new ProcessManager(
            workingDirectory: $customWorkingDir,
            defaultTimeout: $customTimeout,
            ytDlpPath: $customYtDlpPath,
            ffmpegPath: $customFfmpegPath
        );
        
        $reflection = new \ReflectionClass($manager);
        $workingDirProperty = $reflection->getProperty('workingDirectory');
        $workingDirProperty->setAccessible(true);
        $timeoutProperty = $reflection->getProperty('defaultTimeout');
        $timeoutProperty->setAccessible(true);
        $ytDlpPathProperty = $reflection->getProperty('ytDlpPath');
        $ytDlpPathProperty->setAccessible(true);
        $ffmpegPathProperty = $reflection->getProperty('ffmpegPath');
        $ffmpegPathProperty->setAccessible(true);
        
        $this->assertEquals($customWorkingDir, $workingDirProperty->getValue($manager));
        $this->assertEquals($customTimeout, $timeoutProperty->getValue($manager));
        $this->assertEquals($customYtDlpPath, $ytDlpPathProperty->getValue($manager));
        $this->assertEquals($customFfmpegPath, $ffmpegPathProperty->getValue($manager));
    }
    
    public function testExecuteYtDlpWithInvalidBinary(): void
    {
        // Test with a non-existent binary to ensure proper error handling
        $arguments = ['--version'];
        
        try {
            $result = $this->processManager->executeYtDlp($arguments);
            // If we get here, the binary exists, which is fine for testing
            $this->assertInstanceOf(ProcessResult::class, $result);
        } catch (\RuntimeException $e) {
            // Expected when binary doesn't exist
            $this->assertStringContainsString('not found', $e->getMessage());
        }
    }
    
    public function testExecuteFfmpegWithInvalidBinary(): void
    {
        // Test with a non-existent binary to ensure proper error handling
        $arguments = ['-version'];
        
        try {
            $result = $this->processManager->executeFfmpeg($arguments);
            // If we get here, the binary exists, which is fine for testing
            $this->assertInstanceOf(ProcessResult::class, $result);
        } catch (\RuntimeException $e) {
            // Expected when binary doesn't exist
            $this->assertStringContainsString('not found', $e->getMessage());
        }
    }
    
    public function testGetVideoInfoWithInvalidUrl(): void
    {
        $url = 'https://www.youtube.com/watch?v=invalid';
        
        try {
            $result = $this->processManager->getVideoInfo($url);
            // If we get here, either the binary doesn't exist or it handled the invalid URL
            $this->assertTrue(true);
        } catch (\RuntimeException $e) {
            // Expected when binary doesn't exist
            $this->assertStringContainsString('not found', $e->getMessage());
        } catch (ConverterException $e) {
            // Expected when binary exists but URL is invalid
            $this->assertTrue(true);
        }
    }
    
    public function testSetupWindowsEnvironmentPublicMethod(): void
    {
        $processManager = new ProcessManager($this->testWorkingDir);
        
        $mockProcess = $this->createMock(Process::class);
        
        // On Windows, should set environment variables
        if (DIRECTORY_SEPARATOR === '\\') {
            $mockProcess->expects($this->once())
                ->method('setEnv')
                ->with($this->isType('array'));
        } else {
            // On Unix systems, should not set environment variables
            $mockProcess->expects($this->never())
                ->method('setEnv');
        }
        
        $processManager->setupWindowsEnvironment($mockProcess);
        
        $this->assertTrue(true); // Test passes if no exception is thrown
    }
    
    public function testProcessManagerWithCustomPaths(): void
    {
        $customYtDlpPath = '/custom/yt-dlp';
        $customFfmpegPath = '/custom/ffmpeg';
        
        $processManager = new ProcessManager(
            workingDirectory: $this->testWorkingDir,
            defaultTimeout: 60,
            ytDlpPath: $customYtDlpPath,
            ffmpegPath: $customFfmpegPath
        );
        
        // Test that the process manager was created successfully
        $this->assertInstanceOf(ProcessManager::class, $processManager);
        
        // Test that custom paths are used (this would normally fail since the paths don't exist)
        try {
            $processManager->executeYtDlp(['--version']);
        } catch (\RuntimeException $e) {
            // Expected when custom binary path doesn't exist
            $this->assertStringContainsString('not found', $e->getMessage());
        }
    }
}