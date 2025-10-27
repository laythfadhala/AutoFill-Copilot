@extends('layouts.app')

@section('title', 'Dashboard')

@section('styles')
    @livewireStyles
@endsection

@section('navbar')
    <nav class="navbar navbar-expand-md navbar-dark bg-primary shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">
                <i class="fas fa-magic me-2"></i>{{ config('app.public_name') }}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                            href="{{ route('dashboard') }}">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link d-flex align-items-center" href="#" id="userDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="bg-dark bg-opacity-50 rounded-circle d-flex align-items-center justify-content-center me-2 overflow-hidden"
                                style="width: 32px; height: 32px;">
                                @if (auth()->user()->avatar)
                                    <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}"
                                        class="rounded-circle" style="width: 100%; height: 100%; object-fit: cover;">
                                @else
                                    <span
                                        class="text-white fw-bold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                                @endif
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                            <li class="dropdown-header">
                                <div class="fw-semibold">{{ auth()->user()->name }}</div>
                                <small class="text-muted">{{ auth()->user()->email }}</small>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="{{ route('user.settings') }}">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </a></li>
                            <li><a class="dropdown-item" href="{{ route('account.manage') }}">
                                    <i class="fas fa-user-cog me-2"></i>Account Management
                                </a></li>
                            <li><a class="dropdown-item" href="{{ route('billing.subscriptions') }}">
                                    <i class="fas fa-credit-card me-2"></i>Billing & Subscriptions
                                </a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
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
