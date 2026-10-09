<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Minimal structured plain text -> safe HTML. All input is escaped; the only structure is:
 * blank-line separated paragraphs, "## " / "### " headings and "- " bullet lists.
 * No HTML from editors or visitors is ever rendered.
 */
class StructuredText
{
    public static function toHtml(?string $text): HtmlString
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim((string) $text));

        if ($text === '') {
            return new HtmlString('');
        }

        $html = [];

        foreach (preg_split('/\n\s*\n/', $text) as $block) {
            $lines = array_values(array_filter(array_map('rtrim', explode("\n", trim($block))), fn ($l) => $l !== ''));

            if ($lines === []) {
                continue;
            }

            if (count($lines) === 1 && str_starts_with($lines[0], '### ')) {
                $html[] = '<h3>'.e(substr($lines[0], 4)).'</h3>';
            } elseif (count($lines) === 1 && str_starts_with($lines[0], '## ')) {
                $html[] = '<h2>'.e(substr($lines[0], 3)).'</h2>';
            } elseif (collect($lines)->every(fn ($l) => str_starts_with(ltrim($l), '- '))) {
                $items = array_map(fn ($l) => '<li>'.e(substr(ltrim($l), 2)).'</li>', $lines);
                $html[] = '<ul>'.implode('', $items).'</ul>';
            } else {
                $html[] = '<p>'.implode('<br>', array_map('e', $lines)).'</p>';
            }
        }

        return new HtmlString(implode("\n", $html));
    }

    /** Plain excerpt for meta descriptions. */
    public static function excerpt(?string $text, int $limit = 160): string
    {
        $plain = preg_replace('/^(#{2,3} |- )/m', '', (string) $text);

        return str(preg_replace('/\s+/', ' ', $plain))->trim()->limit($limit, '…')->toString();
    }
}
