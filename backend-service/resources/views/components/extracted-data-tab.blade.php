<!-- Extracted Data Tab -->
<div class="tab-pane {{ $activeTab === 'extracted' ? 'active' : '' }}">

    @if (count($groupedDocuments) > 0)
        <div class="accordion" id="documentsAccordion">
            @foreach ($groupedDocuments as $fileName => $documentGroup)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading{{ $loop->index }}">
                        <div class="d-flex align-items-center justify-content-between">
                            <button id="{{ $loop->index }}" wire:click="deleteDocumentGroup('{{ $fileName }}')"
                                wire:confirm="Are you sure you want to delete all fields from this document? This action cannot be undone."
                                class="btn btn-sm btn-outline-danger ms-4" onclick="event.stopPropagation()">
                                <i class="bi bi-trash"></i>
                            </button>
                            <button
                                class="accordion-button bg-transparent shadow-none focus:shadow-none focus:outline-none @if (!in_array($loop->index, $expandedAccordions)) collapsed @endif d-flex justify-content-between align-items-center"
                                type="button" wire:click.prevent="toggleAccordion({{ $loop->index }})"
                                aria-expanded="@if (in_array($loop->index, $expandedAccordions)) true @else false @endif">
                                <div>
                                    <h6 class="mb-0">{{ $documentGroup['title'] }}</h6>
                                    <small class="text-muted">{{ count($documentGroup['fields']) }}
                                        @if (count($documentGroup['fields']) === 1)
                                            field
                                        @else
                                            fields
                                        @endif
                                    </small>
                                </div>
                            </button>
                        </div>
                    </h2>
                    <div id="collapse{{ $loop->index }}"
                        class="accordion-collapse collapse @if (in_array($loop->index, $expandedAccordions)) show @endif"
                        aria-labelledby="heading{{ $loop->index }}">
                        <div class="accordion-body">
                            @if (count($documentGroup['fields']) > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Field</th>
                                                <th>Value</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($documentGroup['fields'] as $key => $value)
                                                <x-field-row :type="'extracted'" :fieldKey="$key" :fieldValue="$value"
                                                    :isEditing="$editingField === 'extracted' && $editingDocumentName === $fileName && $originalFieldKey === $key" :fileName="$fileName" />
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted mb-0">No fields extracted from this document.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-4">
            <p class="text-muted">No extracted data found for this profile.</p>
            <p class="text-muted small">Upload documents to see extracted data here.</p>
        </div>
    @endif
</div>
