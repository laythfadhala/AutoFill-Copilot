<?php

namespace App\Services\TextExtraction;

use Illuminate\Support\Facades\Log;
use Exception;

class PdfTextExtractor implements TextExtractorInterface
{
    /**
     * Extract text from PDF using pdftoppm and tesseract
     */
    public function extract(string $filePath): string
    {
        try {
            Log::info('Converting PDF to images for OCR');

            // Check if required tools are available
            if (!$this->isCommandAvailable('pdftoppm')) {
                throw new Exception('PDF processing tool (pdftoppm) is not installed. Please install poppler-utils package.');
            }

            if (!$this->isCommandAvailable('tesseract')) {
                throw new Exception('OCR tool (tesseract) is not installed. Please install tesseract-ocr package.');
            }

            // Check if file exists and is readable
            if (!file_exists($filePath) || !is_readable($filePath)) {
                throw new Exception('PDF file is not readable or accessible.');
            }

            // Check file size (PDFs over 50MB might cause issues)
            $fileSize = filesize($filePath);
            if ($fileSize > 50 * 1024 * 1024) { // 50MB
                throw new Exception('PDF file is too large (over 50MB). Please use a smaller file or compress the PDF.');
            }

            // Create temp directory
            $tmpDir = sys_get_temp_dir() . '/pdf_' . uniqid();
            if (!mkdir($tmpDir, 0755, true)) {
                throw new Exception('Could not create temporary directory for PDF processing.');
            }

            // Convert PDF to images with error checking
            $cmd = "pdftoppm -r 200 -gray -aa no \"$filePath\" \"$tmpDir/page\" -png 2>&1";
            $output = shell_exec($cmd);

            if ($output !== null && stripos($output, 'error') !== false) {
                throw new Exception('PDF conversion failed. The PDF might be corrupted or password-protected. Error: ' . trim($output));
            }

            $images = glob("$tmpDir/*.png");
            if (empty($images)) {
                throw new Exception('PDF conversion produced no images. The PDF might be empty or corrupted.');
            }

            $text = '';
            $processedPages = 0;

            foreach ($images as $img) {
                if (!file_exists($img)) continue;

                Log::info('OCR processing page', ['page' => basename($img)]);
                $pageText = shell_exec("tesseract \"$img\" stdout -l eng+deu+ara 2>&1");

                // Check for tesseract errors
                if ($pageText && stripos($pageText, 'error') !== false) {
                    Log::warning('Tesseract error on page', ['page' => basename($img), 'error' => $pageText]);
                    continue; // Skip this page but continue with others
                }

                $text .= $pageText . "\n";
                $processedPages++;
            }

            // Cleanup
            array_map('unlink', glob("$tmpDir/*"));
            rmdir($tmpDir);

            if (empty(trim($text))) {
                throw new Exception('No text could be extracted from the PDF. It might contain only images without OCR text, or the quality might be too poor.');
            }

            Log::info('PDF processing completed', ['pages_processed' => $processedPages, 'text_length' => strlen($text)]);
            return trim($text);

        } catch (Exception $e) {
            Log::error('PDF processing failed', ['error' => $e->getMessage(), 'file' => basename($filePath)]);

            // Cleanup temp directory if it exists
            if (isset($tmpDir) && is_dir($tmpDir)) {
                array_map('unlink', glob("$tmpDir/*"));
                @rmdir($tmpDir);
            }

            throw $e; // Re-throw to be caught by caller
        }
    }

    /**
     * Check if this extractor can handle PDF files
     */
    public function canHandle(string $mimeType): bool
    {
        return $mimeType === 'application/pdf';
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
