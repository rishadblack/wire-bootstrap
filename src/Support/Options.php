<?php

namespace Rishadblack\WireBootstrap\Support;

/**
 * Normalises the option shapes accepted by select, radio and checkbox-group:
 * a key => label map, or a list of ['value' => ..., 'name' => ...] items
 * (with 'label' accepted as an alias of 'name').
 */
class Options
{
    /**
     * @param  iterable<int|string, mixed>|null  $options
     * @return list<array{value: int|string, label: string}>
     */
    public static function normalize(?iterable $options): array
    {
        if ($options === null) {
            return [];
        }

        $normalized = [];

        foreach ($options as $key => $option) {
            if (is_array($option)) {
                $normalized[] = [
                    'value' => $option['value'] ?? $key,
                    'label' => (string) ($option['name'] ?? $option['label'] ?? ($option['value'] ?? $key)),
                ];

                continue;
            }

            $normalized[] = [
                'value' => $key,
                'label' => (string) $option,
            ];
        }

        return $normalized;
    }
}
