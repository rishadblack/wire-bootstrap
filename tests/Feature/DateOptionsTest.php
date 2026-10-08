<?php

use Rishadblack\WireBootstrap\Support\DateOptions;

it('builds date-only options with the configured display format', function () {
    $built = DateOptions::build([]);

    expect($built['options']['dateFormat'])->toBe('d-M-Y')
        ->and($built['options']['enableTime'])->toBeFalse()
        ->and($built['options']['noCalendar'])->toBeFalse()
        ->and($built['options']['inline'])->toBeFalse();
});

it('switches formats for datetime and time-only fields', function () {
    expect(DateOptions::build(['time' => true])['options']['dateFormat'])->toBe('d-M-Y h:i K')
        ->and(DateOptions::build(['time' => true])['options']['enableTime'])->toBeTrue()
        ->and(DateOptions::build(['date' => false, 'time' => true])['options'])
        ->toMatchArray(['dateFormat' => 'h:i K', 'noCalendar' => true]);
});

it('honours an explicit format over the derived one', function () {
    expect(DateOptions::build(['format' => 'Y/m/d'])['options']['dateFormat'])->toBe('Y/m/d');
});

it('passes min, max and default dates only when set', function () {
    $built = DateOptions::build(['minDate' => '2026-01-01', 'maxDate' => '2026-12-31', 'defaultDate' => '2026-06-15']);

    expect($built['options'])->toMatchArray([
        'minDate' => '2026-01-01',
        'maxDate' => '2026-12-31',
        'defaultDate' => '2026-06-15',
    ])
        ->and(DateOptions::build([])['options'])->not->toHaveKeys(['minDate', 'maxDate', 'defaultDate']);
});

it('enables range mode', function () {
    expect(DateOptions::build(['range' => true])['options']['mode'])->toBe('range')
        ->and(DateOptions::build([])['options'])->not->toHaveKey('mode');
});

it('merges extra flatpickr config', function () {
    config()->set('wire-bootstrap.date.flatpickr', ['weekNumbers' => true]);

    expect(DateOptions::build([])['options']['weekNumbers'])->toBeTrue();
});

it('converts the supported date spellings to Y-m-d', function () {
    expect(DateOptions::convertDates(['2026-06-25', '25-Jun-2026', '25-06-2026', 'first day of January 2026']))
        ->toBe(['2026-06-25', '2026-06-25', '2026-06-25', '2026-01-01']);
});

it('keeps unparseable dates as they are', function () {
    expect(DateOptions::convertDates(['not-a-date']))->toBe(['not-a-date']);
});

it('recognises the disable_all sentinel', function () {
    $built = DateOptions::build(['enableDates' => ['disable_all']]);

    expect($built['disableAll'])->toBeTrue()
        ->and($built['enable'])->toBe([]);
});

it('converts enable and disable date lists', function () {
    $built = DateOptions::build(['enableDates' => ['25-Jun-2026'], 'disableDates' => ['26-06-2026']]);

    expect($built['enable'])->toBe(['2026-06-25'])
        ->and($built['disable'])->toBe(['2026-06-26'])
        ->and($built['disableAll'])->toBeFalse();
});
