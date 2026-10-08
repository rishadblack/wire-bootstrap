<?php

namespace Rishadblack\WireBootstrap\Support;

/**
 * Quill toolbar presets for the editor field. An array passes through
 * unchanged, so any Quill toolbar spec works.
 */
class EditorOptions
{
    /**
     * @param  string|array<int, mixed>|null  $toolbar
     * @return array<int, mixed>
     */
    public static function toolbar(string|array|null $toolbar): array
    {
        if (is_array($toolbar)) {
            return $toolbar;
        }

        return match ($toolbar) {
            'full' => [
                [['header' => [1, 2, 3, false]]],
                ['bold', 'italic', 'underline', 'strike'],
                [['color' => []], ['background' => []]],
                [['list' => 'ordered'], ['list' => 'bullet']],
                [['align' => []]],
                ['blockquote', 'code-block'],
                ['link'],
                ['clean'],
            ],
            default => [
                ['bold', 'italic', 'underline'],
                [['list' => 'ordered'], ['list' => 'bullet']],
                ['link', 'clean'],
            ],
        };
    }
}
