<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Drafts are only visible to IT staff. */
    public function view(User $user, Article $article): bool
    {
        return $article->is_published || $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Article $article): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->isStaff();
    }
}
