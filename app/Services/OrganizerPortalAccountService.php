<?php

namespace App\Services;

use App\Models\AdminRole;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class OrganizerPortalAccountService
{
    /**
     * Sync linked login user for organizer admin panel access.
     */
    public function sync(
        Organizer $organizer,
        bool $panelAccess,
        ?int $roleId,
        ?string $password,
    ): void {
        if (! $panelAccess || $roleId === null) {
            $this->revokePortalAccess($organizer);

            return;
        }

        $role = AdminRole::query()->find($roleId);

        if ($role === null || ! $role->appliesToOrganizer()) {
            throw ValidationException::withMessages([
                'admin_role_id' => 'Select a valid role for organizer panel access.',
            ]);
        }

        if ($role->is_super) {
            throw ValidationException::withMessages([
                'admin_role_id' => 'Super Administrator cannot be assigned to organizers.',
            ]);
        }

        $organizer->admin_role_id = $role->id;
        $organizer->save();

        $userPayload = [
            'name' => $organizer->name,
            'email' => $organizer->email,
            'is_admin' => false,
            'is_organizer' => true,
            'organizer_id' => $organizer->id,
            'admin_role_id' => $role->id,
        ];

        if ($password !== null && $password !== '') {
            $userPayload['password'] = $password;
        }

        if ($organizer->user_id !== null) {
            $user = User::query()->find($organizer->user_id);

            if ($user === null) {
                $organizer->update(['user_id' => null]);

                $this->createPortalUser($organizer, $userPayload, $password);

                return;
            }

            $conflict = User::query()
                ->where('email', $organizer->email)
                ->where('id', '!=', $user->id)
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'email' => 'This email is already used by another account.',
                ]);
            }

            $user->update($userPayload);

            return;
        }

        $this->createPortalUser($organizer, $userPayload, $password);
    }

    public function revokePortalAccess(Organizer $organizer): void
    {
        if ($organizer->user_id !== null) {
            User::query()->where('id', $organizer->user_id)->delete();
        }

        $organizer->update([
            'user_id' => null,
            'admin_role_id' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $userPayload
     */
    private function createPortalUser(Organizer $organizer, array $userPayload, ?string $password): void
    {
        $existing = User::query()->where('email', $organizer->email)->first();

        if ($existing !== null) {
            if ($existing->is_admin) {
                throw ValidationException::withMessages([
                    'email' => 'This email belongs to a staff account. Use a different email for the organizer.',
                ]);
            }

            if ($existing->organizer_id !== null && $existing->organizer_id !== $organizer->id) {
                throw ValidationException::withMessages([
                    'email' => 'This email is already linked to another organizer.',
                ]);
            }

            if ($password === null || $password === '') {
                throw ValidationException::withMessages([
                    'password' => 'Password is required when enabling panel access.',
                ]);
            }

            $existing->update($userPayload);
            $organizer->update(['user_id' => $existing->id, 'admin_role_id' => $userPayload['admin_role_id']]);

            return;
        }

        if ($password === null || $password === '') {
            throw ValidationException::withMessages([
                'password' => 'Password is required when enabling panel access.',
            ]);
        }

        $userPayload['email_verified_at'] = now();
        $user = User::query()->create($userPayload);
        $organizer->update(['user_id' => $user->id]);
    }
}
