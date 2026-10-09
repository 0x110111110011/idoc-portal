<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DocumentVersionService
{
    public function createInitialVersion(
        Document $document,
        UploadedFile $file,
        int $uploadedBy,
    ): DocumentVersion {
        return $this->createVersion(
            $document,
            $file,
            $uploadedBy,
            1
        );
    }

    public function createVersion(
        Document $document,
        UploadedFile $file,
        int $uploadedBy,
        ?int $versionNumber = null,
    ): DocumentVersion {
        $disk = Storage::disk('local');

        $storedPath = null;

        try {
            return DB::transaction(function () use (
                $document,
                $file,
                $uploadedBy,
                $versionNumber,
                $disk,
                &$storedPath,
            ): DocumentVersion {
                /** @var Document $lockedDocument */
                $lockedDocument = Document::query()
                    ->lockForUpdate()
                    ->findOrFail($document->id);

                if ($versionNumber === null) {
                    $versionNumber = (
                        $lockedDocument
                            ->versions()
                            ->max('version_number') ?? 0
                    ) + 1;
                }

                $extension = strtolower(
                    $file->extension() ?: 'bin'
                );

                $storedPath = sprintf(
                    'documents/%d/v%d.%s',
                    $lockedDocument->getKey(),
                    $versionNumber,
                    $extension
                );

                $writtenPath = $disk->putFileAs(
                    dirname($storedPath),
                    $file,
                    basename($storedPath),
                );

                if ($writtenPath === false) {
                    throw new \RuntimeException(
                        'Unable to store the uploaded document.'
                    );
                }

                $storedPath = $writtenPath;

                /** @var DocumentVersion $version */
                $version = $lockedDocument->versions()->create([
                    'version_number' => $versionNumber,
                    'original_name' => $file->getClientOriginalName(),
                    'stored_path' => $storedPath,
                    'mime_type' => $file->getMimeType()
                        ?? 'application/octet-stream',
                    'size' => $file->getSize() ?? 0,
                    'checksum' => hash_file(
                        'sha256',
                        $file->getRealPath()
                    ),
                    'uploaded_by' => $uploadedBy,
                ]);

                $lockedDocument->update([
                    'current_version_id' => $version->getKey(),
                ]);

                return $version;
            });
        } catch (Throwable $e) {
            if (
                $storedPath !== null
                && $disk->exists($storedPath)
            ) {
                $disk->delete($storedPath);
            }

            throw $e;
        }
    }
}
