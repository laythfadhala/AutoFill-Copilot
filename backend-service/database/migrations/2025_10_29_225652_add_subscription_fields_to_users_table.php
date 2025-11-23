<?php

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\TokenAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('token_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('action', TokenAction::values());
            $table->bigInteger('tokens_used')->default(0); // Cumulative lifetime total
            $table->bigInteger('tokens_this_month')->default(0); // Current month's token usage
            $table->integer('count_this_month')->default(0); // Current month's action count
            $table->string('current_month', 7)->nullable(); // Format: "YYYY-MM" (e.g., "2025-11")
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('action');
            $table->index('current_month');
            $table->unique(['user_id', 'action']); // Ensure one record per user per action
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('subscription_plan', SubscriptionPlan::values())->default('free')->after('avatar');
            $table->enum('subscription_status', SubscriptionStatus::values())->default('active')->after('subscription_plan');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
            $table->string('stripe_customer_id')->nullable()->after('subscription_ends_at');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
            $table->string('payment_status')->nullable()->after('subscription_status');
            // The plan that will take effect at subscription_ends_at
            $table->enum('pending_plan', SubscriptionPlan::values())->nullable()->after('subscription_plan');

            // Optionally track the schedule ID if using Stripe Subscription Schedules
            $table->string('stripe_schedule_id')->nullable()->after('stripe_subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_usages');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_plan',
                'subscription_status',
                'trial_ends_at',
                'subscription_ends_at',
                'stripe_customer_id',
                'stripe_subscription_id',
                'payment_status',
                'pending_plan',
                'stripe_schedule_id',
            ]);
        });
    }
};
