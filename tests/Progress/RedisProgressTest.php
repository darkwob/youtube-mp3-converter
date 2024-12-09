<?php

namespace Darkwob\YoutubeMp3Converter\Tests\Progress;

use PHPUnit\Framework\TestCase;
use Darkwob\YoutubeMp3Converter\Progress\RedisProgress;
use Darkwob\YoutubeMp3Converter\Progress\Exceptions\ProgressException;

class RedisProgressTest extends TestCase
{
    private $redis;
    private $progress;

    protected function setUp(): void
    {
        $this->redis = $this->createMock(\Redis::class);
        $this->redis->method('ping')->willReturn(true);
        $this->progress = new RedisProgress($this->redis);
    }

    public function testUpdateProgress(): void
    {
        $this->redis->expects($this->once())
            ->method('setex')
            ->with(
                $this->stringContains('progress:test123'),
                3600,
                $this->callback(function ($value) {
                    $data = json_decode($value, true);
                    return $data['id'] === 'test123' &&
                           $data['status'] === 'processing' &&
                           $data['progress'] === 50.0 &&
                           $data['message'] === 'Test message';
                })
            )
            ->willReturn(true);

        $this->progress->update('test123', 'processing', 50.0, 'Test message');
    }

    public function testGetProgress(): void
    {
        $testData = [
            'id' => 'test123',
            'status' => 'processing',
            'progress' => 50.0,
            'message' => 'Test message',
            'updated_at' => time()
        ];

        $this->redis->expects($this->once())
            ->method('get')
            ->with($this->stringContains('progress:test123'))
            ->willReturn(json_encode($testData));

        $result = $this->progress->get('test123');
        $this->assertEquals($testData, $result);
    }

    public function testDeleteProgress(): void
    {
        $this->redis->expects($this->once())
            ->method('del')
            ->with($this->stringContains('progress:test123'))
            ->willReturn(1);

        $this->progress->delete('test123');
    }

    public function testGetAllProgress(): void
    {
        $testData1 = [
            'id' => 'test1',
            'status' => 'processing',
            'progress' => 50.0,
            'message' => 'Test 1',
            'updated_at' => time()
        ];

        $testData2 = [
            'id' => 'test2',
            'status' => 'completed',
            'progress' => 100.0,
            'message' => 'Test 2',
            'updated_at' => time()
        ];

        $this->redis->expects($this->once())
            ->method('keys')
            ->with($this->stringContains('progress:*'))
            ->willReturn(['progress:test1', 'progress:test2']);

        $this->redis->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                json_encode($testData1),
                json_encode($testData2)
            );

        $result = $this->progress->getAll();
        $this->assertCount(2, $result);
        $this->assertEquals($testData1, $result[0]);
        $this->assertEquals($testData2, $result[1]);
    }

    public function testCleanupProgress(): void
    {
        $now = time();
        $oldData = [
            'id' => 'old',
            'updated_at' => $now - 7200 // 2 hours old
        ];

        $newData = [
            'id' => 'new',
            'updated_at' => $now - 1800 // 30 minutes old
        ];

        $this->redis->expects($this->once())
            ->method('keys')
            ->with($this->stringContains('progress:*'))
            ->willReturn(['progress:old', 'progress:new']);

        $this->redis->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                json_encode($oldData),
                json_encode($newData)
            );

        $this->redis->expects($this->once())
            ->method('del')
            ->with('progress:old');

        $this->progress->cleanup(3600); // 1 hour
    }

    public function testConnectionFailure(): void
    {
        $this->redis = $this->createMock(\Redis::class);
        $this->redis->method('ping')
            ->willThrowException(new \Exception('Connection failed'));

        $this->expectException(ProgressException::class);
        $this->expectExceptionMessage('Could not connect to Redis server: Connection failed');

        new RedisProgress($this->redis);
    }
} 