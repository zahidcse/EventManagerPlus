<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogPostRequest;
use App\Http\Requests\Admin\UpdateBlogPostRequest;
use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogPostController extends Controller
{
    public function index(): View
    {
        $posts = BlogPost::query()->orderByDesc('updated_at')->paginate(15);

        return view('admin.blog.index', [
            'activeNav' => 'blog',
            'posts' => $posts,
        ]);
    }

    public function create(): View
    {
        return view('admin.blog.form', [
            'activeNav' => 'blog',
            'post' => null,
        ]);
    }

    public function store(StoreBlogPostRequest $request): RedirectResponse
    {
        $data = $this->payloadFromRequest($request);
        $slugBase = $this->slugBaseFromRequest($request);
        $data['slug'] = $this->uniqueBlogSlug($slugBase);
        if ($request->hasFile('hero_image')) {
            $data['hero_image_path'] = $request->file('hero_image')->store('blog/heroes', 'uploads');
        }

        BlogPost::query()->create($data);

        return redirect()
            ->route('admin.blog.index')
            ->with('success', 'Blog post created.');
    }

    public function edit(BlogPost $blog_post): View
    {
        return view('admin.blog.form', [
            'activeNav' => 'blog',
            'post' => $blog_post,
        ]);
    }

    public function update(UpdateBlogPostRequest $request, BlogPost $blog_post): RedirectResponse
    {
        $data = $this->payloadFromRequest($request);
        $slugBase = $this->slugBaseFromRequest($request);
        $data['slug'] = $this->uniqueBlogSlug($slugBase, $blog_post->id);

        if ($request->hasFile('hero_image')) {
            if ($blog_post->hero_image_path) {
                Storage::disk('uploads')->delete($blog_post->hero_image_path);
            }
            $data['hero_image_path'] = $request->file('hero_image')->store('blog/heroes', 'uploads');
        }

        $blog_post->update($data);

        return redirect()
            ->route('admin.blog.index')
            ->with('success', 'Blog post updated.');
    }

    public function destroy(BlogPost $blog_post): RedirectResponse
    {
        $blog_post->delete();

        return redirect()
            ->route('admin.blog.index')
            ->with('success', 'Blog post deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadFromRequest(StoreBlogPostRequest|UpdateBlogPostRequest $request): array
    {
        $data = $request->validated();
        unset($data['hero_image']);

        return $data;
    }

    private function slugBaseFromRequest(StoreBlogPostRequest|UpdateBlogPostRequest $request): string
    {
        $manual = trim((string) $request->input('slug', ''));
        if ($manual !== '') {
            return Str::slug($manual);
        }

        return Str::slug((string) $request->input('title'));
    }

    private function uniqueBlogSlug(string $base, ?int $exceptId = null): string
    {
        $slug = $base !== '' ? $base : 'post';
        $i = 1;
        while (
            BlogPost::query()
                ->where('slug', $slug)
                ->when($exceptId !== null, fn ($q) => $q->where('id', '!=', $exceptId))
                ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
