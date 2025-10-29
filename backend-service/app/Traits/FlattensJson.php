<?php

namespace App\Traits;

trait FlattensJson
{
    /**
     * Recursively flatten nested arrays or JSON strings into a single-level array.
     *
     * @param  array  $data
     * @param  string  $prefix
     * @return array
     */
    private function flattenJsonRecursive(array $data, string $prefix = ''): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $newKey = $prefix === '' ? $key : "{$prefix}_{$key}";

            // Try decoding if value is a JSON string
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $result = array_merge($result, $this->flattenJsonRecursive($decoded, $newKey));
                    continue;
                }
            }

            // If it's an array, go deeper
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenJsonRecursive($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
