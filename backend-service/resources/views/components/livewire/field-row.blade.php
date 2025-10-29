{{-- Field Row Component --}}
@props(['type', 'fieldKey', 'fieldValue', 'isEditing' => false, 'fileName' => null])

<tr>
    @if ($isEditing)
        <td style="width: 30%;">
            <textarea id="field-key-textarea-{{ $type }}-{{ $fileName ?? '' }}-{{ $fieldKey }}"
                wire:model="{{ $type === 'extracted' ? 'fieldKey' : 'fieldKey' }}" class="form-control form-control-sm w-100"
                rows="2" placeholder="Field Key">{{ $fieldKey }}</textarea>
        </td>
        <td style="width: 60%;">
            <textarea id="field-value-textarea-{{ $type }}-{{ $fileName ?? '' }}-{{ $fieldKey }}"
                wire:model="{{ $type === 'extracted' ? 'fieldValue' : 'fieldValue' }}" class="form-control form-control-sm w-100"
                rows="2" placeholder="Field Value">{{ $fieldValue }}</textarea>
        </td>
        <td style="width: 10%;">
            <div class="btn-group btn-group-sm w-100">
                <button id="save-btn-{{ $type }}-{{ $fileName ?? '' }}-{{ $fieldKey }}"
                    wire:click="{{ $type === 'extracted' ? 'saveExtractedField' : 'saveField' }}"
                    class="btn btn-success flex-fill" title="Save changes">
                    <i class="bi bi-check-lg"></i>
                </button>
                <button id="cancel-btn-{{ $type }}-{{ $fileName ?? '' }}-{{ $fieldKey }}"
                    wire:click="cancelEdit" class="btn btn-secondary flex-fill" title="Cancel editing">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </td>
    @else
        <td style="width: 30%;">
            <div class="d-flex align-items-center h-100">
                <code class="text-muted w-100 text-break d-block"
                    style="word-wrap: break-word; white-space: pre-wrap; font-size: 0.875em;">{{ $fieldKey }}</code>
            </div>
        </td>
        <td style="width: 60%;">
            <div class="d-flex align-items-start h-100">
                @if (is_array($fieldValue) || is_object($fieldValue))
                    <code class="text-muted small w-100 text-break d-block"
                        style="word-wrap: break-word; white-space: pre-wrap;">{{ json_encode($fieldValue, JSON_PRETTY_PRINT) }}</code>
                @else
                    <span class="text-break w-100 d-block"
                        style="word-wrap: break-word; white-space: pre-wrap;">{{ $fieldValue }}</span>
                @endif
            </div>
        </td>
        <td style="width: 10%;">
            <div class="btn-group btn-group-sm w-100">
                @if (!is_array($fieldValue) && !is_object($fieldValue))
                    <button id="edit-btn-{{ $type }}-{{ $fileName ?? '' }}-{{ $fieldKey }}"
                        wire:click="{{ $type === 'extracted' ? 'editExtractedField(' . json_encode($fileName) . ', ' . json_encode($fieldKey) . ')' : 'editManualField(' . json_encode($fieldKey) . ', ' . json_encode($fieldValue) . ')' }}"
                        class="btn btn-outline-primary flex-fill" title="Edit field">
                        <i class="bi bi-pencil"></i>
                    </button>
                @endif
                <button id="delete-btn-{{ $type }}-{{ $fileName ?? '' }}-{{ $fieldKey }}"
                    wire:click="{{ $type === 'extracted' ? 'deleteExtractedField(' . json_encode($fileName) . ', ' . json_encode($fieldKey) . ')' : 'deleteManualField(' . json_encode($fieldKey) . ')' }}"
                    wire:confirm="Are you sure you want to delete this {{ $type }} field?"
                    class="btn btn-outline-danger flex-fill" title="Delete field">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </td>
    @endif
</tr>
