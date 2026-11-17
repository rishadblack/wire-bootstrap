<?php

namespace Rishadblack\WireBootstrap\Support;

/**
 * Option building for the grouped code field (input.code) and the PIN pad:
 * group lengths, separator, character set and PIN bounds, with the config
 * defaults applied and every number clamped to something sane.
 */
class CodeOptions
{
    /**
     * The length of every group. "groups" is either a count (each group then
     * has "length" characters) or a list of lengths, e.g. [3, 3, 4].
     *
     * @return list<int>
     */
    public static function groups(mixed $groups, mixed $length): array
    {
        $length ??= config('wire-bootstrap.code.length', 4);
        $length = max(1, min(32, (int) $length));

        if (is_string($groups) && str_contains($groups, ',')) {
            $groups = explode(',', $groups);
        }

        if (is_array($groups)) {
            $lengths = array_values(array_filter(
                array_map(fn (mixed $size): int => max(0, min(32, (int) $size)), $groups),
                fn (int $size): bool => $size > 0,
            ));

            if ($lengths !== []) {
                return array_slice($lengths, 0, 16);
            }

            $groups = null;
        }

        $groups ??= config('wire-bootstrap.code.groups', 4);
        $count = max(1, min(16, (int) $groups));

        return array_fill(0, $count, $length);
    }

    /**
     * The mount data of the wbCode Alpine component.
     *
     * @param  list<int>  $groups
     * @return array{groups: list<int>, separator: string, alphanumeric: bool, formatted: bool}
     */
    public static function code(array $groups, mixed $separator, mixed $alphanumeric, mixed $formatted): array
    {
        $separator = is_string($separator) ? $separator : (string) config('wire-bootstrap.code.separator', '-');

        return [
            'groups' => $groups,
            'separator' => $separator,
            'alphanumeric' => FieldState::boolean($alphanumeric),
            'formatted' => FieldState::boolean($formatted),
        ];
    }

    /**
     * The PIN length bounds: min is at least 1, max at least min, both at most 12.
     *
     * @return array{0: int, 1: int}
     */
    public static function pinBounds(mixed $min, mixed $max): array
    {
        $min = max(1, min(12, (int) ($min ?? config('wire-bootstrap.pin_pad.min', 4))));
        $max = max($min, min(12, (int) ($max ?? config('wire-bootstrap.pin_pad.max', 6))));

        return [$min, $max];
    }
}
