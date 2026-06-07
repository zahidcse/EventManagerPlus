<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffUserRequest;
use App\Http\Requests\Admin\UpdateStaffUserRequest;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffUserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('q');

        $query = User::query()
            ->with('adminRole')
            ->where('is_admin', true)
            ->orderBy('name');

        if (is_string($search) && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        return view('admin.staff.index', [
            'activeNav' => 'staff',
            'staffUsers' => $query->paginate(12)->withQueryString(),
            'totalStaff' => User::query()->where('is_admin', true)->count(),
            'roles' => AdminRole::query()->forStaff()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.staff.create', [
            'activeNav' => 'staff',
            'staffUser' => null,
            'roles' => AdminRole::query()->forStaff()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreStaffUserRequest $request): RedirectResponse
    {
        User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'timezone' => $request->validated('timezone'),
            'is_admin' => true,
            'admin_role_id' => $request->validated('admin_role_id'),
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('admin.staff.index')
            ->with('success', 'Staff member created successfully.');
    }

    public function edit(User $staffUser): View|RedirectResponse
    {
        if (! $staffUser->is_admin) {
            return redirect()
                ->route('admin.staff.index')
                ->with('error', 'That user is not a staff account.');
        }

        return view('admin.staff.edit', [
            'activeNav' => 'staff',
            'staffUser' => $staffUser->load('adminRole'),
            'roles' => AdminRole::query()->forStaff()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateStaffUserRequest $request, User $staffUser): RedirectResponse
    {
        if (! $staffUser->is_admin) {
            return redirect()
                ->route('admin.staff.index')
                ->with('error', 'That user is not a staff account.');
        }

        $data = $request->safe()->only(['name', 'email', 'admin_role_id', 'timezone']);

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        if ($this->wouldRemoveLastSuperAdmin($staffUser, (int) $data['admin_role_id'])) {
            return back()
                ->withInput()
                ->with('error', 'Cannot change role: at least one Super Administrator must remain.');
        }

        $staffUser->update($data);

        return redirect()
            ->route('admin.staff.index')
            ->with('success', 'Staff member updated successfully.');
    }

    public function destroy(User $staffUser): RedirectResponse
    {
        if (! $staffUser->is_admin) {
            return redirect()
                ->route('admin.staff.index')
                ->with('error', 'That user is not a staff account.');
        }

        if ($staffUser->id === auth()->id()) {
            return redirect()
                ->route('admin.staff.index')
                ->with('error', 'You cannot delete your own account.');
        }

        if ($this->wouldRemoveLastSuperAdmin($staffUser, null)) {
            return redirect()
                ->route('admin.staff.index')
                ->with('error', 'Cannot delete the last Super Administrator.');
        }

        $staffUser->delete();

        return redirect()
            ->route('admin.staff.index')
            ->with('success', 'Staff member removed.');
    }

    private function wouldRemoveLastSuperAdmin(User $staffUser, ?int $newRoleId): bool
    {
        if (! $staffUser->isSuperAdmin()) {
            return false;
        }

        if ($newRoleId !== null) {
            $newRole = AdminRole::query()->find($newRoleId);
            if ($newRole?->is_super) {
                return false;
            }
        }

        $superRoleId = AdminRole::query()->where('is_super', true)->value('id');

        if ($superRoleId === null) {
            return false;
        }

        $remaining = User::query()
            ->where('is_admin', true)
            ->where('admin_role_id', $superRoleId)
            ->where('id', '!=', $staffUser->id)
            ->count();

        return $remaining < 1;
    }
}
