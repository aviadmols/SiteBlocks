<?php

namespace App\Policies;

use App\Models\Block;
use App\Models\User;

class BlockPolicy
{
    /**
     * Determine whether the user can view any blocks.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the block.
     */
    public function view(User $user, Block $block): bool
    {
        $site = $block->site;
        return $site && $user->id === $site->user_id;
    }

    /**
     * Determine whether the user can create blocks.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the block.
     */
    public function update(User $user, Block $block): bool
    {
        $site = $block->site;
        return $site && $user->id === $site->user_id;
    }

    /**
     * Determine whether the user can delete the block.
     */
    public function delete(User $user, Block $block): bool
    {
        $site = $block->site;
        return $site && $user->id === $site->user_id;
    }
}
