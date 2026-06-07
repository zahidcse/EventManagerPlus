<?php

namespace App\Http\Requests\Admin;

use App\Models\BlogPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $slug = $this->input('slug');
        if ($slug === null || $slug === '') {
            $this->merge(['slug' => null]);

            return;
        }
        $merged = Str::slug(trim((string) $slug));
        $this->merge(['slug' => $merged !== '' ? $merged : null]);
    }

    public function rules(): array
    {
        /** @var BlogPost $blogPost */
        $blogPost = $this->route('blog_post');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('blog_posts', 'slug')->ignore($blogPost->id)],
            'excerpt' => ['nullable', 'string', 'max:5000'],
            'body' => ['nullable', 'string', 'max:500000'],
            'hero_image' => ['nullable', 'image', 'max:8192'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
