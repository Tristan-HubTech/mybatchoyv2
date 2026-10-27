<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;

class PublicPageController extends Controller
{
    private function getSettings()
    {
        try {
            return BusinessSetting::first() ?? new BusinessSetting([
                'business_name' => 'MyBatchoy',
                'phone' => '+63 917 123 4567',
                'email' => 'hello@mybatchoy.com',
                'address' => 'Iloilo City, Western Visayas, Philippines',
            ]);
        } catch (\Throwable $e) {
            return new BusinessSetting([
                'business_name' => 'MyBatchoy',
                'phone' => '+63 917 123 4567',
                'email' => 'hello@mybatchoy.com',
                'address' => 'Iloilo City, Western Visayas, Philippines',
            ]);
        }
    }

    public function home()
    {
        $settings = $this->getSettings();
        return view('public.home', compact('settings'));
    }

    public function about()
    {
        $settings = $this->getSettings();
        return view('public.about', compact('settings'));
    }

    public function contact()
    {
        $settings = $this->getSettings();
        return view('public.contact', compact('settings'));
    }

    public function menu()
    {
        $settings = $this->getSettings();
        return view('public.menu', compact('settings'));
    }
}
