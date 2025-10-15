<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Data Manager</h5>
        <button wire:click="addNewField" class="btn btn-primary btn-sm"
            @if (!$selectedProfile || $activeTab !== 'manual') disabled @endif>Add New Field</button>
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
                        <span class="badge bg-info">{{ $totalFieldCount }} total fields</span>
                    </div>
                    <div class="card-body">
                        @if ($editingField)
                            <div class="card mb-3 border-warning">
                                <div class="card-header">
                                    <h6>{{ $editingField === 'new' ? 'Add New Field' : ($editingField === 'extracted' ? 'Edit Extracted Field' : 'Edit Field') }}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <form wire:submit.prevent="saveField">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Field Key</label>
                                                    <input type="text" wire:model="fieldKey" class="form-control"
                                                        @if ($editingField === 'manual') readonly @endif>
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

                        <!-- Tabs -->
                        <ul class="nav nav-tabs" id="dataTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $activeTab === 'extracted' ? 'active' : '' }}"
                                    wire:click="$set('activeTab', 'extracted')" type="button" role="tab">Extracted
                                    Data ({{ count($groupedDocuments) }} documents)</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $activeTab === 'manual' ? 'active' : '' }}"
                                    wire:click="$set('activeTab', 'manual')" type="button" role="tab">Manual Fields
                                    ({{ count($manualFields) }} fields)</button>
                            </li>
                        </ul>

                        <div class="tab-content mt-3">
                            <!-- Extracted Data Tab -->
                            <div class="tab-pane {{ $activeTab === 'extracted' ? 'active' : '' }}">
                                @if (count($groupedDocuments) > 0)
                                    @foreach ($groupedDocuments as $documentGroup)
                                        <div class="card mb-3">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0">{{ $documentGroup['title'] }}</h6>
                                                    <small class="text-muted">{{ count($documentGroup['documents']) }}
                                                        document(s)</small>
                                                </div>
                                                <button
                                                    wire:click="deleteDocumentGroup('{{ $documentGroup['title'] }}')"
                                                    wire:confirm="Are you sure you want to delete all fields from this document? This action cannot be undone."
                                                    class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i> Delete Group
                                                </button>
                                            </div>
                                            <div class="card-body">
                                                @if (count($documentGroup['fields']) > 0)
                                                    <div class="table-responsive">
                                                        <table class="table table-sm">
                                                            <thead>
                                                                <tr>
                                                                    <th>Field</th>
                                                                    <th>Value</th>
                                                                    <th>Type</th>
                                                                    <th>Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($documentGroup['fields'] as $key => $value)
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
                                                                            <button
                                                                                wire:click="editExtractedField('{{ $documentGroup['title'] }}', '{{ $key }}')"
                                                                                class="btn btn-sm btn-outline-primary me-1">Edit</button>
                                                                            <button
                                                                                wire:click="deleteExtractedField('{{ $documentGroup['title'] }}', '{{ $key }}')"
                                                                                wire:confirm="Are you sure you want to delete this extracted field?"
                                                                                class="btn btn-sm btn-outline-danger">Delete</button>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @else
                                                    <p class="text-muted mb-0">No fields extracted from this document.
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center py-4">
                                        <p class="text-muted">No extracted data found for this profile.</p>
                                        <p class="text-muted small">Upload documents to see extracted data here.</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Manual Fields Tab -->
                            <div class="tab-pane {{ $activeTab === 'manual' ? 'active' : '' }}">
                                @if (count($manualFields) > 0)
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
                    @endif @if (count($manualFields) > 0)
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
                                    @foreach ($manualFields as $key => $value)
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
                            <p class="text-muted">No manual fields found for this profile.</p>
                            <button wire:click="addNewField" class="btn btn-primary">Add First Field</button>
                        </div>
                    @endif
                </div>
        </div>
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
