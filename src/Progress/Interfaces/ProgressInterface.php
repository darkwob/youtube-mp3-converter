<?php

namespace Darkwob\YoutubeMp3Converter\Progress\Interfaces;

interface ProgressInterface
{
    /**
     * Update progress information
     *
     * @param string $id Unique identifier for the progress
     * @param string $status Current status
     * @param int $progress Progress percentage (-1 for error)
     * @param string $message Progress message
     * @return bool
     */
    public function update(string $id, string $status, int $progress, string $message): bool;

    /**
     * Get progress information
     *
     * @param string $id Unique identifier for the progress
     * @return array|null
     */
    public function get(string $id): ?array;

    /**
     * Delete progress information
     *
     * @param string $id Unique identifier for the progress
     * @return bool
     */
    public function delete(string $id): bool;

    /**
     * Clean up old progress files
     *
     * @param int $maxAge Maximum age in seconds
     * @return bool
     */
    public function cleanup(int $maxAge = 3600): bool;
} 