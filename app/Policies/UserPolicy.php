<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Hanya 'owner' yang bisa melakukan semua aksi.
     * Method ini menjadi "gerbang utama" sebelum method lain dicek.
     */
    public function before(User $user): ?bool
    {
        if ($user->role !== 'owner') {
            return false; // langsung tolak semua aksi untuk non-owner
        }

        return null; // lanjutkan ke method spesifik untuk 'owner'
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, User $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, User $model): bool
    {
        return true;
    }

    public function delete(User $user, User $model): bool
    {
        return true;
    }
}