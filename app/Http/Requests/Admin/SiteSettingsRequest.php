<?php

namespace App\Http\Requests\Admin;

use App\Models\SiteSetting;
use Closure;
use DateTimeZone;
use Illuminate\Validation\Rule;

class SiteSettingsRequest extends ContentRequest
{
    protected array $stringLists = ['budget_options', 'timeline_options'];

    public function rules(): array
    {
        $rules = [
            'business_name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:254'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\-.\s]{5,40}$/'],
            'address' => ['nullable', 'string', 'max:300'],
            'service_coverage' => ['nullable', 'string', 'max:300'],
            'whatsapp_url' => ['nullable', 'string', 'max:2048', 'url:https', $this->whatsappHost()],
            'social_links' => ['array:'.implode(',', array_keys(SiteSetting::SOCIAL_NETWORKS))],
            'budget_options' => ['array', 'max:10'],
            'budget_options.*' => ['string', 'max:120', 'distinct'],
            'timeline_options' => ['array', 'max:10'],
            'timeline_options.*' => ['string', 'max:120', 'distinct'],
            'default_meta_title' => ['nullable', 'string', 'max:70'],
            'default_meta_description' => ['nullable', 'string', 'max:160'],
            'default_timezone' => ['required', Rule::in(DateTimeZone::listIdentifiers())],
            'copyright_name' => ['nullable', 'string', 'max:120'],
        ];

        foreach (array_keys(SiteSetting::SOCIAL_NETWORKS) as $network) {
            $rules["social_links.{$network}"] = ['nullable', 'string', 'max:2048', 'url:http,https'];
        }

        return $rules;
    }

    private function whatsappHost(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $host = strtolower((string) parse_url((string) $value, PHP_URL_HOST));
            if ($value && ! in_array($host, ['wa.me', 'api.whatsapp.com', 'whatsapp.com', 'www.whatsapp.com'], true)) {
                $fail('Enter a WhatsApp link such as https://wa.me/<number>.');
            }
        };
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Use digits, spaces and + ( ) - . only.', 'whatsapp_url.url' => 'Enter an https:// WhatsApp link.'] + parent::messages();
    }
}
