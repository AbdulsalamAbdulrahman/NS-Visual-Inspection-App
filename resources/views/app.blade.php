<!DOCTYPE html>
@php($appearance ??= 'light') {{-- unset on pages outside the web middleware (e.g. a 404 for an unknown URL) --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if (in_array($appearance, ['light', 'dark'], true)) data-theme="{{ $appearance }}" @endif>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#09502E">

        {{-- Only for people who chose Auto: resolve it before first paint so there's no flash. --}}
        <script>
            (function () {
                var d = document.documentElement;
                if (!d.dataset.theme) {
                    d.dataset.theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
            })();
        </script>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="manifest" href="/manifest.webmanifest">

        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        <x-inertia::head>
            <title>{{ config('app.name', 'KENS') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
