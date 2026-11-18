<?php

namespace App\Policies;

use App\Models\Submitter;
use App\Models\User;

class SubmitterPolicy
{
    public function manage(User $user, Submitter $submitter): bool
    {
        return $submitter->account_id === $user->account_id;
    }
}
