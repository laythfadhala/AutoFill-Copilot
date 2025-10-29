<?php

namespace App\Traits;

trait SanitizesJsonStrings
{

    /**
     * Sanitize and normalize a JSON string extracted from AI responses.
     * @param  string  $content
     * @return string
     */
    public function sanitizeResponse(string $content): string
    {
        // 1️⃣ Remove markdown code fences & intro lines
        $content = preg_replace('/^```(?:json)?\s*$/m', '', $content);
        $content = preg_replace('/^```\s*$/m', '', $content);

        // 2️⃣ Extract first valid-looking JSON block
        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $content, $match)) {
            $json = $match[0];
        } else {
            $json = trim($content);
        }

        // 3️⃣ Remove standalone ellipses only outside strings
        // e.g., between array items or trailing in lists, not inside quotes
        $json = preg_replace('/(?<!")\s*\.\.\.\s*(?!")/', '', $json);

        // 4️⃣ Fix malformed escapes
        $json = preg_replace_callback(
            '/"(?:\\\\.|[^"\\\\])*"/s',
            fn($m) => str_replace("\\'", "'", $m[0]),
            $json
        );

        // 5️⃣ Close unbalanced braces/brackets
        $openCurly = substr_count($json, '{');
        $closeCurly = substr_count($json, '}');
        if ($openCurly > $closeCurly) $json .= str_repeat('}', $openCurly - $closeCurly);

        $openSquare = substr_count($json, '[');
        $closeSquare = substr_count($json, ']');
        if ($openSquare > $closeSquare) $json .= str_repeat(']', $openSquare - $closeSquare);

        // 6️⃣ Decode & handle nested JSON strings
        $decoded = json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            array_walk_recursive($decoded, function (&$value) {
                if (!is_string($value)) return;
                $trimmed = trim($value);
                if (
                    (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) ||
                    (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']'))
                ) {
                    $inner = json_decode($trimmed, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $value = $inner;
                    }
                }
            });
            $json = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return trim($json);
    }
}
