@extends('adminlte::page')

@section('title', $title ?? config('app.name'))

@section('content')
    {{ $slot }}
@endsection
