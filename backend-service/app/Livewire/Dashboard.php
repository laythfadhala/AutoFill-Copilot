<?php

namespace App\Livewire;

use Livewire\Component;

class Dashboard extends Component
{
    public $activeTab = 'profiles'; // 'profiles', 'upload', 'data'

    protected $listeners = ['switchToDataTab' => 'switchToDataTab'];

    public function mount()
    {
        // Restore active tab from session, default to 'profiles'
        $this->activeTab = session('active_dashboard_tab', 'profiles');
    }

    public function switchToDataTab()
    {
        $this->activeTab = 'data';
        session(['active_dashboard_tab' => 'data']);
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        session(['active_dashboard_tab' => $tab]);
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
