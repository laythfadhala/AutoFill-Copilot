<!-- Extracted Data Tab -->
<div class="tab-pane {{ $activeTab === 'extracted' ? 'active' : '' }}">
    @if ($editingField === 'extracted')
        @include('components.edit-field-form-modal')
    @endif

    @if (count($groupedDocuments) > 0)
        @foreach ($groupedDocuments as $fileName => $documentGroup)
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ $documentGroup['title'] }}</h6>
                        <small class="text-muted">{{ count($documentGroup['documents']) }}
                            document(s)</small>
                    </div>
                    <button wire:click="deleteDocumentGroup('{{ $fileName }}')"
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
                                                    wire:click="editExtractedField('{{ $fileName }}', '{{ $key }}')"
                                                    class="btn btn-sm btn-outline-primary me-1">Edit</button>
                                                <button
                                                    wire:click="deleteExtractedField('{{ $fileName }}', '{{ $key }}')"
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
