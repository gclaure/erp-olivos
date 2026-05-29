<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;
class ProductService
{
    /**
     * Generates a unique SKU for a product based on categories and name.
     */
    public function generateSku(string $name, array $categoryIds): string
    {
        $catPrefix = 'GEN';
        if (!empty($categoryIds)) {
            $category = Category::find($categoryIds[0]);
            if ($category) {
                $catName = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::ascii($category->name));
                $catPrefix = substr(preg_replace('/[^A-Z]/', '', $catName), 0, 4) ?: 'GEN';
            }
        }

        $nameClean = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::ascii($name));
        $words = array_filter(explode(' ', preg_replace('/[^A-Z0-9 ]/', '', $nameClean)));
        $secPrefix = 'PRD';
        foreach ($words as $word) {
            if (strlen($word) >= 2) {
                $secPrefix = substr($word, 0, 3);
                break;
            }
        }

        $year = date('y');
        $basePrefix = "{$catPrefix}-{$secPrefix}-{$year}-";
        
        $existingCodes = Product::where('code', 'like', "%-{$year}-%")->pluck('code');
        $maxSeq = 0;
        foreach ($existingCodes as $c) {
            if (preg_match('/-'.$year.'-(\d{4})$/', $c, $matches)) {
                $seq = (int) $matches[1];
                if ($seq > $maxSeq) {
                    $maxSeq = $seq;
                }
            }
        }

        $nextSeq = $maxSeq + 1;
        return $basePrefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }

    public function processImage(\Illuminate\Http\UploadedFile $file): string
    {
        try {
            if (!$file->getRealPath()) {
                throw new \Exception('El archivo temporal no es accesible.');
            }

            $imageData = file_get_contents($file->getRealPath());
            $image = @imagecreatefromstring($imageData);
            
            if (!$image) {
                throw new \Exception('No se pudo procesar el archivo como una imagen válida.');
            }

            if (!imageistruecolor($image)) {
                imagepalettetotruecolor($image);
            }

            $filename = 'prod_' . \Illuminate\Support\Str::random(10) . '_' . time() . '.webp';
            $path = 'products/images/' . $filename;

            if (!\Illuminate\Support\Facades\Storage::disk('public')->exists('products/images')) {
                \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('products/images');
            }

            ob_start();
            $success = imagewebp($image, null, 80);
            $webpContent = ob_get_clean();
            imagedestroy($image);

            if (!$success) {
                throw new \Exception('Error al convertir la imagen a formato WebP. Verifique que la extensión GD tenga soporte WebP.');
            }

            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $webpContent);

            return asset(\Illuminate\Support\Facades\Storage::url($path));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error procesando imagen: ' . $e->getMessage(), [
                'exception' => $e,
                'path' => $path ?? 'n/a'
            ]);
            throw $e;
        }
    }

    public function downloadAndConvertImage(string $url): string
    {
        try {
            if (str_contains($url, 'drive.google.com/file/d/')) {
                $driveMatch = [];
                if (preg_match('/drive\.google\.com\/file\/d\/([^\/]+)/', $url, $driveMatch)) {
                    $url = "https://drive.google.com/uc?id={$driveMatch[1]}";
                }
            }

            $response = \Illuminate\Support\Facades\Http::timeout(15)->get($url);
            
            if (!$response->successful()) {
                throw new \Exception('No se pudo acceder a la URL de la imagen.');
            }

            $imageData = $response->body();
            $image = @imagecreatefromstring($imageData);
            
            if (!$image) {
                throw new \Exception('El contenido no es una imagen válida o el formato no es compatible.');
            }

            if (!imageistruecolor($image)) {
                imagepalettetotruecolor($image);
            }

            $filename = 'prod_' . \Illuminate\Support\Str::random(10) . '_' . time() . '.webp';
            $path = 'products/images/' . $filename;

            if (!\Illuminate\Support\Facades\Storage::disk('public')->exists('products/images')) {
                \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('products/images');
            }

            ob_start();
            imagewebp($image, null, 80);
            $webpContent = ob_get_clean();
            imagedestroy($image);

            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $webpContent);

            return asset(\Illuminate\Support\Facades\Storage::url($path));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error descargando/convirtiendo imagen: ' . $e->getMessage(), [
                'url' => $url,
                'exception' => $e
            ]);
            throw $e;
        }
    }
}
