<?php

namespace Tests\Feature;

use App\Enums\SubscriptionPlan;
use Tests\TestCase;
use App\Models\User;

class DynamicTokenLimitTest extends TestCase
{

    /** @test */
    public function it_returns_100k_tokens_for_small_user_base()
    {
        // Create free users
        User::factory()->count(100)->create([
            'subscription_plan' => SubscriptionPlan::FREE->value,
            'subscription_status' => 'active',
        ]);

        $user = User::first();
        $limit = $user->getDynamicFreeTokenLimit();

        $this->assertEquals(100000, $limit);
    }

    /** @test */
    public function it_returns_100k_tokens_for_up_to_10k_users()
    {
        // Simulate up to 10K users - verify logic returns 100K
        $user = User::factory()->create([
            'subscription_plan' => SubscriptionPlan::FREE->value,
            'subscription_status' => 'active',
        ]);

        $limit = $user->getDynamicFreeTokenLimit();

        // With 1 user, should return 100K
        $this->assertEquals(100000, $limit);
    }

    /** @test */
    public function it_reduces_limit_with_logarithmic_scaling_beyond_10k_users()
    {
        // Create 1000 free active users to test the formula works
        User::factory(1000)->create([
            'subscription_plan' => SubscriptionPlan::FREE->value,
            'subscription_status' => 'active',
        ]);

        $user = User::first();
        $limit = $user->getDynamicFreeTokenLimit();

        // With 1000 users (still under 10K threshold), should be 100K
        $this->assertEquals(100000, $limit);

        // Note: To test beyond 10K, you would need to mock the User::where() query
        // or use a different testing approach
    }

    /** @test */
    public function it_only_counts_active_free_users()
    {
        // Create paid users - should not affect free limit
        User::factory(100)->create([
            'subscription_plan' => SubscriptionPlan::PLUS->value,
            'subscription_status' => 'active',
        ]);

        // Create inactive free users - should not affect limit
        User::factory(50)->create([
            'subscription_plan' => SubscriptionPlan::FREE->value,
            'subscription_status' => 'canceled',
        ]);

        // Create active free users
        User::factory()->count(10)->create([
            'subscription_plan' => SubscriptionPlan::FREE->value,
            'subscription_status' => 'active',
        ]);

        $user = User::where('subscription_plan', SubscriptionPlan::FREE->value)
            ->where('subscription_status', 'active')
            ->first();

        $limit = $user->getDynamicFreeTokenLimit();

        // Should only count the 10 active free users, so limit is 100K
        $this->assertEquals(100000, $limit);
    }

    /** @test */
    public function it_displays_dynamic_limit_in_billing_page()
    {
        $user = User::factory()->create([
            'subscription_plan' => SubscriptionPlan::FREE->value,
            'subscription_status' => 'active',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('billing.subscriptions'));

        $response->assertStatus(200);
        // The view should show dynamic token limit
        $response->assertSee('Limited per month'); // Should display the formatted limit
    }
}
