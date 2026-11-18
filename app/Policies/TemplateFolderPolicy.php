<?php

namespace App\Policies;

use App\Models\TemplateFolder;
use App\Models\User;

class TemplateFolderPolicy
{
    public function manage(User $user, TemplateFolder $folder): bool
    {
        return $folder->account_id === $user->account_id;
    }
}
