<?php

namespace App\Filament\Resources\MediaResource\Pages;

use App\Filament\Resources\MediaResource\MediaResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateMedia extends CreateRecord
{
    protected static string $resource = MediaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $filePath = $data['file_path'] ?? null;
        if (is_array($filePath)) {
            $filePath = reset($filePath);
        }

        if ($filePath && is_string($filePath)) {
            $data['file_path'] = $filePath;
            $data['file_name'] = basename($filePath);
            $data['disk'] = $data['disk'] ?? 'public';

            if (empty($data['title'])) {
                $data['title'] = pathinfo($data['file_name'], PATHINFO_FILENAME);
            }

            $fullPath = Storage::disk($data['disk'])->path($filePath);
            if (file_exists($fullPath)) {
                $data['file_size'] = filesize($fullPath);
                $data['mime_type'] = mime_content_type($fullPath);

                $imageSize = @getimagesize($fullPath);
                if ($imageSize) {
                    $data['width'] = $imageSize[0];
                    $data['height'] = $imageSize[1];
                }
            }
        }

        return $data;
    }
}
