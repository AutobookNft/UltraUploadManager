<?php

namespace Ultra\UploadManager\Controllers;

use Illuminate\Http\Request;

use App\Util\ProcessImage;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class FileController extends Controller
{
    public function store(Request $request)
    {
        $file = $request->file('file');
        $path = "file/" . Auth::id() . "/collections/" . $this->teamId . "/items";
        $file->store($path);
        $hash_filename = $file->hashName();
        $relativePath = $file->path();
        $absolutePath = realpath(storage_path('app') . '/' . $relativePath);
        // Crea il percorso assoluto al file del thumbnail
        $thumbnailPath = storage_path('app/' . $path . '/' . 'thumb_' . $hash_filename);
        // Crea il percorso assoluto al file WebP
        $webpPath = storage_path('app/' . $path . '/' . 'webp' . $hash_filename);
        // Aggiungi il job alla coda per la conversione del file
        ProcessImage::dispatch($file, $thumbnailPath, $webpPath);
        return response()->json(['status' => 'success']);
    }
}
