<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\SiteSetting;
use App\Support\PublicFrontendTheme;
use App\Support\RichTextSanitizer;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PublicBlogController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    private function siteContext(): array
    {
        if (! Schema::hasTable('site_settings')) {
            $setting = new SiteSetting([
                'site_name' => config('app.name', 'Event Manager'),
                'frontend_theme' => 'default',
            ]);
        } else {
            $setting = SiteSetting::instance();
        }

        $extras = PublicFrontendTheme::publicPageExtras();

        return [
            'siteSetting' => $setting,
            'siteName' => $setting->site_name ?: config('app.name', 'Event Manager'),
            'siteLogoUrl' => PublicFrontendTheme::resolvePublicLogoUrl($setting),
            'contactEmail' => $extras['contactEmail'],
            'contactPhone' => $extras['contactPhone'],
            'heroImageUrl' => $extras['heroImageUrl'],
        ];
    }

    public function index(Request $request): View
    {
        $context = $this->siteContext();

        if (! Schema::hasTable('blog_posts')) {
            $posts = new LengthAwarePaginator([], 0, 9, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return view(PublicFrontendTheme::blogView('index'), array_merge($context, compact('posts')));
        }

        $query = BlogPost::query()
            ->publishedOnFrontend()
            ->orderByDesc('published_at')
            ->orderByDesc('updated_at');

        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $term = '%'.addcslashes($q, '%_\\').'%';
            $query->where(function ($sub) use ($term): void {
                $sub->where('title', 'like', $term)
                    ->orWhere('excerpt', 'like', $term);
            });
        }

        $posts = $query->paginate(9)->withQueryString();

        return view(PublicFrontendTheme::blogView('index'), array_merge($context, compact('posts')));
    }

    public function show(BlogPost $blog_post): View
    {
        abort_unless($blog_post->isVisibleOnFrontend(), 404);

        $bodyHtml = RichTextSanitizer::html($blog_post->body);

        return view(PublicFrontendTheme::blogView('show'), array_merge($this->siteContext(), [
            'post' => $blog_post,
            'bodyHtml' => $bodyHtml,
        ]));
    }
}
