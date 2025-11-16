<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Manage Profiles</h5>
        <div>
            <button wire:click="openCreateForm" class="btn btn-primary btn-sm m-1">
                Create New Profile
            </button>
        </div>
    </div>

    <x-toast />

    @if ($showCreateForm)
        <div class="card mb-4">
            <div class="card-header">
                <h6>{{ $editingProfile ? 'Edit Profile' : 'Create New Profile' }}</h6>
            </div>
            <div class="card-body">
                <form wire:submit.prevent="saveProfile">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Profile Name</label>
                                <input type="text" wire:model="name" class="form-control">
                                @error('name')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Profile Type</label>
                                <input type="text" wire:model="type" class="form-control"
                                    placeholder="e.g., Personal, Business, Tax">
                                @error('type')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" wire:model="is_default" class="form-check-input" id="is_default">
                                <label class="form-check-label" for="is_default">Set as Default Profile</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" wire:model="is_active" class="form-check-input" id="is_active">
                                <label class="form-check-label" for="is_active">Active Profile</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-success me-2">Save Profile</button>
                        <button type="button" wire:click="cancel" class="btn btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="row">
        @forelse($profiles as $profile)
            <div class="col-lg-6 col-md-12 mb-3">
                @if ($profile['id'] == $editingProfile)
                    <div class="card border-warning">
                        <div class="card-header">
                            <h6>Edit Profile</h6>
                        </div>
                        <div class="card-body">
                            <form wire:submit.prevent="saveProfile">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Profile Name</label>
                                            <input type="text" wire:model="name" class="form-control">
                                            @error('name')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Profile Type</label>
                                            <input type="text" wire:model="type" class="form-control"
                                                placeholder="e.g., Personal, Business, Tax">
                                            @error('type')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" wire:model="is_default" class="form-check-input"
                                                id="edit_is_default">
                                            <label class="form-check-label" for="edit_is_default">Set as Default
                                                Profile</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" wire:model="is_active" class="form-check-input"
                                                id="edit_is_active">
                                            <label class="form-check-label" for="edit_is_active">Active Profile</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <button type="submit" class="btn btn-success me-2">Save Profile</button>
                                    <button type="button" wire:click="cancel" class="btn btn-secondary">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="card {{ $profile['is_default'] ? 'border-primary' : '' }}">
                        <div
                            class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center">
                            <h6 class="mb-2 mb-sm-0">
                                {{ $profile['name'] }}
                                @if ($profile['is_default'])
                                    <span class="badge bg-primary">Default</span>
                                @endif
                            </h6>
                            <div class="d-flex flex-wrap gap-1">
                                <button wire:click="editProfile({{ $profile['id'] }})"
                                    class="btn btn-sm btn-outline-primary">Edit</button>
                                <button wire:click="deleteProfile({{ $profile['id'] }})"
                                    wire:confirm="Are you sure you want to delete this profile?"
                                    class="btn btn-sm btn-outline-danger">Delete</button>
                                @if (!$profile['is_default'])
                                    <button wire:click="setDefaultProfile({{ $profile['id'] }})"
                                        class="btn btn-sm btn-outline-success">Set Default</button>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <p><strong>Type:</strong> {{ $profile['type'] }}</p>
                            <p><strong>Status:</strong>
                                <span class="badge {{ $profile['is_active'] ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $profile['is_active'] ? 'Active' : 'Inactive' }}
                                </span>
                            </p>
                            <p><strong>Documents:</strong> {{ count($profile['data'] ?? []) }}</p>
                            <p><strong>Created:</strong>
                                {{ \Carbon\Carbon::parse($profile['created_at'])->format('M j, Y') }}</p>
                            <button wire:click="viewExtractedData({{ $profile['id'] }})"
                                class="btn btn-info btn-sm w-100">
                                <i class="fas fa-eye"></i> View Extracted Data
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">
                    <h6>No profiles found</h6>
                    <p>Create your first profile to start organizing your documents.</p>
                    <div>
                        <button wire:click="openCreateForm" class="btn btn-primary">
                            Create Profile
                        </button>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>
