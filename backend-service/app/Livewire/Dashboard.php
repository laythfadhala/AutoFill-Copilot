<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\TokenService;

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

    public function getSubscriptionData()
    {
        $user = auth()->user();
        if (!$user) {
            return [
                'current_plan' => 'free',
                'trial_status' => ['on_trial' => false, 'days_remaining' => 0],
                'usage' => ['tokens' => 0, 'tokens_limit' => 10000, 'tokens_remaining' => 10000, 'usage_percentage' => 0, 'is_near_limit' => false, 'is_over_limit' => false],
                'limits' => ['tokens' => 10000, 'profiles' => 1, 'documents' => 10],
                'counts' => ['profiles' => 0, 'documents' => 0]
            ];
        }

        $monthlyUsage = TokenService::getMonthlyUsage($user);

        return [
            'current_plan' => $user->current_plan ?? 'free',
            'trial_status' => TokenService::getTrialStatus($user),
            'usage' => [
                'tokens' => $monthlyUsage['tokens_used'] ?? 0,
                'tokens_limit' => $monthlyUsage['tokens_limit'] ?? 10000,
                'tokens_remaining' => $monthlyUsage['tokens_remaining'] ?? 10000,
                'usage_percentage' => $monthlyUsage['usage_percentage'] ?? 0,
                'is_near_limit' => $monthlyUsage['is_near_limit'] ?? false,
                'is_over_limit' => $monthlyUsage['is_over_limit'] ?? false,
            ],
            'limits' => [
                'tokens' => $user->getTokenLimit(),
                'profiles' => $user->getProfileLimit(),
                'documents' => $user->getDocumentLimit(),
            ],
            'counts' => [
                'profiles' => $user->userProfiles()->count(),
                'documents' => $user->getDocumentCount(),
            ]
        ];
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.dashboard', [
            'subscriptionData' => $this->getSubscriptionData(),
            'isTokenLimitReached' => $user && !$user->hasRole('admin') && ($this->getSubscriptionData()['usage']['is_over_limit'] ?? false),
            'isProfileLimitReached' => $user && !$user->hasRole('admin') && !$user->canCreateProfile(),
            'isDocumentLimitReached' => $user && !$user->hasRole('admin') && !$user->canUploadDocument(),
        ]);
    }
}
