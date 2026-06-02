<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAppSettingsRequest;
use App\Settings\GeneralSettings;
use App\Settings\SecuritySettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AppSettingsController extends Controller
{
    public function edit(GeneralSettings $general, SecuritySettings $security): Response
    {
        return Inertia::render('Settings/App', [
            'general' => [
                'org_name' => $general->org_name,
                'support_email' => $general->support_email,
                'date_format' => $general->date_format,
                'pagination_size' => $general->pagination_size,
            ],
            'security' => [
                'password_change_interval_days' => $security->password_change_interval_days,
            ],
        ]);
    }

    public function update(UpdateAppSettingsRequest $request, GeneralSettings $general, SecuritySettings $security): RedirectResponse
    {
        $data = $request->validated();

        $general->org_name = $data['org_name'];
        $general->support_email = $data['support_email'];
        $general->date_format = $data['date_format'];
        $general->pagination_size = $data['pagination_size'];
        $general->save();

        $security->password_change_interval_days = $data['password_change_interval_days'];
        $security->save();

        return redirect()->route('app-settings.edit')->with('success', 'Settings updated successfully');
    }
}
