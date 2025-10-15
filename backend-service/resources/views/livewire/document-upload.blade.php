<div>
    <div class="card">
        <div class="card-header">
            <h5>Upload Documents for AI Processing</h5>
        </div>
        <div class="card-body">
            @if (session()->has('error'))
                <div class="alert alert-danger">
                    <strong>Upload Errors:</strong><br>
                    {!! nl2br(e(session('error'))) !!}
                </div>
            @endif

            <form wire:submit.prevent="uploadDocument">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Please fix the following errors:</strong>
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
                    @error('selectedProfile')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="documents" class="form-label">Document Files</label>
                    <input type="file" wire:model="documents" class="form-control" id="documents"
                        accept=".pdf,.jpg,.jpeg,.png" multiple>
                    @error('documents')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    @error('documents.*')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Supported formats: PDF, JPG, PNG (max 10MB per file, up to 10 files)<br>
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

                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"
                    wire:target="uploadDocument">
                    <span wire:loading.remove wire:target="uploadDocument">Upload & Process Files</span>
                    <span wire:loading wire:target="uploadDocument">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                        Processing Files...
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

            @if (!empty($jobStatuses))
                <div class="mt-3" wire:poll.5s="checkJobStatuses">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Background Processing Status</h6>
                            <div>
                                <small class="text-muted me-2">
                                    <i class="fas fa-clock"></i> Auto-refreshing every 5s
                                </small>
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    wire:click="checkJobStatuses">
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
                                            <div class="progress-bar" role="progressbar"
                                                style="width: {{ $batch->progress() }}%"
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
                                                    @else
                                                        <span
                                                            class="badge bg-secondary">{{ ucfirst($job['status']) }}</span>
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
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5>Your Document History</h5>
        </div>
        <div class="card-body">
            @if (empty($uploadedDocuments))
                <p class="text-muted">No documents uploaded yet.</p>
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> Troubleshooting Common Issues</h6>
                    <ul class="mb-0 small">
                        <li><strong>"Could not extract text"</strong> - PDF might contain only images. Try a different
                            PDF or scan with OCR.</li>
                        <li><strong>"OCR tool not installed"</strong> - Contact administrator to install tesseract and
                            poppler-utils.</li>
                        <li><strong>"AI service not configured"</strong> - TOGETHER_API_KEY needs to be set in
                            environment.</li>
                        <li><strong>"File too large"</strong> - Compress your PDF or use a smaller image (max 5MB).</li>
                        <li><strong>"Invalid format"</strong> - Only PDF, JPG, and PNG files are supported.</li>
                    </ul>
                </div>
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
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-start">
                                                    <div class="ms-2 me-auto">
                                                        <div class="fw-bold">{{ $doc['filename'] ?? 'Unknown' }}</div>
                                                        <small class="text-muted">Uploaded:
                                                            {{ \Carbon\Carbon::parse($doc['uploaded_at'])->format('M j, Y H:i') }}</small>
                                                        @if (isset($doc['extracted_data']))
                                                            @if (isset($doc['extracted_data']['error']))
                                                                <br><small class="text-danger">⚠️
                                                                    {{ $doc['extracted_data']['error'] }}</small>
                                                            @elseif(is_array($doc['extracted_data']) && count($doc['extracted_data']) > 0)
                                                                <br><small class="text-success">✅
                                                                    {{ count($doc['extracted_data']) }} fields
                                                                    extracted</small>
                                                            @else
                                                                <br><small class="text-warning">⚠️ No data
                                                                    extracted</small>
                                                            @endif
                                                        @endif
                                                    </div>
                                                    @if (isset($doc['extracted_data']) && is_array($doc['extracted_data']) && !isset($doc['extracted_data']['error']))
                                                        <span
                                                            class="badge bg-success rounded-pill">{{ count($doc['extracted_data']) }}</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                        @if ($item['count'] > 3)
                                            <div class="mt-2">
                                                <small class="text-muted">... and {{ $item['count'] - 3 }} more
                                                    documents</small>
                                            </div>
                                        @endif
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
