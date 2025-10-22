<div>
    <x-toast />

    <div class="row">
        @include('components.livewire.profile-selector')

        <!-- Data Fields -->
        <div class="col-lg-8 col-md-12" style="max-height: 80vh; overflow-y: auto;">
            @if ($selectedProfile)
                <div class="card-body">
                    <ul id="dataTabs"
                        class="nav nav-tabs flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center"
                        role="tablist">
                        <div class="d-flex flex-column flex-sm-row">
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
                        <span class="badge bg-info mt-2 mt-sm-0">{{ $totalFieldCount }} total fields</span>
                    </ul>

                    <div class="tab-content mt-3">
                        @include('components.livewire.extracted-data-tab')
                        @include('components.livewire.manual-fields-tab')
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
