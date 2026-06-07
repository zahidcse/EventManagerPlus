<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSpeakerRequest;
use App\Http\Requests\Admin\UpdateSpeakerRequest;
use App\Models\Speaker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SpeakerController extends Controller
{
    public function index(): View
    {
        $speakers = Speaker::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.speakers.index', [
            'activeNav' => 'speakers',
            'speakers' => $speakers,
        ]);
    }

    public function create(): View
    {
        return view('admin.speakers.create', [
            'activeNav' => 'speakers',
            'speaker' => null,
        ]);
    }

    public function store(StoreSpeakerRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        unset($data['photo']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('speakers', 'uploads');
        }

        $speaker = Speaker::query()->create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'speaker' => [
                    'id' => $speaker->id,
                    'label' => $speaker->name.($speaker->headline ? ' — '.$speaker->headline : ''),
                ],
            ], 201);
        }

        return redirect()
            ->route('admin.speakers.index')
            ->with('success', 'Speaker created.');
    }

    public function edit(Speaker $speaker): View
    {
        return view('admin.speakers.edit', [
            'activeNav' => 'speakers',
            'speaker' => $speaker,
        ]);
    }

    public function update(UpdateSpeakerRequest $request, Speaker $speaker): RedirectResponse
    {
        $data = $request->validated();
        unset($data['photo']);
        if (array_key_exists('sort_order', $data) && $data['sort_order'] === null) {
            $data['sort_order'] = 0;
        }
        if ($request->hasFile('photo')) {
            if ($speaker->photo_path) {
                Storage::disk('uploads')->delete($speaker->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('speakers', 'uploads');
        }

        $speaker->update($data);

        return redirect()
            ->route('admin.speakers.index')
            ->with('success', 'Speaker updated.');
    }

    public function destroy(Speaker $speaker): RedirectResponse
    {
        $speaker->delete();

        return redirect()
            ->route('admin.speakers.index')
            ->with('success', 'Speaker removed.');
    }
}
