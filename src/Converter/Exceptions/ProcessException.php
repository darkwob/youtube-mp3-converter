<?php

declare(strict_types=1);

namespace Darkwob\YoutubeMp3Converter\Converter\Exceptions;

/**
 * Exception thrown for binary execution failures
 * 
 * @requires PHP >=8.4
 */
class ProcessException extends ConverterException
{
    public static function executionFailed(string $command, int $exitCode, string $errorOutput = ''): self
    {
        $message = "Process execution failed: $command\n";
        $message .= "Exit code: $exitCode\n";
        
        if (!empty($errorOutput)) {
            $message .= "Error output: $errorOutput\n";
        }
        
        $message .= "\nTroubleshooting:\n";
        $message .= "1. Verify the binary exists and is executable\n";
        $message .= "2. Check command arguments are valid\n";
        $message .= "3. Ensure sufficient system resources\n";
        $message .= "4. Check for missing dependencies";
        
        return new self($message);
    }

    public static function timeout(string $command, int $timeoutSeconds): self
    {
        $message = "Process timed out after $timeoutSeconds seconds: $command\n\n";
        $message .= "Possible causes:\n";
        $message .= "1. Large file processing taking longer than expected\n";
        $message .= "2. Network issues during download\n";
        $message .= "3. System resource constraints\n";
        $message .= "4. Hung process requiring manual intervention\n\n";
        $message .= "Solutions:\n";
        $message .= "1. Increase timeout value\n";
        $message .= "2. Check system resources (CPU, memory, disk)\n";
        $message .= "3. Verify network connectivity";
        
        return new self($message);
    }

    public static function startFailed(string $command, string $reason = ''): self
    {
        $message = "Failed to start process: $command";
        if (!empty($reason)) {
            $message .= "\nReason: $reason";
        }
        
        $message .= "\n\nCheck:\n";
        $message .= "1. Binary file exists and is executable\n";
        $message .= "2. Correct path to binary\n";
        $message .= "3. System has sufficient resources\n";
        $message .= "4. No permission issues";
        
        return new self($message);
    }

    public static function unexpectedTermination(string $command, int $signal = 0): self
    {
        $message = "Process terminated unexpectedly: $command";
        if ($signal > 0) {
            $message .= "\nSignal: $signal";
        }
        
        $message .= "\n\nPossible causes:\n";
        $message .= "1. System killed the process (out of memory)\n";
        $message .= "2. User interrupted the process\n";
        $message .= "3. System shutdown or restart\n";
        $message .= "4. Process crashed due to internal error";
        
        return new self($message);
    }

    public static function invalidArguments(string $command, array $arguments): self
    {
        $message = "Invalid arguments for command: $command\n";
        $message .= "Arguments: " . implode(' ', $arguments) . "\n\n";
        $message .= "Please check:\n";
        $message .= "1. Argument syntax is correct\n";
        $message .= "2. File paths exist and are accessible\n";
        $message .= "3. Options are supported by the binary version\n";
        $message .= "4. Special characters are properly escaped";
        
        return new self($message);
    }

    public static function workingDirectoryNotFound(string $workingDir): self
    {
        $message = "Working directory not found: $workingDir\n\n";
        $message .= "Ensure:\n";
        $message .= "1. Directory exists\n";
        $message .= "2. Process has access permissions\n";
        $message .= "3. Path is correct and absolute";
        
        return new self($message);
    }

    public static function environmentSetupFailed(string $reason): self
    {
        $message = "Failed to setup process environment: $reason\n\n";
        $message .= "Check:\n";
        $message .= "1. Environment variables are valid\n";
        $message .= "2. PATH contains required directories\n";
        $message .= "3. System environment is not corrupted";
        
        return new self($message);
    }

    public static function outputParsingFailed(string $output, string $reason = ''): self
    {
        $message = "Failed to parse process output";
        if (!empty($reason)) {
            $message .= ": $reason";
        }
        
        $message .= "\nOutput: " . substr($output, 0, 500);
        if (strlen($output) > 500) {
            $message .= "... (truncated)";
        }
        
        return new self($message);
    }

    public static function windowsExecutionError(string $command, string $windowsError): self
    {
        $message = "Windows-specific execution error for: $command\n";
        $message .= "Windows error: $windowsError\n\n";
        $message .= "Common Windows issues:\n";
        $message .= "1. File association problems\n";
        $message .= "2. Windows Defender blocking execution\n";
        $message .= "3. Missing Visual C++ redistributables\n";
        $message .= "4. Path contains spaces without quotes\n";
        $message .= "5. Long path issues (>260 characters)";
        
        return new self($message);
    }
}