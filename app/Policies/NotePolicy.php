<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/**
 * Notes hang off leads, contacts, partners and bookings, so they follow those sections:
 * staff who can view any of them can read notes, staff who can edit any of them can write notes.
 * The notes panel additionally requires edit rights on the record the note is added to.
 */
class NotePolicy
{
    /** Sections whose records carry notes. */
    public const SECTIONS = ['leads', 'contacts', 'companies', 'bookings'];

    public function viewAny(User $user): bool
    {
        return $this->allowsAny($user, Permissions::VIEW);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->allowsAny($user, Permissions::UPDATE);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->allowsAny($user, Permissions::UPDATE);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->allowsAny($user, Permissions::UPDATE);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allowsAny($user, Permissions::UPDATE);
    }

    private function allowsAny(User $user, string $ability): bool
    {
        foreach (self::SECTIONS as $section) {
            if ($user->can(Permissions::for($section, $ability))) {
                return true;
            }
        }

        return false;
    }
}
