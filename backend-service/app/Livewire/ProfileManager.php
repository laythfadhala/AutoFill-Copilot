<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\UserProfile;

class ProfileManager extends Component
{
    public $profiles = [];
    public $showCreateForm = false;
    public $editingProfile = null;

    public $name = '';
    public $type = '';
    public $is_default = false;
    public $is_active = true;

    protected $rules = [
        'name' => 'required|string|max:255',
        'type' => 'required|string|max:255',
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

    public function showCreateForm()
    {
        try {
            $this->resetForm();
            $this->showCreateForm = true;
            $this->editingProfile = null;

            // Debug: Add a flash message to confirm the method is called
            session()->flash('debug', 'showCreateForm method called successfully');
        } catch (\Exception $e) {
            session()->flash('debug', 'Error in showCreateForm: ' . $e->getMessage());
        }
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
            $this->showCreateForm = true;
        }
    }

    public function saveProfile()
    {
        $this->validate();

        if ($this->editingProfile) {
            // Update existing profile
            $profile = UserProfile::find($this->editingProfile);
            $profile->update([
                'name' => $this->name,
                'type' => $this->type,
                'is_default' => $this->is_default,
                'is_active' => $this->is_active,
            ]);

            session()->flash('message', 'Profile updated successfully!');
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

            session()->flash('message', 'Profile created successfully!');
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
            session()->flash('message', 'Profile deleted successfully!');
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
            session()->flash('message', 'Default profile updated!');
            $this->loadProfiles();

            // Emit event to refresh other components
            $this->dispatch('profileUpdated');
        }
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

    public function render()
    {
        return view('livewire.profile-manager');
    }
}
