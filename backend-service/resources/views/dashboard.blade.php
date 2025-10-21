@extends('layouts.app')

@section('title', 'Dashboard')

@section('styles')
    @livewireStyles
@endsection

@section('navbar')
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="{{ route('dashboard') }}">{{ config('app.public_name') }}</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, {{ auth()->user()->name }}!</span>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
                </form>
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
