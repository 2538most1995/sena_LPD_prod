<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class LegacyStorage
{
    public function store(UploadedFile $file, string $directory): string
    {
        $directory = trim($directory, '/');
        $targetDirectory = $this->root().'/uploads/'.$directory;

        if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0775, true) && ! is_dir($targetDirectory)) {
            throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์สำหรับจัดเก็บไฟล์ได้');
        }

        $filename = Str::uuid()->toString().'.'.strtolower($file->getClientOriginalExtension());
        $file->move($targetDirectory, $filename);

        return 'uploads/'.$directory.'/'.$filename;
    }

    public function delete(?string $relativePath): void
    {
        if (! $relativePath || ! str_starts_with($relativePath, 'uploads/')) {
            return;
        }

        $root = realpath($this->root().'/uploads');
        $path = realpath($this->root().'/'.$relativePath);

        if ($root && $path && str_starts_with($path, $root.DIRECTORY_SEPARATOR) && is_file($path)) {
            unlink($path);
        }
    }

    public function absolute(string $relativePath): ?string
    {
        if (! str_starts_with($relativePath, 'uploads/')) {
            return null;
        }

        $root = realpath($this->root().'/uploads');
        $path = realpath($this->root().'/'.$relativePath);

        return $root && $path && str_starts_with($path, $root.DIRECTORY_SEPARATOR) && is_file($path)
            ? $path
            : null;
    }

    public function root(): string
    {
        return storage_path('app/private');
    }
}
