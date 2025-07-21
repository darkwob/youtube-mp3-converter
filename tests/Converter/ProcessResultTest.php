<?php

declare(strict_types=1);

namespace Tests\Converter;

use PHPUnit\Framework\TestCase;
use Darkwob\YoutubeMp3Converter\Converter\ProcessResult;

/**
 * Unit tests for ProcessResult class
 */
class ProcessResultTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $result = new ProcessResult(
            success: true,
            exitCode: 0,
            output: 'Success output',
            errorOutput: '',
            executionTime: 1.5,
            command: 'test-command --arg',
            workingDirectory: '/tmp'
        );
        
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals(0, $result->getExitCode());
        $this->assertEquals('Success output', $result->getOutput());
        $this->assertEquals('', $result->getErrorOutput());
        $this->assertEquals(1.5, $result->getExecutionTime());
        $this->assertEquals('test-command --arg', $result->getCommand());
        $this->assertEquals('/tmp', $result->getWorkingDirectory());
    }
    
    public function testConstructorWithNullWorkingDirectory(): void
    {
        $result = new ProcessResult(
            success: false,
            exitCode: 1,
            output: '',
            errorOutput: 'Error occurred',
            executionTime: 0.5,
            command: 'failing-command'
        );
        
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(1, $result->getExitCode());
        $this->assertEquals('', $result->getOutput());
        $this->assertEquals('Error occurred', $result->getErrorOutput());
        $this->assertEquals(0.5, $result->getExecutionTime());
        $this->assertEquals('failing-command', $result->getCommand());
        $this->assertNull($result->getWorkingDirectory());
    }
    
    public function testIsSuccessful(): void
    {
        $successResult = new ProcessResult(
            success: true,
            exitCode: 0,
            output: 'Success',
            errorOutput: '',
            executionTime: 1.0,
            command: 'test-command'
        );
        
        $this->assertTrue($successResult->isSuccessful());
        
        $failureResult = new ProcessResult(
            success: false,
            exitCode: 1,
            output: '',
            errorOutput: 'Error',
            executionTime: 1.0,
            command: 'test-command'
        );
        
        $this->assertFalse($failureResult->isSuccessful());
    }
    
    public function testIsFailed(): void
    {
        $successResult = new ProcessResult(
            success: true,
            exitCode: 0,
            output: 'Success',
            errorOutput: '',
            executionTime: 1.0,
            command: 'test-command'
        );
        
        $this->assertFalse($successResult->isFailed());
        
        $failureResult = new ProcessResult(
            success: false,
            exitCode: 1,
            output: '',
            errorOutput: 'Error',
            executionTime: 1.0,
            command: 'test-command'
        );
        
        $this->assertTrue($failureResult->isFailed());
    }
    
    public function testHasErrors(): void
    {
        $noErrorResult = new ProcessResult(
            success: true,
            exitCode: 0,
            output: 'Success',
            errorOutput: '',
            executionTime: 1.0,
            command: 'test-command'
        );
        
        $this->assertFalse($noErrorResult->hasErrors());
        
        $errorResult = new ProcessResult(
            success: false,
            exitCode: 1,
            output: '',
            errorOutput: 'Error occurred',
            executionTime: 1.0,
            command: 'test-command'
        );
        
        $this->assertTrue($errorResult->hasErrors());
        
        $whitespaceErrorResult = new ProcessResult(
            success: false,
            exitCode: 1,
            output: '',
            errorOutput: '   ',
            executionTime: 1.0,
            command: 'test-command'
        );
        
        $this->assertFalse($whitespaceErrorResult->hasErrors());
    }
    
    public function testGetFormattedError(): void
    {
        $noErrorResult = new ProcessResult(
            success: true,
            exitCode: 0,
            output: 'Success',
            errorOutput: '',
            executionTime: 1.0,
            command: 'test-command'
        );
        
        $this->assertEquals('', $noErrorResult->getFormattedError());
        
        $errorResult = new ProcessResult(
            success: false,
            exitCode: 1,
            output: '',
            errorOutput: 'File not found',
            executionTime: 1.0,
            command: 'test-command --file missing.txt'
        );
        
        $expected = "Command 'test-command --file missing.txt' failed with exit code 1:\nFile not found";
        $this->assertEquals($expected, $errorResult->getFormattedError());
    }
    
    public function testValidateWithValidData(): void
    {
        $result = new ProcessResult(
            success: true,
            exitCode: 0,
            output: 'Success',
            errorOutput: '',
            executionTime: 1.5,
            command: 'test-command'
        );
        
        $this->assertTrue($result->validate());
    }
    
    public function testValidateWithNegativeExitCode(): void
    {
        $result = new ProcessResult(
            success: false,
            exitCode: -1,
            output: '',
            errorOutput: 'Error',
            executionTime: 1.0,
            command: 'test-command'
        );
        
        $this->assertFalse($result->validate());
    }
    
    public function testValidateWithEmptyCommand(): void
    {
        $result = new ProcessResult(
            success: true,
            exitCode: 0,
            output: 'Success',
            errorOutput: '',
            executionTime: 1.0,
            command: ''
        );
        
        $this->assertFalse($result->validate());
    }
    
    public function testValidateWithWhitespaceCommand(): void
    {
        $result = new ProcessResult(
            success: true,
            exitCode: 0,
            output: 'Success',
            errorOutput: '',
            executionTime: 1.0,
            command: '   '
        );
        
        $this->assertFalse($result->validate());
    }
    
    public function testValidateWithNegativeExecutionTime(): void
    {
        $result = new ProcessResult(
            success: true,
            exitCode: 0,
            output: 'Success',
            errorOutput: '',
            executionTime: -1.0,
            command: 'test-command'
        );
        
        $this->assertFalse($result->validate());
    }
    
    public function testValidateWithZeroExecutionTime(): void
    {
        $result = new ProcessResult(
            success: true,
            exitCode: 0,
            output: 'Success',
            errorOutput: '',
            executionTime: 0.0,
            command: 'test-command'
        );
        
        $this->assertTrue($result->validate());
    }
    
    public function testToArray(): void
    {
        $result = new ProcessResult(
            success: true,
            exitCode: 0,
            output: 'Success output',
            errorOutput: '',
            executionTime: 1.5,
            command: 'test-command --arg',
            workingDirectory: '/tmp'
        );
        
        $expected = [
            'success' => true,
            'exitCode' => 0,
            'output' => 'Success output',
            'errorOutput' => '',
            'executionTime' => 1.5,
            'command' => 'test-command --arg',
            'workingDirectory' => '/tmp'
        ];
        
        $this->assertEquals($expected, $result->toArray());
    }
    
    public function testToArrayWithNullWorkingDirectory(): void
    {
        $result = new ProcessResult(
            success: false,
            exitCode: 1,
            output: '',
            errorOutput: 'Error',
            executionTime: 0.5,
            command: 'failing-command'
        );
        
        $expected = [
            'success' => false,
            'exitCode' => 1,
            'output' => '',
            'errorOutput' => 'Error',
            'executionTime' => 0.5,
            'command' => 'failing-command',
            'workingDirectory' => null
        ];
        
        $this->assertEquals($expected, $result->toArray());
    }
    
    public function testFromArray(): void
    {
        $data = [
            'success' => true,
            'exitCode' => 0,
            'output' => 'Success output',
            'errorOutput' => '',
            'executionTime' => 1.5,
            'command' => 'test-command --arg',
            'workingDirectory' => '/tmp'
        ];
        
        $result = ProcessResult::fromArray($data);
        
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals(0, $result->getExitCode());
        $this->assertEquals('Success output', $result->getOutput());
        $this->assertEquals('', $result->getErrorOutput());
        $this->assertEquals(1.5, $result->getExecutionTime());
        $this->assertEquals('test-command --arg', $result->getCommand());
        $this->assertEquals('/tmp', $result->getWorkingDirectory());
    }
    
    public function testFromArrayWithSnakeCaseKeys(): void
    {
        $data = [
            'success' => false,
            'exit_code' => 1,
            'output' => '',
            'error_output' => 'Error occurred',
            'execution_time' => 0.5,
            'command' => 'failing-command',
            'working_directory' => '/home/user'
        ];
        
        $result = ProcessResult::fromArray($data);
        
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(1, $result->getExitCode());
        $this->assertEquals('', $result->getOutput());
        $this->assertEquals('Error occurred', $result->getErrorOutput());
        $this->assertEquals(0.5, $result->getExecutionTime());
        $this->assertEquals('failing-command', $result->getCommand());
        $this->assertEquals('/home/user', $result->getWorkingDirectory());
    }
    
    public function testFromArrayWithMissingValues(): void
    {
        $data = [
            'command' => 'test-command'
        ];
        
        $result = ProcessResult::fromArray($data);
        
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(-1, $result->getExitCode());
        $this->assertEquals('', $result->getOutput());
        $this->assertEquals('', $result->getErrorOutput());
        $this->assertEquals(0.0, $result->getExecutionTime());
        $this->assertEquals('test-command', $result->getCommand());
        $this->assertNull($result->getWorkingDirectory());
    }
    
    public function testFromArrayWithEmptyArray(): void
    {
        $data = [];
        
        $result = ProcessResult::fromArray($data);
        
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(-1, $result->getExitCode());
        $this->assertEquals('', $result->getOutput());
        $this->assertEquals('', $result->getErrorOutput());
        $this->assertEquals(0.0, $result->getExecutionTime());
        $this->assertEquals('', $result->getCommand());
        $this->assertNull($result->getWorkingDirectory());
    }
}