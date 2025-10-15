<div>
    <div class="card">
        <div class="card-header">
            <h5>Upload Document for AI Processing</h5>
        </div>
        <div class="card-body">
            @if (session()->has('message'))
                <div class="alert alert-success">
                    {{ session('message') }}
                </div>
            @endif

            <form wire:submit.prevent="uploadDocument">
                <div class="mb-3">
                    <label for="profile" class="form-label">Select Profile</label>
                    <select wire:model="selectedProfile" class="form-select" id="profile">
                        <option value="">Choose a profile...</option>
                        @foreach ($profiles as $profile)
                            <option value="{{ $profile->id }}">{{ $profile->name }} ({{ $profile->type }})</option>
                        @endforeach
                    </select>
                    @error('selectedProfile')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="document" class="form-label">Document File</label>
                    <input type="file" wire:model="document" class="form-control" id="document"
                        accept=".pdf,.jpg,.jpeg,.png">
                    @error('document')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Supported formats: PDF, JPG, PNG (max 10MB)</div>
                </div>

                @if ($document)
                    <div class="mb-3">
                        <div class="alert alert-info">
                            Selected: {{ $document->getClientOriginalName() }}
                        </div>
                    </div>
                @endif

                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"
                    wire:target="uploadDocument">
                    <span wire:loading.remove wire:target="uploadDocument">Upload & Process</span>
                    <span wire:loading wire:target="uploadDocument">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                        Processing...
                    </span>
                </button>
            </form>

            @if ($isProcessing)
                <div class="mt-3">
                    <div class="alert alert-info">
                        <strong>Processing:</strong> {{ $processingStatus }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5>Your Document History</h5>
        </div>
        <div class="card-body">
            @if (empty($uploadedDocuments))
                <p class="text-muted">No documents uploaded yet.</p>
            @else
                <div class="row">
                    @foreach ($uploadedDocuments as $item)
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-header">
                                    <strong>{{ $item['profile']->name }}</strong>
                                    <span class="badge bg-secondary">{{ $item['count'] }} documents</span>
                                </div>
                                <div class="card-body">
                                    @if ($item['count'] > 0)
                                        <ul class="list-group list-group-flush">
                                            @foreach (array_slice($item['documents'], -3) as $doc)
                                                <li class="list-group-item">
                                                    <small
                                                        class="text-muted">{{ $doc['filename'] ?? 'Unknown' }}</small>
                                                    <br>
                                                    <small>Uploaded:
                                                        {{ \Carbon\Carbon::parse($doc['uploaded_at'])->format('M j, Y H:i') }}</small>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="text-muted mb-0">No documents yet.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
