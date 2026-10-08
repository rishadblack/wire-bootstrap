@props([
    'title' => null,
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="{{ config('wire-bootstrap.theme.default') === 'dark' ? 'dark' : 'light' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? config('app.name') }}</title>

    @if (config('wire-bootstrap.theme.dark_mode'))
        {{-- Pre-paint theme application; the package's single inline script (add a CSP nonce here if you publish the view). --}}
        <script>
            (() => {
                try {
                    const stored = localStorage.getItem('wb-theme') || @js((string) config('wire-bootstrap.theme.default', 'light'));
                    const theme = stored === 'auto'
                        ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                        : stored;
                    document.documentElement.setAttribute('data-bs-theme', theme);
                } catch (e) {}
            })();
        </script>
    @endif

    @vite(config('wire-bootstrap.vite_entries'))
    @stack('css')
    {{ $head ?? '' }}
</head>

<body {{ $attributes }}>
    {{ $nav ?? '' }}

    {{ $slot }}

    {{ $footer ?? '' }}
    @stack('js')
    {{ $scripts ?? '' }}
</body>

</html>
