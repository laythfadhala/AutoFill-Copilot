<?php

namespace Tests\Unit;

use App\Services\TextExtraction\TextExtractionService;
use App\Services\TogetherAIService;
use Tests\TestCase;

class TextExtractionServiceTest extends TestCase
{
    public function test_text_extraction_service_can_be_instantiated()
    {
        $service = app(TextExtractionService::class);
        $this->assertInstanceOf(TextExtractionService::class, $service);
    }

    public function test_together_ai_service_can_be_instantiated_with_text_extractor()
    {
        $service = app(TogetherAIService::class);
        $this->assertInstanceOf(TogetherAIService::class, $service);
    }

    public function test_text_extraction_service_supports_common_mime_types()
    {
        $service = app(TextExtractionService::class);

        $this->assertTrue($service->supportsMimeType('application/pdf'));
        $this->assertTrue($service->supportsMimeType('image/jpeg'));
        $this->assertTrue($service->supportsMimeType('image/png'));
        $this->assertTrue($service->supportsMimeType('text/plain'));
        $this->assertFalse($service->supportsMimeType('application/octet-stream'));
    }
}
