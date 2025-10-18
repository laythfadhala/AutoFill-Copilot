<!-- Manual Fields Tab -->
<div class="tab-pane {{ $activeTab === 'manual' ? 'active' : '' }}">
    @if ($editingField === 'new')
        @include('components.new-field-form-modal')
    @endif

    @if (count($manualFields) > 0)
        <div class="table-responsive">
            <button wire:click="addNewField" class="btn btn-outline-primary float-end py-0">Add +</button>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Field Key</th>
                        <th>Field Value</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($manualFields as $key => $value)
                        <x-field-row :type="'manual'" :fieldKey="$key" :fieldValue="$value" :isEditing="$editingField === 'manual' && $fieldKey == $key" />
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
