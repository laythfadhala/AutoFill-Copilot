@if ($isProcessing)
    <div class="mt-3">
        <div class="alert alert-info">
            <strong>Processing:</strong> {{ $processingStatus }}
        </div>
    </div>
@endif

@if (!empty($jobStatuses))
    <div class="mt-3" wire:poll.5s="checkJobStatuses">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Background Processing Status</h6>
                <div>
                    <small class="text-muted me-2">
                        <i class="fas fa-clock"></i> Auto-refreshing every 5s
                    </small>
                    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="checkJobStatuses">
                        <i class="fas fa-sync-alt"></i> Refresh Now
                    </button>
                </div>
            </div>
            <div class="card-body">
                @if ($currentBatchId)
                    @php
                        $batch = \Illuminate\Support\Facades\Bus::findBatch($currentBatchId);
                    @endphp
                    @if ($batch)
                        <div class="mb-3">
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: {{ $batch->progress() }}%"
                                    aria-valuenow="{{ $batch->processedJobs() }}" aria-valuemin="0"
                                    aria-valuemax="{{ $batch->totalJobs }}">
                                    {{ $batch->processedJobs() }} / {{ $batch->totalJobs }} files
                                    processed
                                </div>
                            </div>
                            <small class="text-muted mt-1">
                                Progress: {{ $batch->progress() }}% complete
                                @if ($batch->finished())
                                    (Batch completed)
                                @elseif($batch->cancelled())
                                    (Batch cancelled)
                                @else
                                    (Processing...)
                                @endif
                            </small>
                        </div>
                    @endif
                @endif

                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Status</th>
                                <th>Queued At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($jobStatuses as $job)
                                <tr>
                                    <td>{{ $job['filename'] }}</td>
                                    <td>
                                        @if ($job['status'] === 'queued')
                                            <span class="badge bg-warning">Queued</span>
                                        @elseif ($job['status'] === 'processing')
                                            <span class="badge bg-info">
                                                <span class="spinner-border spinner-border-sm me-1"
                                                    role="status"></span>
                                                Processing
                                            </span>
                                        @elseif ($job['status'] === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif ($job['status'] === 'failed')
                                            <span class="badge bg-danger">Failed</span>
                                        @elseif ($job['status'] === 'deleted')
                                            <span class="badge bg-secondary">Deleted</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($job['status']) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($job['queued_at'])->format('M j, H:i:s') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-2">
                    <small class="text-muted">
                        💡 Files are processed in the background. Click "Refresh Status" to check progress.
                        Completed files will appear in your document history below.
                    </small>
                </div>
            </div>
        </div>
    </div>
@endif

@if (!$isProcessing && session()->has('error'))
    <div class="mt-3">
        <div class="alert alert-danger">
            <strong>Processing Errors:</strong><br>
            {!! nl2br(e(session('error'))) !!}
        </div>
    </div>
@endif
