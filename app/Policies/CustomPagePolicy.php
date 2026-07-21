<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CustomPage;

class CustomPagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_custom_pages') || $user->can('custom_pages.view');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_custom_pages') || $user->can('custom_pages.create');
    }

    public function update(User $user, CustomPage $customPage): bool
    {
        return $user->can('manage_custom_pages') || $user->can('custom_pages.update');
    }

    public function delete(User $user, CustomPage $customPage): bool
    {
        return $user->can('manage_custom_pages') || $user->can('custom_pages.delete');
    }

    public function publish(User $user, CustomPage $customPage): bool
    {
        return $user->can('publish_custom_pages') || $user->can('custom_pages.publish');
    }

    public function restore(User $user, CustomPage $customPage): bool
    {
        return $user->can('manage_custom_pages') || $user->can('custom_pages.delete');
    }
}
