<?php

namespace Rishadblack\WireBootstrap\Support;

use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\Factory;

/**
 * Everything a form field view needs, resolved once per field: the wire:model
 * binding, derived name and id, label markers, placeholder, size, floating
 * mode and the validation error for the field. Views call resolve() in one
 * @php line and render; no other logic lives in the Blade files.
 */
class FieldState
{
    public function __construct(
        public readonly ?string $model,
        public readonly ?string $name,
        public readonly ?string $id,
        public readonly ?string $label,
        public readonly ?string $placeholder,
        public readonly bool $required,
        public readonly bool $optional,
        public readonly ?string $help,
        public readonly ?string $size,
        public readonly bool $floating,
        public readonly ?string $errorKey,
        public readonly bool $invalid,
        public readonly ?string $error,
        public readonly ?string $requiredMarker,
        public readonly ?string $optionalMarker,
    ) {}

    /**
     * @param array{
     *     label?: string|null,
     *     name?: string|null,
     *     id?: string|null,
     *     placeholder?: string|null,
     *     required?: mixed,
     *     optional?: mixed,
     *     help?: string|null,
     *     size?: string|null,
     *     floating?: mixed,
     * } $props
     */
    public static function resolve(ComponentAttributeBag $attributes, array $props, ?Factory $view = null): self
    {
        $wireModel = $attributes->whereStartsWith('wire:model')->getAttributes();
        $model = $wireModel === [] ? null : head(array_values($wireModel));
        $model = is_string($model) && $model !== '' ? $model : null;

        $label = self::stringOrNull($props['label'] ?? null);
        $name = self::stringOrNull($props['name'] ?? null) ?? $model;
        $id = self::stringOrNull($props['id'] ?? null) ?? self::deriveId($name);
        $errorKey = $model ?? $name;

        $required = self::boolean($props['required'] ?? false);
        $optional = self::boolean($props['optional'] ?? false);
        $floating = self::boolean($props['floating'] ?? config('wire-bootstrap.defaults.floating', false));

        $size = self::stringOrNull($props['size'] ?? null) ?? config('wire-bootstrap.defaults.size');
        $size = in_array($size, ['sm', 'lg'], true) ? $size : null;

        $placeholder = self::resolvePlaceholder($props['placeholder'] ?? null, $label, $floating);

        [$invalid, $error] = self::resolveError($errorKey, $view);

        return new self(
            model: $model,
            name: $name,
            id: $id,
            label: $label,
            placeholder: $placeholder,
            required: $required,
            optional: $optional,
            help: self::stringOrNull($props['help'] ?? null),
            size: $size,
            floating: $floating && $label !== null,
            errorKey: $errorKey,
            invalid: $invalid,
            error: $error,
            requiredMarker: $required && config('wire-bootstrap.markers.required', true)
                ? __('wire-bootstrap::wire-bootstrap.required_marker')
                : null,
            optionalMarker: $optional && config('wire-bootstrap.markers.optional', true)
                ? __('wire-bootstrap::wire-bootstrap.optional_marker')
                : null,
        );
    }

    /**
     * Derive a stable, collision-free HTML id from the field name. Dotted
     * Livewire names become hyphenated with a short hash suffix so that
     * "user.name" and "user-name" never collide, while plain names stay as-is.
     */
    public static function deriveId(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $sanitized = preg_replace('/[^A-Za-z0-9_-]/', '-', $name) ?? $name;

        if ($sanitized === $name) {
            return $name;
        }

        return $sanitized.'-'.substr(md5($name), 0, 6);
    }

    public static function boolean(mixed $value, bool $default = false): bool
    {
        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        // A valueless HTML attribute (e.g. plain "required") reaches us as "".
        if ($value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    protected static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * An explicit placeholder wins; an explicit empty string suppresses it; by
     * default the label produces one. Floating labels require a placeholder,
     * so the label is used even when auto placeholders are off.
     */
    protected static function resolvePlaceholder(mixed $placeholder, ?string $label, bool $floating): ?string
    {
        if (is_string($placeholder)) {
            return $placeholder === '' ? null : $placeholder;
        }

        if ($label === null) {
            return null;
        }

        if (config('wire-bootstrap.defaults.show_placeholder', true)) {
            return __('wire-bootstrap::wire-bootstrap.placeholder', ['label' => $label]);
        }

        return $floating ? $label : null;
    }

    /**
     * Read the validation error through the view factory's shared data, the
     * access path that works both with and without the web middleware (and the
     * one Blaze supports).
     *
     * @return array{0: bool, 1: string|null}
     */
    protected static function resolveError(?string $errorKey, ?Factory $view): array
    {
        if ($errorKey === null) {
            return [false, null];
        }

        $view ??= app('view');

        $errors = $view->shared('errors', new ViewErrorBag);

        if (! $errors instanceof ViewErrorBag || ! $errors->has($errorKey)) {
            return [false, null];
        }

        return [true, $errors->first($errorKey)];
    }
}
