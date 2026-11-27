<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Composer\InstalledVersions;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OpsHealthController extends Controller
{
    public function show(): JsonResponse
    {
        $versions = [
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'filament' => $this->packageVersion('filament/filament'),
            'livewire' => $this->packageVersion('livewire/livewire'),
        ];

        $buildMeta = $this->readBuildMeta();
        if ($buildMeta !== []) {
            $versions = array_merge($versions, array_filter([
                'astro' => $buildMeta['astro'] ?? null,
                'react' => $buildMeta['react'] ?? null,
                'tailwindcss' => $buildMeta['tailwindcss'] ?? null,
                'node' => $buildMeta['node'] ?? null,
                'git_commit' => $buildMeta['git_commit'] ?? null,
                'built_at' => $buildMeta['built_at'] ?? null,
            ]));
        }

        $dbOk = false;
        try {
            DB::select('SELECT 1');
            $dbOk = true;
        } catch (\Throwable) {
            $dbOk = false;
        }

        $diskPath = base_path();
        $freeBytes = @disk_free_space($diskPath);
        $totalBytes = @disk_total_space($diskPath);
        $freeMb = is_numeric($freeBytes) ? (int) round($freeBytes / 1048576) : null;
        $totalMb = is_numeric($totalBytes) ? (int) round($totalBytes / 1048576) : null;
        $freePercent = ($freeMb !== null && $totalMb !== null && $totalMb > 0)
            ? round(($freeMb / $totalMb) * 100, 1)
            : null;

        return response()->json([
            'app' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
                'url' => config('app.url'),
                'timezone' => config('app.timezone'),
            ],
            'versions' => $versions,
            'database' => [
                'ok' => $dbOk,
                'driver' => config('database.default'),
            ],
            'disk' => [
                'free_mb' => $freeMb,
                'total_mb' => $totalMb,
                'free_percent' => $freePercent,
            ],
            'cron' => [
                'last_heartbeat_at' => $this->readCronHeartbeat(),
            ],
            'logs' => [
                'errors_24h' => $this->countRecentLogErrors(),
            ],
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    protected function packageVersion(string $package): ?string
    {
        try {
            if (InstalledVersions::isInstalled($package)) {
                return InstalledVersions::getPrettyVersion($package);
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function readBuildMeta(): array
    {
        $candidates = [
            storage_path('app/build-meta.json'),
            public_path('build-meta.json'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                $decoded = json_decode((string) file_get_contents($path), true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return [];
    }

    protected function readCronHeartbeat(): ?string
    {
        $path = storage_path('app/ops-cron-heartbeat.txt');
        if (! is_file($path)) {
            return null;
        }

        $value = trim((string) file_get_contents($path));

        return $value !== '' ? $value : null;
    }

    protected function countRecentLogErrors(): int
    {
        $logPath = storage_path('logs/laravel.log');
        if (! is_file($logPath) || ! is_readable($logPath)) {
            return 0;
        }

        $size = filesize($logPath);
        if ($size === false || $size === 0) {
            return 0;
        }

        $maxBytes = 262144; // 256 KB tail limit to keep endpoint lightweight
        $handle = fopen($logPath, 'rb');
        if (! $handle) {
            return 0;
        }

        try {
            if ($size > $maxBytes) {
                fseek($handle, -$maxBytes, SEEK_END);
            }
            $content = (string) fread($handle, $maxBytes);
        } finally {
            fclose($handle);
        }

        $cutoff = now()->subHours(24)->format('Y-m-d H:i:s');
        $count = 0;

        if (preg_match_all('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+[^.]+\.(ERROR|CRITICAL):/m', $content, $matches)) {
            foreach ($matches[1] as $timestamp) {
                if ($timestamp >= $cutoff) {
                    $count++;
                }
            }
        }

        return $count;
    }
}
