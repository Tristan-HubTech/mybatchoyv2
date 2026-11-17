<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Throwable;

class PersistentStorage
{
    public static function mediaDisk(): string
    {
        $disk = trim((string) config('filesystems.media_disk', 'public'));

        return $disk !== '' ? $disk : 'public';
    }

    public static function apkDisk(): string
    {
        $disk = trim((string) config('filesystems.apk_disk', 'local'));

        return $disk !== '' ? $disk : 'local';
    }

    public static function isLocalDisk(string $diskName): bool
    {
        return strtolower((string) config("filesystems.disks.{$diskName}.driver", 'local')) === 'local';
    }

    public static function isReadOnlyServerless(): bool
    {
        return filter_var(config('app.serverless_readonly', false), FILTER_VALIDATE_BOOLEAN)
            || filter_var($_SERVER['VERCEL'] ?? $_ENV['VERCEL'] ?? false, FILTER_VALIDATE_BOOLEAN)
            || filter_var($_SERVER['VERCEL_ENV'] ?? $_ENV['VERCEL_ENV'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Validate that the configured disk can persist uploads across serverless requests.
     */
    public static function assertWritableDisk(string $diskName, string $artifactLabel): ?string
    {
        if (self::isReadOnlyServerless() && self::isLocalDisk($diskName)) {
            return "{$artifactLabel} cannot use local filesystem storage on read-only serverless hosting (Vercel). Configure MEDIA_DISK=s3 / APK_DISK=s3 with S3/R2 object storage or set MOBILE_APK_URL.";
        }

        $configuredDisk = config("filesystems.disks.{$diskName}");
        if (!is_array($configuredDisk)) {
            return "Storage disk [{$diskName}] is not defined in config/filesystems.php.";
        }

        try {
            $disk = Storage::disk($diskName);
            $adapter = method_exists($disk, 'getAdapter') ? $disk->getAdapter() : null;
            if ($adapter && str_contains($adapter::class, 'AwsS3V3Adapter') && trim((string) config("filesystems.disks.{$diskName}.bucket", '')) === '') {
                return "External object storage disk [{$diskName}] for {$artifactLabel} requires AWS_BUCKET credentials.";
            }
        } catch (Throwable) {
            return "External storage disk [{$diskName}] for {$artifactLabel} is unavailable. Ensure league/flysystem-aws-s3-v3 and object storage credentials are configured.";
        }

        return null;
    }
}

