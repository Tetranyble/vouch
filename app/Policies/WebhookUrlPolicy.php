<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WebhookUrl;

class WebhookUrlPolicy
{
    public function manage(User $user, WebhookUrl $webhook): bool
    {
        return $webhook->account_id === $user->account_id;
    }
}
