<?php

namespace App\Http\Requests\Admin;

use App\Models\TeamMember;

class TeamMemberRequest extends ContentRequest
{
    protected array $stringLists = ['skills'];

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'role_title' => ['required', 'string', 'max:160'],
            'biography' => ['nullable', 'string', 'max:5000'],
            'skills' => ['array', 'max:20'],
            'skills.*' => ['string', 'max:60'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'social_links' => ['array:'.implode(',', array_keys(TeamMember::SOCIAL_NETWORKS))],
        ];

        foreach (array_keys(TeamMember::SOCIAL_NETWORKS) as $network) {
            $rules["social_links.{$network}"] = ['nullable', 'string', 'max:2048', 'url:http,https'];
        }

        return $rules;
    }
}
