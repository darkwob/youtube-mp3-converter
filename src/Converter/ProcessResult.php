<?php

declare(strict_types=1);

namespace Darkwob\YoutubeMp3Converter\Converter;

/**
 * Binary process execution result data object
 * 
 * @package Darkwob\YoutubeMp3Converter
 * @requires PHP >=8.4
 */
readonly class ProcessResult
{
    public function __construct(
        public bool $success,
        public int $exitCode,
        public string $output,
        public string $errorOutput,
        public float $executionTime,
        public string $command,
        public ?string $workingDirectory = null
    ) {}

    /**
     * Check if the process was successful
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Get the process exit code
     */
    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    /**
     * Get the standard output
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    /**
     * Get the error output
     */
    public function getErrorOutput(): string
    {
        return $this->errorOutput;
    }

    /**
     * Get the execution time in seconds
     */
    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    /**
     * Get the executed command
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * Get the working directory
     */
    public function getWorkingDirectory(): ?string
    {
        return $this->workingDirectory;
    }

    /**
     * Validate the process result
     */
    public function validate(): bool
    {
        // Basic validation - exit code should be non-negative
        if ($this->exitCode < 0) {
            return false;
        }

        // Command should not be empty
        if (empty(trim($this->command))) {
            return false;
        }

        // Execution time should be non-negative
        if ($this->executionTime < 0) {
            return false;
        }

        return true;
    }

    /**
     * Check if the process failed
     */
    public function isFailed(): bool
    {
        return !$this->success;
    }

    /**
     * Check if there are errors in the output
     */
    public function hasErrors(): bool
    {
        return !empty(trim($this->errorOutput));
    }

    /**
     * Get formatted error message
     */
    public function getFormattedError(): string
    {
        if (!$this->hasErrors()) {
            return '';
        }

        return sprintf(
            "Command '%s' failed with exit code %d:\n%s",
            $this->command,
            $this->exitCode,
            $this->errorOutput
        );
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'exitCode' => $this->exitCode,
            'output' => $this->output,
            'errorOutput' => $this->errorOutput,
            'executionTime' => $this->executionTime,
            'command' => $this->command,
            'workingDirectory' => $this->workingDirectory
        ];
    }

    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['success'] ?? false,
            $data['exitCode'] ?? $data['exit_code'] ?? -1,
            $data['output'] ?? '',
            $data['errorOutput'] ?? $data['error_output'] ?? '',
            $data['executionTime'] ?? $data['execution_time'] ?? 0.0,
            $data['command'] ?? '',
            $data['workingDirectory'] ?? $data['working_directory'] ?? null
        );
    }
}