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
                        <input type="text" wire:model="fieldKey" class="form-control">
                        @error('fieldKey')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Field Type</label>
                        <select wire:model="manualFieldType" class="form-select">
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
                @if ($manualFieldType === 'date')
                    <input type="date" wire:model="fieldValue" class="form-control">
                @elseif($manualFieldType === 'email')
                    <input type="email" wire:model="fieldValue" class="form-control">
                @elseif($manualFieldType === 'number')
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
                <button type="button" wire:click="cancelEdit" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>
