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
                                <strong>{{ $item['profile_name'] }}</strong>
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
