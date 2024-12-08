<?php

namespace Darkwob\YoutubeMp3Converter\Progress\Interfaces;

interface ProgressInterface
{
    /**
     * Update progress status
     * 
     * @param string $id Process ID
     * @param string $status Process status (processing, completed, error, etc.)
     * @param float $progress Progress percentage (0-100)
     * @param string $message Progress message
     * @return void
     */
    public function update(string $id, string $status, float $progress, string $message): void;

    /**
     * Get progress status
     * 
     * @param string $id Process ID
     * @return array|null Progress information or null
     */
    public function get(string $id): ?array;

    /**
     * Delete progress record
     * 
     * @param string $id Process ID
     * @return void
     */
    public function delete(string $id): void;

    /**
     * Get all progress records
     * 
     * @return array Progress records
     */
    public function getAll(): array;

    /**
     * Clean up old progress records
     * 
     * @param int $maxAge Maximum age in seconds
     * @return void
     */
    public function cleanup(int $maxAge = 3600): void;
} 