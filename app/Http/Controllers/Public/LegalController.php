<?php

namespace App\Http\Controllers\Public;

use App\Enums\LegalPageKey;
use App\Http\Controllers\Controller;
use App\Models\LegalPage;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function privacy(): View
    {
        return $this->show(LegalPageKey::Privacy);
    }

    public function terms(): View
    {
        return $this->show(LegalPageKey::Terms);
    }

    private function show(LegalPageKey $key): View
    {
        $page = LegalPage::query()->published()->where('key', $key->value)->first();
        abort_unless($page, 404);

        return view('public.legal', ['page' => $page]);
    }
}
