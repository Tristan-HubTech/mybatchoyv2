<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Services\PersistentStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AppDownloadController extends Controller
{
    public const APK_FILENAME = 'mybatchoy.apk';

    /**
     * Stream the active business logo safely across local or object storage disks.
     */
    public function serveLogo(): StreamedResponse|RedirectResponse|Response
    {
        $settings = BusinessSetting::current();
        $path = trim((string) $settings?->logo_path);

        if ($path === '') {
            abort(404);
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return redirect()->away($path);
        }

        $diskName = PersistentStorage::mediaDisk();

        try {
            $disk = Storage::disk($diskName);
            if (!$disk->exists($path)) {
                abort(404);
            }

            return $disk->response($path, basename($path), [
                'Cache-Control' => 'public, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (Throwable) {
            abort(404);
        }
    }

    public static function validatedExternalApkUrl(): ?string
    {
        $raw = trim((string) config('app.mobile_apk_url', ''));
        if ($raw === '') {
            return null;
        }

        if (!self::isSafeHttpsUrl($raw)) {
            Log::warning('Ignoring invalid or non-HTTPS MOBILE_APK_URL configuration.');

            return null;
        }

        return $raw;
    }

    private static function isSafeHttpsUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = trim((string) ($parts['host'] ?? ''));

        return $scheme === 'https'
            && $host !== ''
            && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Serve the Android APK directly with auto-download headers.
     */
    public function downloadApk(): BinaryFileResponse|StreamedResponse|RedirectResponse|Response
    {
        $rawCustomUrl = trim((string) config('app.mobile_apk_url', ''));
        if ($rawCustomUrl !== '') {
            $validatedUrl = self::validatedExternalApkUrl();
            if ($validatedUrl === null) {
                return response(
                    '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Invalid APK URL Configuration</title></head>'
                    . '<body style="background:#111111;color:#FFFFFF;font-family:sans-serif;padding:32px;text-align:center;">'
                    . '<h2 style="color:#E53935;">Invalid External APK URL</h2>'
                    . '<p style="color:#BDBDBD;max-width:460px;margin:12px auto;">The configured <code>MOBILE_APK_URL</code> is unsafe or malformed. Only valid <code>https://</code> download URLs are allowed.</p>'
                    . '</body></html>',
                    422,
                    ['Content-Type' => 'text/html; charset=UTF-8']
                );
            }

            return redirect()->away($validatedUrl);
        }

        $downloadHeaders = [
            'Content-Type' => 'application/vnd.android.package-archive',
            'Content-Disposition' => 'attachment; filename="' . self::APK_FILENAME . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ];

        $apkDisk = PersistentStorage::apkDisk();
        if (!PersistentStorage::isLocalDisk($apkDisk)) {
            try {
                $disk = Storage::disk($apkDisk);
                $remotePath = 'downloads/' . self::APK_FILENAME;
                if ($disk->exists($remotePath)) {
                    if (filled(config("filesystems.disks.{$apkDisk}.url"))) {
                        $diskUrl = $disk->url($remotePath);
                        if (self::isSafeHttpsUrl($diskUrl)) {
                            return redirect()->away($diskUrl);
                        }
                    }

                    return $disk->download($remotePath, self::APK_FILENAME, $downloadHeaders);
                }
            } catch (Throwable) {
                // Fall through to local/404 response safely.
            }
        }

        $path = self::resolveApkPath();

        if ($path === null) {
            return response(
                '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MyBatchoy Android APK</title></head>'
                . '<body style="background:#111111;color:#FFFFFF;font-family:sans-serif;padding:32px;text-align:center;">'
                . '<h2 style="color:#FFC107;">MyBatchoy Android APK Not Uploaded Yet</h2>'
                . '<p style="color:#BDBDBD;max-width:460px;margin:12px auto;">Ask the Owner/Admin to configure <code>MOBILE_APK_URL</code>, upload <strong>mybatchoy.apk</strong> in <strong>Settings &rarr; Download Android App</strong>, or place the file in <code>public/downloads/mybatchoy.apk</code>.</p>'
                . '</body></html>',
                404,
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }

        return response()->download($path, self::APK_FILENAME, $downloadHeaders);
    }

    /**
     * Allow Owner/Admin to upload or replace the hosted Android APK directly on the server.
     */
    public function uploadApk(Request $request): RedirectResponse
    {
        $request->validate([
            'apk_file' => ['required', 'file', 'max:153600'],
        ], [
            'apk_file.required' => 'Choose an Android .apk file before uploading.',
            'apk_file.max' => 'The APK file cannot exceed 150 MB.',
        ]);

        $file = $request->file('apk_file');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ($extension !== 'apk') {
            return back()->withErrors([
                'apk_file' => 'Only Android .apk files are allowed here.',
            ]);
        }

        $apkDisk = PersistentStorage::apkDisk();
        $savedLocation = null;

        if (!PersistentStorage::isLocalDisk($apkDisk)) {
            $storageError = PersistentStorage::assertWritableDisk($apkDisk, 'Android APK');
            if ($storageError !== null) {
                return back()->withErrors([
                    'apk_file' => $storageError,
                ]);
            }

            $storedPath = Storage::disk($apkDisk)->putFileAs('downloads', $file, self::APK_FILENAME);
            if ($storedPath === false) {
                return back()->withErrors([
                    'apk_file' => 'Failed to store the Android APK on the configured object storage disk.',
                ]);
            }

            $savedLocation = $apkDisk . ':' . $storedPath;
        } else {
            if (PersistentStorage::isReadOnlyServerless()) {
                return back()->withErrors([
                    'apk_file' => PersistentStorage::assertWritableDisk($apkDisk, 'Android APK'),
                ]);
            }

            $targetDir = public_path('downloads');

            try {
                File::ensureDirectoryExists($targetDir);
                $file->move($targetDir, self::APK_FILENAME);
                $savedLocation = basename($targetDir . DIRECTORY_SEPARATOR . self::APK_FILENAME);
            } catch (Throwable) {
                $fallbackDir = storage_path('app/downloads');
                File::ensureDirectoryExists($fallbackDir);
                $file->move($fallbackDir, self::APK_FILENAME);
                $savedLocation = basename($fallbackDir . DIRECTORY_SEPARATOR . self::APK_FILENAME);
            }
        }

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'apk_uploaded',
            'model_type' => null,
            'model_id' => null,
            'details' => 'Uploaded Android APK (' . (string) $savedLocation . ')',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->to(route('owner.settings.index') . '#mobile-app-download')
            ->with('status', 'Android APK uploaded! Scanning the QR code or clicking Download now auto-downloads mybatchoy.apk.');
    }

    public static function resolveApkPath(): ?string
    {
        $candidates = [
            public_path('downloads/' . self::APK_FILENAME),
            storage_path('app/downloads/' . self::APK_FILENAME),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array{exists: bool, size_label: string|null, updated_at: string|null}
     */
    public static function apkMetadata(): array
    {
        $apkDisk = PersistentStorage::apkDisk();
        if (!PersistentStorage::isLocalDisk($apkDisk)) {
            try {
                $disk = Storage::disk($apkDisk);
                $remotePath = 'downloads/' . self::APK_FILENAME;
                if ($disk->exists($remotePath)) {
                    $bytes = (int) ($disk->size($remotePath) ?: 0);
                    $mb = $bytes > 0 ? number_format($bytes / 1048576, 1) . ' MB' : 'Ready';
                    $mtime = $disk->lastModified($remotePath);

                    return [
                        'exists' => true,
                        'size_label' => $mb,
                        'updated_at' => $mtime ? date('M d, Y h:i A', $mtime) : null,
                    ];
                }
            } catch (Throwable) {
            }
        }

        $path = self::resolveApkPath();
        if ($path === null) {
            return [
                'exists' => false,
                'size_label' => null,
                'updated_at' => null,
            ];
        }

        $bytes = @filesize($path) ?: 0;
        $mb = $bytes > 0 ? number_format($bytes / 1048576, 1) . ' MB' : 'Ready';
        $mtime = @filemtime($path);

        return [
            'exists' => true,
            'size_label' => $mb,
            'updated_at' => $mtime ? date('M d, Y h:i A', $mtime) : null,
        ];
    }

    /**
     * Resolve direct auto-download URL (host-local or explicit env override, never GitHub).
     */
    public static function directDownloadUrl(?Request $request = null, bool $preferLanHost = false): string
    {
        $validatedCustom = self::validatedExternalApkUrl();
        if ($validatedCustom !== null) {
            return $validatedCustom;
        }

        $url = route('app.download');
        if (!$preferLanHost || !$request) {
            return $url;
        }

        $host = strtolower((string) $request->getHost());
        if ($host === 'localhost' || $host === '127.0.0.1') {
            $lanIp = @gethostbyname(gethostname() ?: '');
            if (is_string($lanIp) && filter_var($lanIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $lanIp !== '127.0.0.1') {
                return preg_replace(
                    '/^(https?:\/\/)(localhost|127\.0\.0\.1)(:\d+)?/i',
                    '$1' . $lanIp . '$3',
                    $url
                ) ?: $url;
            }
        }

        return $url;
    }
}

