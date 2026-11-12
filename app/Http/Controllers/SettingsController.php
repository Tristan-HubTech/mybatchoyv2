<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\InventoryCategory;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = BusinessSetting::firstOrCreate(
            ['id' => 1],
            [
                'business_name' => config('app.name'),
                'session_timeout_minutes' => config('session.lifetime', 20),
            ]
        );

        return view('settings.index', [
            'settings' => $settings,
            'categories' => InventoryCategory::withCount('items')->orderBy('name')->get(),
            'units' => Unit::withCount('items')->orderBy('name')->get(),
            'systemInfo' => [
                'application_version' => app()->version(),
                'environment' => config('app.env'),
                'database_driver' => DB::connection()->getDriverName(),
                'database_status' => DB::select('SELECT 1') ? 'Connected' : 'Unavailable',
                'session_driver' => config('session.driver'),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:32',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'logo' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|mimetypes:image/jpeg,image/png,image/webp|max:2048',
            'session_timeout_minutes' => 'required|integer|min:5|max:240',
            'default_minimum_stock_level' => 'required|numeric|min:0|max:999999999.999',
            'default_reorder_level' => 'required|numeric|min:0|max:999999999.999',
        ]);

        $settings = BusinessSetting::firstOrNew(['id' => 1]);
        $previousLogo = $settings->logo_path;
        $newLogo = null;

        if ($request->hasFile('logo')) {
            $newLogo = $request->file('logo')->storePublicly('business-logos', 'public');
            if ($newLogo === false) {
                throw new RuntimeException('The business logo could not be stored.');
            }
        }

        unset($validated['logo']);
        if ($newLogo !== null) {
            $validated['logo_path'] = $newLogo;
        }

        try {
            $settings = DB::transaction(function () use ($validated, $settings, $request): BusinessSetting {
                $settings->fill($validated);
                $settings->save();

                AuditLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'business_settings_updated',
                    'model_type' => BusinessSetting::class,
                    'model_id' => $settings->id,
                    'details' => 'Business and application settings updated.',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return $settings;
            });
        } catch (Throwable $exception) {
            if ($newLogo !== null) {
                Storage::disk('public')->delete($newLogo);
            }

            throw $exception;
        }

        if ($newLogo !== null && $previousLogo !== null) {
            Storage::disk('public')->delete($previousLogo);
        }

        return redirect()->route('owner.settings.index')->with('success', 'Settings saved.');
    }
}
