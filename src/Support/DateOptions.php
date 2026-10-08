<?php

namespace Rishadblack\WireBootstrap\Support;

/**
 * Builds the flatpickr mount data for the date field: the base options from
 * the component props and config, plus normalised enable/disable date lists.
 * All conversion happens here so the Alpine component stays a thin consumer.
 */
class DateOptions
{
    /**
     * Sentinel accepted inside enableDates to disable every date.
     */
    public const DISABLE_ALL = 'disable_all';

    /**
     * @param array{
     *     date?: mixed,
     *     time?: mixed,
     *     format?: string|null,
     *     minDate?: string|null,
     *     maxDate?: string|null,
     *     inline?: mixed,
     *     defaultDate?: string|null,
     *     enableDates?: array<int, string>|null,
     *     disableDates?: array<int, string>|null,
     *     range?: mixed,
     * } $props
     * @return array{
     *     options: array<string, mixed>,
     *     enable: array<int, string>,
     *     disable: array<int, string>,
     *     disableAll: bool
     * }
     */
    public static function build(array $props): array
    {
        $date = FieldState::boolean($props['date'] ?? true, true);
        $time = FieldState::boolean($props['time'] ?? false);
        $inline = FieldState::boolean($props['inline'] ?? false);
        $range = FieldState::boolean($props['range'] ?? false);

        $format = $props['format'] ?? match (true) {
            ! $date && $time => 'h:i K',
            $date && $time => (string) config('wire-bootstrap.date.display_format_time', 'd-M-Y h:i K'),
            default => (string) config('wire-bootstrap.date.display_format', 'd-M-Y'),
        };

        $options = array_merge(
            [
                'dateFormat' => $format,
                'enableTime' => $time,
                'noCalendar' => ! $date,
                'monthSelectorType' => 'static',
                'inline' => $inline,
            ],
            $range ? ['mode' => 'range'] : [],
            filled($props['minDate'] ?? null) ? ['minDate' => $props['minDate']] : [],
            filled($props['maxDate'] ?? null) ? ['maxDate' => $props['maxDate']] : [],
            filled($props['defaultDate'] ?? null) ? ['defaultDate' => $props['defaultDate']] : [],
            (array) config('wire-bootstrap.date.flatpickr', []),
        );

        $enableDates = $props['enableDates'] ?? null;
        $disableAll = is_array($enableDates) && in_array(self::DISABLE_ALL, $enableDates, true);

        return [
            'options' => $options,
            'enable' => $disableAll ? [] : self::convertDates($enableDates),
            'disable' => self::convertDates($props['disableDates'] ?? null),
            'disableAll' => $disableAll,
        ];
    }

    /**
     * Normalise a list of dates in Y-m-d, d-M-Y, d-m-Y or any strtotime-able
     * form into Y-m-d strings, which flatpickr accepts directly. Unparseable
     * values are kept as-is.
     *
     * @param  array<int, string>|null  $dates
     * @return array<int, string>
     */
    public static function convertDates(?array $dates): array
    {
        if ($dates === null) {
            return [];
        }

        $converted = [];

        foreach ($dates as $value) {
            if ($value === self::DISABLE_ALL) {
                continue;
            }

            $converted[] = self::convertDate($value);
        }

        return $converted;
    }

    protected static function convertDate(string $date): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        if (preg_match('/^(\d{2})-([A-Za-z]{3})-(\d{4})$/', $date, $matches)) {
            $timestamp = strtotime($matches[1].' '.$matches[2].' '.$matches[3]);

            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }
        }

        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $date, $matches)) {
            return $matches[3].'-'.$matches[2].'-'.$matches[1];
        }

        $timestamp = strtotime($date);

        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return $date;
    }
}
