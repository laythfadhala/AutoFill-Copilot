<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Data Manager</h5>
        <button wire:click="addNewField" class="btn btn-primary btn-sm"
            @if (!$selectedProfile) disabled @endif>Add New Field</button>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    <div class="row">
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

        <!-- Data Fields -->
        <div class="col-md-8">
            @if ($selectedProfile)
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6>Data Fields for "{{ $selectedProfile['name'] }}"</h6>
                        <span class="badge bg-info">{{ count($dataFields) }} fields</span>
                    </div>
                    <div class="card-body">
                        @if ($editingField)
                            <div class="card mb-3 border-warning">
                                <div class="card-header">
                                    <h6>{{ $editingField === 'new' ? 'Add New Field' : 'Edit Field' }}</h6>
                                </div>
                                <div class="card-body">
                                    <form wire:submit.prevent="saveField">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Field Key</label>
                                                    <input type="text" wire:model="fieldKey" class="form-control"
                                                        @if ($editingField !== 'new') readonly @endif>
                                                    @error('fieldKey')
                                                        <div class="text-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Field Type</label>
                                                    <select wire:model="fieldType" class="form-select">
                                                        <option value="text">Text</option>
                                                        <option value="email">Email</option>
                                                        <option value="number">Number</option>
                                                        <option value="date">Date</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Field Value</label>
                                            @if ($fieldType === 'date')
                                                <input type="date" wire:model="fieldValue" class="form-control">
                                            @elseif($fieldType === 'email')
                                                <input type="email" wire:model="fieldValue" class="form-control">
                                            @elseif($fieldType === 'number')
                                                <input type="number" wire:model="fieldValue" class="form-control">
                                            @else
                                                <textarea wire:model="fieldValue" class="form-control" rows="3"></textarea>
                                            @endif
                                            @error('fieldValue')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div>
                                            <button type="submit" class="btn btn-success me-2">Save Field</button>
                                            <button type="button" wire:click="cancelEdit"
                                                class="btn btn-secondary">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif

                        @if (count($dataFields) > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Field Key</th>
                                            <th>Field Value</th>
                                            <th>Type</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataFields as $key => $value)
                                            <tr>
                                                <td><code>{{ $key }}</code></td>
                                                <td>
                                                    @if (is_string($value) && strlen($value) > 50)
                                                        {{ substr($value, 0, 50) }}...
                                                    @elseif(is_array($value) || is_object($value))
                                                        <code>{{ json_encode($value) }}</code>
                                                    @else
                                                        {{ $value }}
                                                    @endif
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-secondary">{{ $this->detectFieldType($value) }}</span>
                                                </td>
                                                <td>
                                                    <button wire:click="editField('{{ $key }}')"
                                                        class="btn btn-sm btn-outline-primary me-1">Edit</button>
                                                    <button wire:click="deleteField('{{ $key }}')"
                                                        wire:confirm="Are you sure you want to delete this field?"
                                                        class="btn btn-sm btn-outline-danger">Delete</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <p class="text-muted">No data fields found for this profile.</p>
                                <button wire:click="addNewField" class="btn btn-primary">Add First Field</button>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        <h6 class="text-muted">Select a profile to manage data fields</h6>
                        <p class="text-muted">Choose a profile from the left panel to view and edit its extracted data
                            fields.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
