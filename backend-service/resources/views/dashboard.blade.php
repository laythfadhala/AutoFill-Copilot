@extends('layouts.app')

@section('title', 'Dashboard')

@section('styles')
    @livewireStyles
@endsection

@section('navbar')
    <nav class="navbar navbar-expand-md navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">{{ config('app.public_name') }}</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <span class="navbar-text me-3">Welcome, {{ auth()->user()->name }}!</span>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
@endsection

@section('content')
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                @livewire('dashboard')
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @livewireScripts

    @if (Auth::check())
        <script>
            const token = "{{ session('auth_token') }}";
            if (token) {
                window.postMessage({
                    type: 'loginSuccess',
                    token: token
                }, '*');
            } else {
                console.error('Auth token not found in session.');
            }
        </script>
    @endif
@endsection
