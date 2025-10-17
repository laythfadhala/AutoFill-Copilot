<div>
    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    <div class="row">
        @include('components.profile-selector')

        <!-- Data Fields -->
        <div class="col-md-8" style="height: 100vh; overflow-y: auto;">
            @if ($selectedProfile)
                <div class="card-body">
                    <ul id="dataTabs" class="nav nav-tabs d-flex justify-content-between align-items-center"
                        role="tablist">
                        <div class="d-flex">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $activeTab === 'extracted' ? 'active' : '' }}"
                                    wire:click="$set('activeTab', 'extracted')" type="button" role="tab">
                                    Extracted Data ({{ count($groupedDocuments) }} documents)
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $activeTab === 'manual' ? 'active' : '' }}"
                                    wire:click="$set('activeTab', 'manual')" type="button" role="tab">
                                    Manual Fields ({{ count($manualFields) }} fields)
                                </button>
                            </li>
                        </div>
                        <span class="badge bg-info">{{ $totalFieldCount }} total fields</span>
                    </ul>

                    <div class="tab-content mt-3">
                        @include('components.extracted-data-tab')
                        @include('components.manual-fields-tab')
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        <h6 class="text-muted">Select a profile to manage data fields</h6>
                        <p class="text-muted">Choose a profile from the left panel to view and edit its extracted data
                            fields.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
