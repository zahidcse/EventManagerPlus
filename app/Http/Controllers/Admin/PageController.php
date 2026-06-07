<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View
    {
        $pages = Page::query()->orderByDesc('updated_at')->paginate(15);

        return view('admin.pages.index', [
            'activeNav' => 'pages',
            'pages' => $pages,
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.form', [
            'activeNav' => 'pages',
            'page' => null,
        ]);
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        $data = $this->payloadFromRequest($request);
        $slugBase = $this->slugBaseFromRequest($request);
        $data['slug'] = $this->uniquePageSlug($slugBase);
        if ($request->hasFile('hero_image')) {
            $data['hero_image_path'] = $request->file('hero_image')->store('pages/heroes', 'uploads');
        }

        Page::query()->create($data);

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Page created.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.form', [
            'activeNav' => 'pages',
            'page' => $page,
        ]);
    }

    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $data = $this->payloadFromRequest($request);
        $slugBase = $this->slugBaseFromRequest($request);
        $newSlug = $this->uniquePageSlug($slugBase, $page->id);
        $data['slug'] = $newSlug;

        if ($request->hasFile('hero_image')) {
            if ($page->hero_image_path) {
                Storage::disk('uploads')->delete($page->hero_image_path);
            }
            $data['hero_image_path'] = $request->file('hero_image')->store('pages/heroes', 'uploads');
        }

        $page->update($data);

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Page updated.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Page deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadFromRequest(StorePageRequest|UpdatePageRequest $request): array
    {
        $data = $request->validated();
        unset($data['hero_image']);

        return $data;
    }

    private function slugBaseFromRequest(StorePageRequest|UpdatePageRequest $request): string
    {
        $manual = trim((string) $request->input('slug', ''));
        if ($manual !== '') {
            return Str::slug($manual);
        }

        return Str::slug((string) $request->input('title'));
    }

    private function uniquePageSlug(string $base, ?int $exceptId = null): string
    {
        $slug = $base !== '' ? $base : 'page';
        $i = 1;
        while (
            Page::query()
                ->where('slug', $slug)
                ->when($exceptId !== null, fn ($q) => $q->where('id', '!=', $exceptId))
                ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
