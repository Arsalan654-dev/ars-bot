<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Disable bots and reset payment status for expired subscriptions';

    public function handle(SubscriptionService $subscriptions): int
    {
        $expired = $subscriptions->expireOverdueSubscriptions();

        $this->info("Expired {$expired} subscription(s).");

        return self::SUCCESS;
    }
}
