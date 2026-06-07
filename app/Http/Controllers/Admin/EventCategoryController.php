<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.event-categories.index', [
            'activeNav' => 'event_categories',
            'categories' => EventCategory::query()->withCount('events')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:event_categories,name'],
        ]);

        $max = (int) EventCategory::query()->max('sort_order');

        $category = EventCategory::query()->create([
            'name' => $validated['name'],
            'sort_order' => $max + 1,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
            ], 201);
        }

        return redirect()
            ->route('admin.event-categories.index')
            ->with('success', 'Category added.');
    }

    public function edit(EventCategory $eventCategory): View
    {
        return view('admin.event-categories.edit', [
            'activeNav' => 'event_categories',
            'category' => $eventCategory,
        ]);
    }

    public function update(Request $request, EventCategory $eventCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('event_categories', 'name')->ignore($eventCategory->id),
            ],
        ]);

        DB::transaction(function () use ($eventCategory, $validated): void {
            $eventCategory->update(['name' => $validated['name']]);
            Event::query()
                ->where('event_category_id', $eventCategory->id)
                ->update(['category' => $validated['name']]);
        });

        return redirect()
            ->route('admin.event-categories.index')
            ->with('success', 'Category updated.');
    }

    public function destroy(EventCategory $eventCategory): RedirectResponse
    {
        if ($eventCategory->events()->exists()) {
            return redirect()
                ->route('admin.event-categories.index')
                ->withErrors(['delete' => 'Cannot delete a category that is still used by events. Reassign those events first.']);
        }

        $eventCategory->delete();

        return redirect()
            ->route('admin.event-categories.index')
            ->with('success', 'Category removed.');
    }
}
