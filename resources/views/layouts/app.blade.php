<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="antialiased bg-gray-50 text-gray-900 flex">
    @auth
        <div class="no-print">
            <livewire:layout.sidebar />
        </div>
        <main class="flex-1 ml-64 min-h-screen print-only-container">
            <div class="p-8">
                {{ $slot }}
            </div>
        </main>
    @else
        <main class="w-full">
            {{ $slot }}
        </main>
    @endauth

    @livewireScripts
</body>

</html>