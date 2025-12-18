<?php

namespace App\Policies;

use App\Models\CustomerReview;
use App\Models\User;

class CustomerReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, CustomerReview $review): bool
    {
        return $user->tenant_id === $review->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, CustomerReview $review): bool
    {
        return $user->tenant_id === $review->tenant_id;
    }

    public function delete(User $user, CustomerReview $review): bool
    {
        return $user->tenant_id === $review->tenant_id;
    }

    public function restore(User $user, CustomerReview $review): bool
    {
        return $user->tenant_id === $review->tenant_id;
    }

    public function forceDelete(User $user, CustomerReview $review): bool
    {
        return $user->tenant_id === $review->tenant_id;
    }
}
