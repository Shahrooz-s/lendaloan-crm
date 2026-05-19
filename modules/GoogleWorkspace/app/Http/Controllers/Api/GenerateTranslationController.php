<?php

namespace Modules\GoogleWorkspace\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class GenerateTranslationController
{
    /**
     * Get the deals initial board data.
     */
    public function generateTranslations(Request $request)
    {
        Artisan::call('translator:json');

        return response()->json([
            'message' => 'Translations generated successfully',
        ]);
    }
}
