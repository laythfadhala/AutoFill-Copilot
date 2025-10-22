<div>
    <div class="card">
        <div class="card-header">
            <h5>Upload Documents for AI Processing</h5>
        </div>
        <div class="card-body">
            @include('components.livewire.upload-form')
            @include('components.livewire.processing-status')
        </div>
    </div>

    @include('components.document-history')
</div>
