<?php

namespace App\Services\TextExtraction;

use Exception;

class PlainTextExtractor implements TextExtractorInterface
{
    /**
     * Extract text from plain text file
     */
    public function extract(string $filePath): string
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new Exception('Text file is not readable or accessible.');
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new Exception('Could not read text file content.');
        }

        $trimmedContent = trim($content);

        if (empty($trimmedContent)) {
            throw new Exception('Text file is empty.');
        }

        return $trimmedContent;
    }

    /**
     * Check if this extractor can handle plain text files
     */
    public function canHandle(string $mimeType): bool
    {
        return $mimeType === 'text/plain';
    }
}
