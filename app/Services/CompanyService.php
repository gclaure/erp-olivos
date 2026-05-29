<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CompanyService
{
    /**
     * Get the company information (assuming there is only one).
     */
    public function getCompany(): ?Company
    {
        return \App\Models\Company::first();
    }

    /**
     * Get the tenant ID.
     */
    public function getTenantId(): ?string
    {
        return $this->getCompany()?->id;
    }

    /**
     * Update company information.
     */
    public function update(array $data): Company
    {
        $company = $this->getCompany() ?? new Company();

        if (isset($data['logo']) && $data['logo'] instanceof UploadedFile) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $data['logo_path'] = $data['logo']->store('company', 'public');
            
            // Generate favicon
            $this->generateFavicon($data['logo']);
            
            unset($data['logo']);
        }

        $company->fill($data);
        $company->save();

        return $company;
    }

    /**
     * Generate a favicon from the uploaded logo.
     */
    protected function generateFavicon(UploadedFile $file): void
    {
        try {
            $imageInfo = getimagesize($file->getRealPath());
            if (!$imageInfo) return;

            $mime = $imageInfo['mime'];
            $source = match ($mime) {
                'image/jpeg' => imagecreatefromjpeg($file->getRealPath()),
                'image/png'  => imagecreatefrompng($file->getRealPath()),
                'image/gif'  => imagecreatefromgif($file->getRealPath()),
                'image/webp' => imagecreatefromwebp($file->getRealPath()),
                default      => null,
            };

            if (!$source) return;

            $size = 32;
            $favicon = imagecreatetruecolor($size, $size);

            // Preserve transparency for PNG/WebP
            if ($mime === 'image/png' || $mime === 'image/webp') {
                imagealphablending($favicon, false);
                imagesavealpha($favicon, true);
                $transparent = imagecolorallocatealpha($favicon, 255, 255, 255, 127);
                imagefilledrectangle($favicon, 0, 0, $size, $size, $transparent);
            }

            imagecopyresampled(
                $favicon, $source,
                0, 0, 0, 0,
                $size, $size, imagesx($source), imagesy($source)
            );

            // Save as PNG in public company folder
            $path = Storage::disk('public')->path('company/favicon.png');
            imagepng($favicon, $path);

            imagedestroy($source);
            imagedestroy($favicon);
        } catch (\Exception $e) {
            // Silently fail if GD is not available or error occurs
            report($e);
        }
    }
}
