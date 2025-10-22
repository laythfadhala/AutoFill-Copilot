<!-- Profile Selector -->
<div class="col-md-4">
    <div class="card">
        <div class="card-header">
            <h6>Select Profile</h6>
        </div>
        <div class="card-body">
            @forelse($profiles as $profile)
                <div class="mb-2">
                    <button wire:click="selectProfile({{ $profile['id'] }})"
                        class="btn btn-outline-primary btn-sm w-100 text-start {{ $selectedProfile && $selectedProfile['id'] == $profile['id'] ? 'active' : '' }}">
                        {{ $profile['name'] }}
                        @if ($profile['is_default'])
                            <span class="badge bg-primary ms-1">Default</span>
                        @endif
                    </button>
                </div>
            @empty
                <p class="text-muted">No profiles available. Create a profile first.</p>
            @endforelse
        </div>
    </div>
</div>
