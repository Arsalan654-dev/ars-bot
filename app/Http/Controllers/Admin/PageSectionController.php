<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Super admin CRUD for the public page content:
 * Work, Agency (our about-us) and Careers pages.
 * Every section supports title, subtitle, text, image and video.
 */
class PageSectionController extends Controller
{
    public function index(Request $request): View
    {
        $page = $request->string('page')->toString();
        $page = in_array($page, PageSection::PAGES, true) ? $page : PageSection::PAGE_WORK;

        return view('admin.pages.index', [
            'page' => $page,
            'sections' => PageSection::query()
                ->where('page', $page)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'counts' => collect(PageSection::PAGES)
                ->mapWithKeys(fn (string $key): array => [
                    $key => PageSection::query()->where('page', $key)->count(),
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        PageSection::query()->create($validated);

        return back()->with('status', 'Section created.');
    }

    public function update(Request $request, PageSection $section): RedirectResponse
    {
        $validated = $this->validated($request, $section);

        $section->update($validated);

        return back()->with('status', 'Section updated.');
    }

    public function destroy(PageSection $section): RedirectResponse
    {
        $section->delete();

        return back()->with('status', 'Section deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?PageSection $section = null): array
    {
        $validated = $request->validate([
            'page' => ['required', Rule::in(PageSection::PAGES)],
            'title' => ['required', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:160'],
            'content' => ['nullable', 'string', 'max:20000'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'max:8192'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        unset($validated['image']);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('page-sections', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }
}
