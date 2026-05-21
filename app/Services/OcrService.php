<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OcrService
{
    public function extractAmount(UploadedFile $file): ?int
    {
        $path = $file->getPathname();

        try {
            $ocr = new TesseractOCR($path);

            if (config('services.tesseract.path')) {
                $ocr->executable(config('services.tesseract.path'));
            }

            $text = $ocr->lang('eng')->psm(6)->run();

            return $this->parseAmount($text);
        } catch (\Throwable $e) {
            Log::warning('OCR extraction failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function parseAmount(string $text): ?int
    {
        // Match patterns like: 1,234.56 | ₹1234 | Rs. 450.00 | Total: 299
        $patterns = [
            '/(?:total|amount|grand\s*total|net\s*amount|bill\s*amount)\s*:?\s*(?:rs\.?|inr|₹)?\s*([\d,]+(?:\.\d{1,2})?)/i',
            '/(?:rs\.?|inr|₹)\s*([\d,]+(?:\.\d{1,2})?)/i',
            '/\b([\d,]+\.\d{2})\b/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $clean = str_replace(',', '', $matches[1]);
                $float = (float) $clean;
                if ($float > 0) {
                    return (int) round($float * 100);
                }
            }
        }

        return null;
    }
}

