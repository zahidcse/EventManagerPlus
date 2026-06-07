<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrganizerRequest;
use App\Http\Requests\Admin\UpdateOrganizerRequest;
use App\Models\AdminRole;
use App\Models\Organizer;
use App\Repositories\Contracts\OrganizerRepositoryInterface;
use App\Services\OrganizerPortalAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OrganizerController extends Controller
{
    public function __construct(
        private readonly OrganizerRepositoryInterface $organizers,
        private readonly OrganizerPortalAccountService $portalAccounts,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->query('status');
        if ($status !== 'active' && $status !== 'inactive') {
            $status = null;
        }

        $search = $request->query('q');

        return view('admin.organizers.index', [
            'activeNav' => 'organizers',
            'organizers' => $this->organizers->paginateFiltered(10, $status, is_string($search) ? $search : null),
            'totalOrganizers' => $this->organizers->totalCount(),
            'activePartners' => $this->organizers->activeCount(),
            'statusFilter' => $status ?? 'all',
            'topPartners' => $this->organizers->topByEventsCount(2),
        ]);
    }

    public function create(): View
    {
        return view('admin.organizers.create', [
            'activeNav' => 'organizers',
            'organizer' => null,
            'roles' => AdminRole::query()->forOrganizer()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreOrganizerRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->safe()->except(['photo', 'admin_panel_access', 'admin_role_id']);
        $data['auto_approve_events'] = $request->boolean('auto_approve_events');
        $data['digest_notifications'] = $request->boolean('digest_notifications');
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('organizers', 'uploads');
        }

        $organizer = $this->organizers->create($data);

        $this->portalAccounts->sync(
            $organizer,
            $request->boolean('admin_panel_access'),
            $request->filled('admin_role_id') ? (int) $request->input('admin_role_id') : null,
            $request->input('password'),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'organizer' => [
                    'id' => $organizer->id,
                    'label' => $organizer->formattedName().' — '.$organizer->company_name,
                ],
            ], 201);
        }

        return redirect()
            ->route('admin.organizers.index')
            ->with('success', 'Organizer created successfully.');
    }

    public function edit(Organizer $organizer): View
    {
        return view('admin.organizers.edit', [
            'activeNav' => 'organizers',
            'organizer' => $organizer->load(['adminRole', 'portalUser']),
            'roles' => AdminRole::query()->forOrganizer()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateOrganizerRequest $request, Organizer $organizer): RedirectResponse
    {
        $data = $request->safe()->except(['photo', 'admin_panel_access', 'admin_role_id']);
        $data['auto_approve_events'] = $request->boolean('auto_approve_events');
        $data['digest_notifications'] = $request->boolean('digest_notifications');
        if (! filled($data['password'] ?? null)) {
            unset($data['password']);
        }
        if ($request->hasFile('photo')) {
            if ($organizer->photo_path) {
                Storage::disk('uploads')->delete($organizer->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('organizers', 'uploads');
        }

        $this->organizers->update($organizer, $data);

        $this->portalAccounts->sync(
            $organizer->fresh(),
            $request->boolean('admin_panel_access'),
            $request->filled('admin_role_id') ? (int) $request->input('admin_role_id') : null,
            $request->input('password'),
        );

        return redirect()
            ->route('admin.organizers.index')
            ->with('success', 'Organizer updated successfully.');
    }

    public function destroy(Organizer $organizer): RedirectResponse
    {
        if ($organizer->photo_path) {
            Storage::disk('uploads')->delete($organizer->photo_path);
        }
        $this->portalAccounts->revokePortalAccess($organizer);
        $this->organizers->delete($organizer);

        return redirect()
            ->route('admin.organizers.index')
            ->with('success', 'Organizer removed.');
    }
}
