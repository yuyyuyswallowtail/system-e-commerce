<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
    @include('layouts.partials.theme-link')
</head>
<body class="layout-top-nav">
    <main class="container pt-5 pb-5">
        <h1>{{ config('app.name') }}</h1>
        <p class="measure text-muted">Browse the catalog, read the latest articles, or sign in to follow your orders.</p>
        @if (Route::has('products.index'))
            <a class="btn btn-primary" href="{{ route('products.index') }}">Browse products</a>
        @endif
    </main>
</body>
</html>
