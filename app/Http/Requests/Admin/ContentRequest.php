<?php

namespace App\Http\Requests\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Base for content forms: normalizes repeatable list inputs (trim, drop blanks, reindex)
 * and derives a slug from the title when left empty.
 */
abstract class ContentRequest extends FormRequest
{
    /** @var list<string> */
    protected array $stringLists = [];

    /** @var list<string> title/body object lists */
    protected array $pairLists = [];

    protected ?string $slugSource = null;

    public function authorize(): bool
    {
        return $this->user()->can('manage-content');
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach ($this->stringLists as $field) {
            $values = $this->input($field, []);
            $merge[$field] = is_array($values)
                ? array_values(array_filter(array_map(fn ($v) => is_string($v) ? trim($v) : $v, $values), fn ($v) => $v !== '' && $v !== null))
                : $values;
        }

        foreach ($this->pairLists as $field) {
            $rows = $this->input($field, []);
            $merge[$field] = is_array($rows)
                ? array_values(array_filter(
                    array_map(fn ($row) => ['title' => trim((string) ($row['title'] ?? '')), 'body' => trim((string) ($row['body'] ?? ''))], array_filter($rows, 'is_array')),
                    fn ($row) => $row['title'] !== '' || $row['body'] !== '',
                ))
                : $rows;
        }

        if ($this->slugSource && $this->exists('slug')) {
            $slug = trim((string) $this->input('slug'));
            $merge['slug'] = Str::slug($slug !== '' ? $slug : (string) $this->input($this->slugSource));
        }

        $this->merge($merge);
    }

    protected function slugRules(string $table, ?Model $model, int $max): array
    {
        return ['required', 'string', 'max:'.$max, 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique($table, 'slug')->ignore($model?->getKey())];
    }

    protected function seoRules(): array
    {
        return [
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug may contain only lowercase letters, numbers and single hyphens.',
            '*.url' => 'Enter a full http:// or https:// address.',
        ];
    }
}
