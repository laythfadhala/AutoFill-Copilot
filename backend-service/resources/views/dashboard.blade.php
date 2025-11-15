@extends('layouts.app')

@section('title', 'Dashboard')

@section('styles')
    @livewireStyles
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
