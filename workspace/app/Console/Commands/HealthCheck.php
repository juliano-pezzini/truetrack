<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check application health status for production monitoring';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🏥 TrueTrack Health Check');
        $this->newLine();

        $healthy = true;

        // Check Database
        if ($this->checkDatabase()) {
            $this->info('✓ Database: Connected');
        } else {
            $this->error('✗ Database: Connection Failed');
            $healthy = false;
        }

        // Check Redis
        if ($this->checkRedis()) {
            $this->info('✓ Redis: Connected');
        } else {
            $this->error('✗ Redis: Connection Failed');
            $healthy = false;
        }

        // Check Storage
        if ($this->checkStorage()) {
            $this->info('✓ Storage: Writable');
        } else {
            $this->error('✗ Storage: Not Writable');
            $healthy = false;
        }

        // Check Queue
        if ($this->checkQueue()) {
            $this->info('✓ Queue: Working');
        } else {
            $this->error('✗ Queue: Not Working');
            $healthy = false;
        }

        // Display environment info
        $this->newLine();
        $this->info('Environment: ' . config('app.env'));
        $this->info('Debug Mode: ' . (config('app.debug') ? 'ON' : 'OFF'));
        $this->info('Cache Driver: ' . config('cache.default'));
        $this->info('Queue Driver: ' . config('queue.default'));

        $this->newLine();

        if ($healthy) {
            $this->info('🎉 All systems operational!');
            return self::SUCCESS;
        } else {
            $this->error('⚠️  Some systems are not working properly');
            return self::FAILURE;
        }
    }

    /**
     * Check database connection
     */
    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check Redis connection
     */
    private function checkRedis(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check storage writability
     */
    private function checkStorage(): bool
    {
        try {
            $testFile = storage_path('app/health_check_test.txt');
            file_put_contents($testFile, 'test');
            unlink($testFile);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check queue connection
     */
    private function checkQueue(): bool
    {
        try {
            // Just check if we can connect to the queue driver
            $connection = config('queue.default');
            if ($connection === 'redis') {
                return $this->checkRedis();
            } elseif ($connection === 'database') {
                return $this->checkDatabase();
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
