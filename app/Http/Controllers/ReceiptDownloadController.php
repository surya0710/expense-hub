<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Support\Storage\ReceiptStorageManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceiptDownloadController extends Controller
{
    public function __invoke(Request $request, Media $media, ReceiptStorageManager $storage): StreamedResponse
    {
        if ($media->model_type !== Expense::class || ! $media->model instanceof Expense) {
            abort(404);
        }

        $this->authorize('view', $media->model);

        $resolved = $storage->resolveReadablePath(
            $media->disk,
            $media->getPathRelativeToRoot()
        );

        if (! $resolved) {
            abort(404);
        }

        return Storage::disk($resolved['disk'])->response(
            $resolved['path'],
            $media->file_name,
            ['Content-Type' => $media->mime_type ?? 'application/octet-stream']
        );
    }
}
