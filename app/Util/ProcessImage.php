<?php

namespace App\Util;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ProcessImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $thumbnailPath;
    protected $webpPath;

    public function __construct($filePath, $thumbnailPath, $webpPath)
    {
        $this->filePath = $filePath;
        $this->thumbnailPath = $thumbnailPath;
        $this->webpPath = $webpPath;
    }

    public function handle()
    {
        $temporaryFilePath = $this->filePath;
        $originalName = $this->file->getClientOriginalName();
        $mimeType = $this->file->getMimeType();

        $file = new UploadedFile($temporaryFilePath, $originalName, $mimeType, null, true);

        $image = Image::make($file->getRealPath());
        $thumbnail = $image->fit(200)->encode('webp');
        Storage::put($this->webpPath, $thumbnail);
        $thumbnail = $image->fit(100, 100)->encode('webp');
        Storage::put($this->thumbnailPath, $thumbnail);
        Storage::move($file->hashName(), $this->absolutePath);
    }
}






