<!DOCTYPE html>
<html lang="es" class="h-full bg-canvas">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#001836">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acceso | SIGELECTRO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-800 bg-canvas">
    @yield('content')
</body>
</html>
