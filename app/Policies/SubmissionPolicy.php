<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Submission $submission): bool
    {
        return $submission->account_id === $user->account_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Submission $submission): bool
    {
        return $submission->account_id === $user->account_id;
    }

    public function delete(User $user, Submission $submission): bool
    {
        return $submission->account_id === $user->account_id;
    }

    public function restore(User $user, Submission $submission): bool
    {
        return $submission->account_id === $user->account_id;
    }
}
