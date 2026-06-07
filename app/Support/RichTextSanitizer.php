<?php

namespace App\Support;

/**
 * Strip risky tags/attributes from admin TinyMCE HTML for safe public rendering.
 */
final class RichTextSanitizer
{
    /** @see resources/views/admin/partials/rich-text-editor.blade.php */
    private const ALLOWED_TAGS = '<p><br><h2><h3><strong><b><em><i><u><s><strike><del><a><ul><ol><li><blockquote><hr><div><span><sub><sup><img>';

    public static function html(?string $raw): string
    {
        if ($raw === null || trim($raw) === '') {
            return '';
        }

        if (! preg_match('/<[a-z!?]/i', $raw)) {
            return nl2br(e($raw));
        }

        $html = strip_tags($raw, self::ALLOWED_TAGS);
        $html = preg_replace('#\s*on\w+\s*=\s*(["\']).*?\1#iu', '', $html) ?? $html;
        $html = preg_replace('#\s*on\w+\s*=\s*[^\s>]+#iu', '', $html) ?? $html;
        $html = preg_replace('#\sjavascript\s*:#iu', '', $html) ?? $html;
        $html = preg_replace('#<img\b([^>]*)\bsrc\s*=\s*(["\'])\s*javascript:[^"\']*\2#iu', '<img$1', $html) ?? $html;

        return $html;
    }
}
