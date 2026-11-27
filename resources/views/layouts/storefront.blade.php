@extends('adminlte::master')

@section('title', $title ?? config('app.name'))

@section('adminlte_css')
    @include('layouts.partials.theme-link')
@endsection

@section('classes_body', 'layout-top-nav')

@section('body')
    <div class="wrapper" data-layout="storefront">
        <a class="skip-link" href="#main-content">{{ __('storefront.ui.skip') }}</a>

        <nav class="main-header navbar navbar-expand-md navbar-light navbar-white" aria-label="{{ __('storefront.ui.nav_label') }}">
            <div class="container">
                <a href="{{ url('/') }}" class="navbar-brand">
                    <span class="brand-text">{{ config('app.name') }}</span>
                </a>

                <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#storefront-nav" aria-controls="storefront-nav" aria-expanded="false" aria-label="{{ __('storefront.ui.toggle_nav') }}">
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
                                <li class="nav-item"><a href="{{ route('login') }}" class="nav-link">{{ __('storefront.ui.login') }}</a></li>
                            @endif
                            @if (Route::has('register'))
                                <li class="nav-item"><a href="{{ route('register') }}" class="nav-link">{{ __('storefront.ui.register') }}</a></li>
                            @endif
                        @endguest

                        @auth
                            @if (Route::has('cart.index'))
                                <li class="nav-item"><a href="{{ route('cart.index') }}" class="nav-link">{{ __('storefront.nav.cart') }}</a></li>
                            @endif
                            @if (Route::has('notifications.index'))
                                <livewire:notification.unread-badge />
                            @endif
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">{{ auth()->user()->name }}</a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    @if (Route::has('me'))
                                        <a class="dropdown-item" href="{{ route('me') }}">{{ __('storefront.ui.account') }}</a>
                                    @endif
                                    @if (Route::has('orders.index'))
                                        <a class="dropdown-item" href="{{ route('orders.index') }}">{{ __('storefront.ui.orders') }}</a>
                                    @endif
                                    @if (Route::has('logout'))
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">{{ __('storefront.ui.logout') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </li>
                        @endauth
                    </ul>
                </div>
            </div>
        </nav>

        <main id="main-content" class="content-wrapper" tabindex="-1">
            <div class="content">
                <div class="container pb-5">
                    {{ $slot }}
                </div>
            </div>
        </main>

        <footer class="main-footer site-footer">
            <div class="container">
                <div class="site-footer__grid">
                    <div>
                        <strong class="site-footer__brand">{{ config('app.name') }}</strong>
                        <p class="mb-0">{{ __('storefront.footer.description') }}</p>
                    </div>
                    <nav aria-label="{{ __('storefront.footer.shop_label') }}">
                        <h2 class="site-footer__heading">{{ __('storefront.footer.shop_heading') }}</h2>
                        <ul class="site-footer__list">
                            @if (Route::has('products.index'))
                                <li><a href="{{ route('products.index') }}">{{ __('storefront.nav.products') }}</a></li>
                            @endif
                            @if (Route::has('articles.index'))
                                <li><a href="{{ route('articles.index') }}">{{ __('storefront.nav.articles') }}</a></li>
                            @endif
                        </ul>
                    </nav>
                    <nav aria-label="{{ __('storefront.footer.account_label') }}">
                        <h2 class="site-footer__heading">{{ __('storefront.footer.account_heading') }}</h2>
                        <ul class="site-footer__list">
                            @guest
                                @if (Route::has('login'))
                                    <li><a href="{{ route('login') }}">{{ __('storefront.nav.login') }}</a></li>
                                @endif
                                @if (Route::has('register'))
                                    <li><a href="{{ route('register') }}">{{ __('storefront.nav.register') }}</a></li>
                                @endif
                            @endguest
                            @auth
                                @if (Route::has('me'))
                                    <li><a href="{{ route('me') }}">{{ __('storefront.nav.account') }}</a></li>
                                @endif
                                @if (Route::has('orders.index'))
                                    <li><a href="{{ route('orders.index') }}">{{ __('storefront.nav.orders') }}</a></li>
                                @endif
                                @if (Route::has('cart.index'))
                                    <li><a href="{{ route('cart.index') }}">{{ __('storefront.nav.cart') }}</a></li>
                                @endif
                            @endauth
                        </ul>
                    </nav>
                </div>
                <p class="site-footer__legal">{{ __('storefront.footer.copyright', ['year' => now()->year, 'name' => config('app.name')]) }}</p>
            </div>
        </footer>
    </div>
@endsection
