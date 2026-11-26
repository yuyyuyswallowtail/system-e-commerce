@extends('adminlte::master')

@section('title', $title ?? config('app.name'))

@section('classes_body', 'layout-top-nav')

@section('body')
    <div class="wrapper" data-layout="storefront">
        <nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
            <div class="container">
                <a href="{{ url('/') }}" class="navbar-brand">
                    <span class="brand-text font-weight-light">{{ config('app.name') }}</span>
                </a>

                <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#storefront-nav" aria-controls="storefront-nav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse order-3" id="storefront-nav">
                    <ul class="navbar-nav">
                        @if (Route::has('products.index'))
                            <li class="nav-item"><a href="{{ route('products.index') }}" class="nav-link">Products</a></li>
                        @endif
                        @if (Route::has('articles.index'))
                            <li class="nav-item"><a href="{{ route('articles.index') }}" class="nav-link">Articles</a></li>
                        @endif
                    </ul>

                    <ul class="navbar-nav ml-auto">
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item"><a href="{{ route('login') }}" class="nav-link">Log in</a></li>
                            @endif
                            @if (Route::has('register'))
                                <li class="nav-item"><a href="{{ route('register') }}" class="nav-link">Register</a></li>
                            @endif
                        @endguest

                        @auth
                            @if (Route::has('notifications.index'))
                                <livewire:notification.unread-badge />
                            @endif
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">{{ auth()->user()->name }}</a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    @if (Route::has('me'))
                                        <a class="dropdown-item" href="{{ route('me') }}">My profile</a>
                                    @endif
                                    @if (Route::has('logout'))
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">Log out</button>
                                        </form>
                                    @endif
                                </div>
                            </li>
                        @endauth
                    </ul>
                </div>
            </div>
        </nav>

        <div class="content-wrapper">
            <div class="content">
                <div class="container pt-3">
                    {{ $slot }}
                </div>
            </div>
        </div>

        <footer class="main-footer">
            <div class="container">
                <strong>{{ config('app.name') }}</strong>
            </div>
        </footer>
    </div>
@endsection
