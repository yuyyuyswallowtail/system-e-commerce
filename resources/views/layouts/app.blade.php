@extends('adminlte::page')

@section('title', $title ?? config('app.name'))

@push('css')
    @include('layouts.partials.theme-link')
@endpush

@section('content')
    {{ $slot }}
@endsection
