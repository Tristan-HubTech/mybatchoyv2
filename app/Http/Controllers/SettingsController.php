<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BusinessSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = BusinessSetting::firstOrCreate(
            ['id' => 1],
            ['business_name' => config('app.name')]
        );

        return view('settings.index', [
            'settings' => $settings,
            'sessionLifetime' => config('session.lifetime'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:32',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
        ]);

        $settings = BusinessSetting::updateOrCreate(['id' => 1], $validated);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'business_settings_updated',
            'model_type' => BusinessSetting::class,
            'model_id' => $settings->id,
            'details' => 'Business contact details updated.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('owner.settings.index')->with('success', 'Business settings saved.');
    }
}