<div>
    <!-- Tab Navigation -->
    <ul class="nav nav-tabs flex-column flex-sm-row" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'profiles' ? 'active' : '' }}" wire:click="setActiveTab('profiles')"
                type="button">
                Manage Profiles
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'upload' ? 'active' : '' }}" wire:click="setActiveTab('upload')"
                type="button">
                Upload Documents
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'data' ? 'active' : '' }}" wire:click="setActiveTab('data')"
                type="button">
                Extracted Data
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content mt-4">
        @if ($activeTab === 'profiles')
            <div class="tab-pane active">
                @livewire('profile-manager')
            </div>
        @elseif($activeTab === 'upload')
            <div class="tab-pane active">
                @livewire('document-upload')
            </div>
        @elseif($activeTab === 'data')
            <div class="tab-pane active">
                @livewire('data-manager')
            </div>
        @endif
    </div>
</div>
