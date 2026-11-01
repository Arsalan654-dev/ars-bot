<?php

namespace App\Http\Controllers;

use App\Models\PageSection;
use App\Models\User;
use Illuminate\View\View;

/**
 * Public marketing pages (Work / Agency / Careers) rendered
 * from the super admin's content.
 */
class PageController extends Controller
{
    public function work(): View
    {
        return $this->render(PageSection::PAGE_WORK);
    }

    public function agency(): View
    {
        return $this->render(PageSection::PAGE_AGENCY);
    }

    public function careers(): View
    {
        return $this->render(PageSection::PAGE_CAREERS);
    }

    private function render(string $page): View
    {
        return view('pages.'.$page, [
            'sections' => PageSection::query()->forPage($page)->get(),
            'bot' => $this->platformBot(),
        ]);
    }

    private function platformBot(): ?User
    {
        return User::query()->where('role', User::ROLE_SUPER_ADMIN)->first();
    }
}
