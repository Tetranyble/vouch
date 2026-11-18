<?php

namespace App\Policies;

use App\Models\Template;
use App\Models\User;

class TemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Template $template): bool
    {
        return $template->account_id === $user->account_id
            || $template->sharings()->where('account_id', $user->account_id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Template $template): bool
    {
        return $template->account_id === $user->account_id
            || $template->sharings()->where('account_id', $user->account_id)
                ->whereIn('ability', ['write', 'manage'])->exists();
    }

    public function delete(User $user, Template $template): bool
    {
        return $template->account_id === $user->account_id;
    }

    public function restore(User $user, Template $template): bool
    {
        return $template->account_id === $user->account_id;
    }
}
