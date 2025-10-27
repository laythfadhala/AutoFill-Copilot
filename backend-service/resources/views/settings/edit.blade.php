@extends('layouts.app')

@section('title', 'Settings')

@section('styles')
    @livewireStyles
@endsection

@section('content')
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h1>User Settings</h1>
                <p>Manage your account settings and preferences here.</p>
                <!-- Add settings form/content here -->
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @livewireScripts
@endsection
