@if (session()->has('error'))
    <div class="alert alert-danger">
        <strong>Upload Errors:</strong><br>
        {!! nl2br(e(session('error'))) !!}
    </div>
@endif

<form wire:submit.prevent="uploadDocument">
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="mb-3">
        <label for="profile" class="form-label">Select Profile</label>
        <select wire:model="selectedProfile" class="form-select" id="profile">
            <option value="">Choose a profile...</option>
            @foreach ($profiles as $profile)
                <option value="{{ $profile->id }}">{{ $profile->name }} ({{ $profile->type }})</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="documents" class="form-label">Document Files</label>
        <input type="file" wire:model="documents" class="form-control" id="documents" accept=".pdf,.jpg,.jpeg,.png"
            multiple>
        <div class="form-text">Supported formats: PDF, JPG, PNG (max 5MB per file, up to 10 files)<br>
            <small class="text-muted">💡 <strong>Tips:</strong> For best results, ensure PDFs contain
                selectable text (not just images). High-quality scans work better than photos.</small>
        </div>
    </div>

    @if ($documents && count($documents) > 0)
        <div class="mb-3">
            <div class="alert alert-info">
                <strong>Selected files ({{ count($documents) }}):</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($documents as $document)
                        <li>{{ $document->getClientOriginalName() }}
                            ({{ number_format($document->getSize() / 1024 / 1024, 2) }} MB)
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="uploadDocument">
        <span wire:loading.remove wire:target="uploadDocument">Upload & Process Files</span>
        <span wire:loading wire:target="uploadDocument">
            <span class="spinner-border spinner-border-sm" role="status"></span>
            Processing Files...
        </span>
    </button>
</form>
