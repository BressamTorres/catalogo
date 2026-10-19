@extends('layouts.auth')

@section('title', 'Criar conta')

@push('head')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
@endpush

@section('content')
    @include('auth.partials.register-card')
@endsection
