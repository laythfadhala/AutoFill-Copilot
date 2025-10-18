{{-- Field Row Component --}}
@props(['type', 'fieldKey', 'fieldValue', 'isEditing' => false, 'fileName' => null])

<tr>
    @if ($isEditing)
        <td><input id="field-key-input-{{ $type }}-{{ $fileName ?? '' }}-{{ $fieldKey }}"
                wire:model="{{ $type === 'extracted' ? 'fieldKey' : 'fieldKey' }}" class="form-control form-control-sm"
                placeholder="Field Key"></td>
        <td>
            @if (is_array($fieldValue) || is_object($fieldValue))
                <textarea id="field-value-textarea-{{ $type }}-{{ $fileName ?? '' }}-{{ $fieldKey }}"
                    wire:model="{{ $type === 'extracted' ? 'fieldValue' : 'fieldValue' }}" class="form-control form-control-sm"
                    rows="3">{{ json_encode($fieldValue) }}</textarea>
            @else
                <input id="field-value-input-{{ $type }}-{{ $fileName ?? '' }}-{{ $fieldKey }}"
                    wire:model="{{ $type === 'extracted' ? 'fieldValue' : 'fieldValue' }}"
                    class="form-control form-control-sm" placeholder="Field Value">
            @endif
        </td>
        <td>
            <button id="save-btn-{{ $type }}-{{ $fileName ?? '' }}-{{ $fieldKey }}"
                wire:click="{{ $type === 'extracted' ? 'saveExtractedField' : 'saveField' }}"
                class="btn btn-sm btn-success me-1">Save</button>
            <button id="cancel-btn-{{ $type }}-{{ $fileName ?? '' }}-{{ $fieldKey }}"
                wire:click="cancelEdit" class="btn btn-sm btn-secondary">Cancel</button>
        </td>
    @else
        <td><code>{{ $fieldKey }}</code></td>
        <td>
            @if (is_string($fieldValue) && strlen($fieldValue) > 50)
                {{ substr($fieldValue, 0, 50) }}...
            @elseif(is_array($fieldValue) || is_object($fieldValue))
                <code>{{ json_encode($fieldValue) }}</code>
            @else
                {{ $fieldValue }}
            @endif
        </td>
        <td>
            <button id="edit-btn-{{ $type }}-{{ $fileName ?? '' }}-{{ $fieldKey }}"
                wire:click="{{ $type === 'extracted' ? 'editExtractedField(\'' . addslashes($fileName) . '\', \'' . addslashes($fieldKey) . '\')' : 'editManualField(\'' . addslashes($fieldKey) . '\', \'' . addslashes(is_array($fieldValue) || is_object($fieldValue) ? json_encode($fieldValue) : $fieldValue) . '\')' }}"
                class="btn btn-sm btn-outline-primary me-1">Edit</button>
            <button id="delete-btn-{{ $type }}-{{ $fileName ?? '' }}-{{ $fieldKey }}"
                wire:click="{{ $type === 'extracted' ? 'deleteExtractedField(\'' . addslashes($fileName) . '\', \'' . addslashes($fieldKey) . '\')' : 'deleteManualField(\'' . addslashes($fieldKey) . '\')' }}"
                wire:confirm="Are you sure you want to delete this {{ $type }} field?"
                class="btn btn-sm btn-outline-danger">Delete</button>
        </td>
    @endif
</tr>
