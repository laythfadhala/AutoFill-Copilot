@php
    $message = session('success') ?: session('error');
    $type = session('success') ? 'success' : (session('error') ? 'error' : null);
@endphp

@if ($message && $type)
    @php
        $bgColor = $type === 'error' ? '#dc3545' : '#28a745';
        $icon = $type === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill';
        $title = $type === 'error' ? 'Error!' : 'Success!';
        $toastId = 'toast-' . uniqid(); // moved up for reuse
    @endphp

    <style>
        .simple-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: {{ $bgColor }};
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1050;
            animation: toastSlideIn 0.3s ease-out, toastFadeOut 0.3s ease-in 1.7s forwards;
            display: flex;
            align-items: center;
            gap: 8px;
            max-width: 300px;
        }

        .toast-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.2em;
            cursor: pointer;
            padding: 0;
            margin-left: 8px;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .toast-close:hover {
            opacity: 1;
        }

        .toast-checkbox {
            display: none;
        }

        .toast-checkbox:checked+.simple-toast {
            display: none;
        }

        @keyframes toastSlideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes toastFadeOut {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
            }
        }
    </style>

    <input type="checkbox" id="{{ $toastId }}" class="toast-checkbox">
    <div wire:key="{{ uniqid('toast_') }}" class="simple-toast">
        <i class="bi {{ $icon }}"></i>
        <div>
            <strong>{{ $title }}</strong> {{ $message }}
        </div>
        <label for="{{ $toastId }}" class="toast-close" title="Close">&times;</label>
    </div>
@endif
