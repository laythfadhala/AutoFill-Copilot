{{-- Field Row Component --}}
@props(['type', 'fieldKey', 'fieldValue', 'isEditing' => false, 'fileName' => null])

<tr>
    @if ($isEditing)
        <td>
            <textarea id="field-key-textarea-{{ $type }}-{{ $fileName ?? '' }}-{{ $fieldKey }}"
                wire:model="{{ $type === 'extracted' ? 'fieldKey' : 'fieldKey' }}" class="form-control form-control-sm"
                placeholder="Field Key" rows="3">
            </textarea>
        </td>
        <td>
            <textarea id="field-value-textarea-{{ $type }}-{{ $fileName ?? '' }}-{{ $fieldKey }}"
                wire:model="{{ $type === 'extracted' ? 'fieldValue' : 'fieldValue' }}" class="form-control form-control-sm"
                rows="3" placeholder="Field Value">{{ $fieldValue }}</textarea>
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
            @if (is_string($fieldValue))
                <div style="word-wrap: break-word; max-width: 300px;">{!! nl2br(e($fieldValue)) !!}</div>
            @elseif (is_array($fieldValue) || is_object($fieldValue))
                <div style="word-wrap: break-word; max-width: 300px;"><code>{!! nl2br(e(json_encode($fieldValue, JSON_PRETTY_PRINT))) !!}</code></div>
            @endif
        </td>
        <td>
            @if (!is_array($fieldValue) && !is_object($fieldValue))
                <button id="edit-btn-{{ $type }}-{{ $fileName ?? '' }}-{{ $fieldKey }}"
                    wire:click="{{ $type === 'extracted' ? 'editExtractedField(\'' . addslashes($fileName) . '\', \'' . addslashes($fieldKey) . '\')' : 'editManualField(\'' . addslashes($fieldKey) . '\', \'' . addslashes(is_array($fieldValue) || is_object($fieldValue) ? json_encode($fieldValue) : $fieldValue) . '\')' }}"
                    class="btn btn-sm btn-outline-primary me-1">Edit</button>
            @endif
            <button id="delete-btn-{{ $type }}-{{ $fileName ?? '' }}-{{ $fieldKey }}"
                wire:click="{{ $type === 'extracted' ? 'deleteExtractedField(\'' . addslashes($fileName) . '\', \'' . addslashes($fieldKey) . '\')' : 'deleteManualField(\'' . addslashes($fieldKey) . '\')' }}"
                wire:confirm="Are you sure you want to delete this {{ $type }} field?"
                class="btn btn-sm btn-outline-danger">Delete</button>
        </td>
    @endif
</tr>
