<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => [
                'ai_api_key' => Setting::get('ai_api_key', ''),
                'ai_base_url' => Setting::get('ai_base_url', 'https://api.openai.com/v1'),
                'ai_model' => Setting::get('ai_model', 'gpt-4o-mini'),
                'ai_image_model' => Setting::get('ai_image_model', 'gemini-nano-banana-2.1'),
                'crm_brand_name' => Setting::get('crm_brand_name', 'ARS Bot'),
                'crm_brand_color' => Setting::get('crm_brand_color', '#0B5FFF'),
                'crm_logo_path' => Setting::get('crm_logo_path'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ai_api_key' => ['nullable', 'string', 'max:500'],
            'ai_base_url' => ['required', 'url', 'max:255'],
            'ai_model' => ['required', 'string', 'max:120'],
            'ai_image_model' => ['required', 'string', 'max:120'],
            'crm_brand_name' => ['required', 'string', 'max:80'],
            'crm_brand_color' => ['required', 'string', 'max:16'],
            'crm_logo' => ['nullable', 'image', 'max:2048'],
        ]);

        Setting::put('ai_api_key', $validated['ai_api_key'] ?? '');
        Setting::put('ai_base_url', $validated['ai_base_url']);
        Setting::put('ai_model', $validated['ai_model']);
        Setting::put('ai_image_model', $validated['ai_image_model']);
        Setting::put('crm_brand_name', $validated['crm_brand_name']);
        Setting::put('crm_brand_color', $validated['crm_brand_color']);

        if ($request->hasFile('crm_logo')) {
            $path = $request->file('crm_logo')->store('branding', 'public');
            Setting::put('crm_logo_path', $path);
        }

        return back()->with('status', 'Settings saved.');
    }

    /**
     * Upload the super admin's own bot avatar (also available per-user).
     */
    public function updateOwnBot(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bot_name' => ['required', 'string', 'max:60'],
            'bot_color' => ['required', 'string', 'max:16'],
            'bot_theme' => ['required', 'string', 'max:32'],
            'avatar' => ['nullable', 'image', 'max:4096'],
            'knowledge_base' => ['nullable', 'string', 'max:20000'],
        ]);

        $user = $request->user();

        $user->fill([
            'bot_name' => $validated['bot_name'],
            'bot_color' => $validated['bot_color'],
            'bot_theme' => $validated['bot_theme'],
            'knowledge_base' => $validated['knowledge_base'] ?? null,
        ]);

        if ($request->hasFile('avatar')) {
            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();

        return back()->with('status', 'Bot profile updated.');
    }

    public function regenerateApiKey(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->update(['api_key' => User::generateApiKey()]);

        return back()->with('status', 'API key regenerated. Update your embedded widget.');
    }
}
