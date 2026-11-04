<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Acceso al Sistema | SIG Electrocentro S. A.' }}</title>

    <!-- Activos locales sin dependencias externas -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-800 bg-slate-950 flex flex-col justify-between">
    @yield('content')
</body>
</html>
