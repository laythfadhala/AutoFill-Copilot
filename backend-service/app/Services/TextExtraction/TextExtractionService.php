<?php

namespace App\Services\TextExtraction;

use Illuminate\Support\Facades\Log;
use Exception;

class TextExtractionService
{
    /**
     * @var TextExtractorInterface[]
     */
    private array $extractors;

    public function __construct()
    {
        $this->extractors = [
            new PdfTextExtractor(),
            new ImageTextExtractor(),
            new PlainTextExtractor(),
        ];
    }

    /**
     * Extract text from a file based on its MIME type
     *
     * @param string $filePath
     * @param string $mimeType
     * @return string
     * @throws Exception
     */
    public function extractText(string $filePath, string $mimeType): string
    {
        foreach ($this->extractors as $extractor) {
            if ($extractor->canHandle($mimeType)) {
                return $extractor->extract($filePath);
            }
        }

        Log::warning('Unsupported file type', ['mime_type' => $mimeType]);
        throw new Exception("Unsupported file type: {$mimeType}. Supported types: PDF, images, and plain text files.");
    }

    /**
     * Check if a MIME type is supported
     *
     * @param string $mimeType
     * @return bool
     */
    public function supportsMimeType(string $mimeType): bool
    {
        foreach ($this->extractors as $extractor) {
            if ($extractor->canHandle($mimeType)) {
                return true;
            }
        }
        return false;
    }
}
