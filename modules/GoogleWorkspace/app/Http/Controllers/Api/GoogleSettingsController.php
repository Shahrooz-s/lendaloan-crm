<?php

namespace Modules\GoogleWorkspace\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\GoogleWorkspace\Models\GoogleSetting;

class GoogleSettingsController extends Controller
{
    public function show()
    {
        return GoogleSetting::first();
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'required|string',
        ]);

        $settings = GoogleSetting::firstOrNew();
        $settings->fill($data)->save();

        return response()->json(['message' => 'Settings updated successfully.']);
    }
}
