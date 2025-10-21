@extends('layouts.app')

@section('title', 'Welcome')

@section('styles')
    <style>
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
        }
    </style>
@endsection

@section('navbar')
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">{{ config('app.public_name') }}</a>
            <div class="navbar-nav ms-auto">
                @if (Auth::check())
                    <span class="navbar-text me-3">Welcome, {{ auth()->user()->name }}!</span>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-light">Sign In</a>
                @endif
            </div>
        </div>
    </nav>
@endsection

@section('content')
    <section class="hero">
        <div class="container text-center">
            @if (Auth::check())
                <h1>Welcome back, {{ auth()->user()->name }}!</h1>
                <p>Ready to continue with your AI-powered form filling?</p>
                <a href="{{ route('dashboard') }}" class="btn btn-light btn-lg">Go to Dashboard</a>
            @else
                <h1>Welcome to {{ config('app.public_name') }}</h1>
                <p>Your AI-powered assistant for automating form filling and document processing.</p>
                <a href="{{ route('login') }}" class="btn btn-light btn-lg">Get Started</a>
            @endif
        </div>
    </section>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-4 text-center">
                <i class="bi bi-robot display-4 text-primary"></i>
                <h3 class="mt-3">AI-Powered</h3>
                <p>Leverage advanced AI to intelligently fill forms and extract data from documents.</p>
            </div>
            <div class="col-md-4 text-center">
                <i class="bi bi-shield-check display-4 text-primary"></i>
                <h3 class="mt-3">Secure</h3>
                <p>Your data is protected with enterprise-grade security measures.</p>
            </div>
            <div class="col-md-4 text-center">
                <i class="bi bi-lightning-charge display-4 text-primary"></i>
                <h3 class="mt-3">Fast</h3>
                <p>Process documents and fill forms in seconds, not hours.</p>
            </div>
        </div>
    </div>

    <footer class="bg-light text-center py-4 mt-5">
        <div class="container">
            <p>&copy; 2025 {{ config('app.public_name') }}. All rights reserved.</p>
        </div>
    </footer>
@endsection

@section('scripts')
    @if (session('logged_out'))
        <script>
            window.postMessage({
                type: 'logout'
            }, '*');
        </script>
    @endif
@endsection
