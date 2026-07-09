<?php

namespace App\Support\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ReceiptStorageManager
{
    public function isS3Configured(): bool
    {
        return filled(config('filesystems.disks.'.config('receipts.s3_disk').'.key'))
            && filled(config('filesystems.disks.'.config('receipts.s3_disk').'.secret'))
            && filled(config('filesystems.disks.'.config('receipts.s3_disk').'.bucket'));
    }

    /**
     * Disk used for new uploads. S3 when credentials exist, otherwise local.
     */
    public function writeDisk(): string
    {
        return $this->isS3Configured()
            ? config('receipts.s3_disk')
            : config('receipts.local_disk');
    }

    /**
     * All disks that may contain receipt files (for fallback reads during migration).
     *
     * @return list<string>
     */
    public function readableDisks(): array
    {
        $disks = [config('receipts.local_disk')];

        if ($this->isS3Configured()) {
            array_unshift($disks, config('receipts.s3_disk'));
        }

        return array_values(array_unique($disks));
    }

    public function disk(string $diskName): Filesystem
    {
        return Storage::disk($diskName);
    }

    public function buildPath(int $companyId, ?string $filename = null): string
    {
        $base = sprintf('%d/receipts/%s', $companyId, now()->format('Y/m'));

        return $filename ? $base.'/'.$filename : $base;
    }

    public function storeUploadedFile(int $companyId, UploadedFile $file, ?string $disk = null): array
    {
        $disk ??= $this->writeDisk();
        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $path = $this->buildPath($companyId, $filename);

        $this->disk($disk)->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );

        return [
            'disk' => $disk,
            'path' => $path,
            'filename' => $filename,
        ];
    }

    public function exists(string $disk, string $path): bool
    {
        return $this->disk($disk)->exists($path);
    }

    /**
     * Resolve a readable path, trying the recorded disk first then fallbacks.
     */
    public function resolveReadablePath(string $preferredDisk, string $path): ?array
    {
        if ($this->exists($preferredDisk, $path)) {
            return ['disk' => $preferredDisk, 'path' => $path];
        }

        foreach ($this->readableDisks() as $disk) {
            if ($disk !== $preferredDisk && $this->exists($disk, $path)) {
                return ['disk' => $disk, 'path' => $path];
            }
        }

        return null;
    }

    public function temporaryUrl(Media $media, ?int $minutes = null): ?string
    {
        $minutes ??= config('receipts.signed_url_ttl_minutes', 15);
        $resolved = $this->resolveReadablePath($media->disk, $media->getPathRelativeToRoot());

        if (! $resolved) {
            return null;
        }

        if ($resolved['disk'] === config('receipts.s3_disk') && $this->isS3Configured()) {
            return $this->disk($resolved['disk'])->temporaryUrl(
                $resolved['path'],
                now()->addMinutes($minutes)
            );
        }

        return URL::temporarySignedRoute(
            'receipts.download',
            now()->addMinutes($minutes),
            ['media' => $media->id]
        );
    }

    public function delete(string $disk, string $path): bool
    {
        if (! $this->exists($disk, $path)) {
            return false;
        }

        return $this->disk($disk)->delete($path);
    }
}
