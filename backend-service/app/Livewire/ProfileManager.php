<?php

namespace App\Livewire;

use App\Livewire\Traits\FlashesValidationErrors;
use Livewire\Component;
use App\Models\UserProfile;

class ProfileManager extends Component
{
    use FlashesValidationErrors;

    public $profiles = [];
    public $showCreateForm = false;
    public $editingProfile = null;
    public $isTokenLimitReached = false;
    public $isProfileLimitReached = false;

    public $name = '';
    public $type = '';
    public $is_default = false;
    public $is_active = true;

    protected $rules = [
        'name' => 'required|max:255',
        'type' => 'required|max:255',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function mount()
    {
        $this->loadProfiles();
    }

    public function loadProfiles()
    {
        $this->profiles = auth()->user()->userProfiles()->get()->toArray();
    }

    public function openCreateForm()
    {
        $this->resetForm();
        $this->showCreateForm = true;
        $this->editingProfile = null;
    }

    public function editProfile($profileId)
    {
        $profile = collect($this->profiles)->firstWhere('id', $profileId);
        if ($profile) {
            $this->editingProfile = $profileId;
            $this->name = $profile['name'];
            $this->type = $profile['type'];
            $this->is_default = $profile['is_default'];
            $this->is_active = $profile['is_active'];
        }
    }

    public function saveProfile()
    {
        $this->validateAndFlash([
            'name' => $this->name,
            'type' => $this->type,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
        ]);

        $user = auth()->user();

        // Check profile limit for new profiles
        if (!$this->editingProfile && !$user->canCreateProfile()) {
            $this->addError('limit', 'You\'ve reached your profile limit (' . $user->getProfileLimit() . '). Upgrade your plan to create more profiles.');
            return;
        }

        if ($profile = UserProfile::find($this->editingProfile)) {
            // Update existing profile
            $profile->update([
                'name' => $this->name,
                'type' => $this->type,
                'is_default' => $this->is_default,
                'is_active' => $this->is_active,
            ]);

            session()->flash('success', 'Profile updated successfully!');
        } else {
            // Create new profile
            UserProfile::create([
                'user_id' => auth()->id(),
                'name' => $this->name,
                'type' => $this->type,
                'is_default' => $this->is_default,
                'is_active' => $this->is_active,
                'data' => [],
            ]);

            session()->flash('success', 'Profile created successfully!');
        }

        $this->resetForm();
        $this->loadProfiles();

        // Emit event to refresh other components
        $this->dispatch('profileUpdated');
    }

    public function deleteProfile($profileId)
    {
        $profile = UserProfile::find($profileId);
        if ($profile && $profile->user_id === auth()->id()) {
            $profile->delete();
            session()->flash('success', 'Profile deleted successfully!');
            $this->loadProfiles();

            // Emit event to refresh other components
            $this->dispatch('profileUpdated');
        }
    }

    public function setDefaultProfile($profileId)
    {
        // Remove default from all profiles
        UserProfile::where('user_id', auth()->id())->update(['is_default' => false]);

        // Set new default
        $profile = UserProfile::find($profileId);
        if ($profile && $profile->user_id === auth()->id()) {
            $profile->update(['is_default' => true]);
            session()->flash('success', 'Default profile updated!');
            $this->loadProfiles();

            // Emit event to refresh other components
            $this->dispatch('profileUpdated');
        }
    }

    public function viewExtractedData($profileId)
    {
        // Store the profile ID in session so DataManager can pick it up
        session(['selected_profile_for_data_tab' => $profileId]);

        // Dispatch event to parent Dashboard component to switch tabs
        $this->dispatch('switchToDataTab')->to(Dashboard::class);
    }

    public function resetForm()
    {
        $this->name = '';
        $this->type = '';
        $this->is_default = false;
        $this->is_active = true;
        $this->showCreateForm = false;
        $this->editingProfile = null;
        $this->resetValidation();
    }

    public function cancel()
    {
        $this->resetForm();
    }

    public function render()
    {
        return view('livewire.profile-manager');
    }
}
