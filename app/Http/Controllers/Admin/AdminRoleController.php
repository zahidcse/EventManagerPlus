<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminRoleRequest;
use App\Http\Requests\Admin\UpdateAdminRoleRequest;
use App\Models\AdminRole;
use App\Support\Admin\AdminModules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminRoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'activeNav' => 'staff',
            'roles' => AdminRole::query()
                ->withCount(['users', 'organizers'])
                ->orderBy('name')
                ->get(),
            'moduleDefinitions' => AdminModules::definitions(),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'activeNav' => 'staff',
            'role' => null,
            'moduleDefinitions' => AdminModules::definitions(),
            'groupedModules' => $this->groupedModules(),
        ]);
    }

    public function store(StoreAdminRoleRequest $request): RedirectResponse
    {
        AdminRole::query()->create([
            'name' => $request->validated('name'),
            'slug' => Str::slug($request->validated('slug')),
            'audience' => $request->validated('audience'),
            'description' => $request->validated('description'),
            'permissions' => array_values($request->input('permissions', [])),
            'is_super' => false,
        ]);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(AdminRole $role): View|RedirectResponse
    {
        if ($role->is_super) {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', 'The Super Administrator role cannot be edited.');
        }

        return view('admin.roles.edit', [
            'activeNav' => 'staff',
            'role' => $role,
            'moduleDefinitions' => AdminModules::definitions(),
            'groupedModules' => $this->groupedModules(),
        ]);
    }

    public function update(UpdateAdminRoleRequest $request, AdminRole $role): RedirectResponse
    {
        if ($role->is_super) {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', 'The Super Administrator role cannot be edited.');
        }

        $role->update([
            'name' => $request->validated('name'),
            'slug' => Str::slug($request->validated('slug')),
            'audience' => $request->validated('audience'),
            'description' => $request->validated('description'),
            'permissions' => array_values($request->input('permissions', [])),
        ]);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(AdminRole $role): RedirectResponse
    {
        if ($role->is_super) {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', 'The Super Administrator role cannot be deleted.');
        }

        if ($role->users()->exists() || $role->organizers()->exists()) {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', 'Cannot delete a role that is assigned to staff or organizers.');
        }

        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    /**
     * @return array<string, array<string, array{label: string, description: string, group: string}>>
     */
    private function groupedModules(): array
    {
        $grouped = [];

        foreach (AdminModules::definitions() as $key => $definition) {
            $grouped[$definition['group']][$key] = $definition;
        }

        return $grouped;
    }
}
