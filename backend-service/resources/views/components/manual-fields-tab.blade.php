<!-- Manual Fields Tab -->
<div class="tab-pane {{ $activeTab === 'manual' ? 'active' : '' }}">
    @if ($editingField === 'manual' || $editingField === 'new')
        @include('components.edit-field-form-modal')
    @endif

    @if (count($manualFields) > 0)
        <div class="table-responsive">
            <button wire:click="addNewField" class="btn btn-outline-primary float-end py-0">Add +</button>
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
                                <span class="badge bg-secondary">{{ $this->detectFieldType($value) }}</span>
                            </td>
                            <td>
                                <button wire:click="editManualField('{{ $key }}', '{{ $value }}')"
                                    class="btn btn-sm btn-outline-primary me-1">Edit</button>
                                <button wire:click="deleteManualField('{{ $key }}')"
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
