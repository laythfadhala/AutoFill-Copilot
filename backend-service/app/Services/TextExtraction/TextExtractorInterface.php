<?php

namespace App\Services\TextExtraction;

interface TextExtractorInterface
{
    /**
     * Extract text from a file
     *
     * @param string $filePath
     * @return string
     * @throws \Exception
     */
    public function extract(string $filePath): string;

    /**
     * Check if this extractor can handle the given MIME type
     *
     * @param string $mimeType
     * @return bool
     */
    public function canHandle(string $mimeType): bool;
}
