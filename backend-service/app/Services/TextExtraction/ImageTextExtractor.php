<?php

namespace App\Services\TextExtraction;

use Illuminate\Support\Facades\Log;
use Exception;

class ImageTextExtractor implements TextExtractorInterface
{
    /**
     * Extract text from image using tesseract
     */
    public function extract(string $filePath): string
    {
        try {
            Log::info('Running OCR on image');

            // Check if tesseract is available
            if (!$this->isCommandAvailable('tesseract')) {
                throw new Exception('OCR tool (tesseract) is not installed. Please install tesseract-ocr package.');
            }

            // Check if file exists and is readable
            if (!file_exists($filePath) || !is_readable($filePath)) {
                throw new Exception('Image file is not readable or accessible.');
            }

            // Check file size (images over 20MB might cause issues)
            $fileSize = filesize($filePath);
            if ($fileSize > 20 * 1024 * 1024) { // 20MB
                throw new Exception('Image file is too large (over 20MB). Please use a smaller image or compress it.');
            }

            $text = shell_exec("tesseract \"$filePath\" stdout -l eng+deu+ara 2>&1");

            // Check for tesseract errors
            if ($text && stripos($text, 'error') !== false) {
                throw new Exception('OCR processing failed. The image might be corrupted or in an unsupported format. Error: ' . trim($text));
            }

            $trimmedText = trim($text ?: '');

            if (empty($trimmedText)) {
                throw new Exception('No text could be extracted from the image. It might not contain readable text, or the quality might be too poor.');
            }

            Log::info('Image OCR completed', ['text_length' => strlen($trimmedText)]);
            return $trimmedText;

        } catch (Exception $e) {
            Log::error('Image OCR failed', ['error' => $e->getMessage(), 'file' => basename($filePath)]);
            throw $e; // Re-throw to be caught by caller
        }
    }

    /**
     * Check if this extractor can handle image files
     */
    public function canHandle(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/');
    }

    /**
     * Check if a command is available on the system
     */
    private function isCommandAvailable(string $command): bool
    {
        $which = shell_exec("which $command 2>/dev/null");
        return !empty(trim($which));
    }
}
