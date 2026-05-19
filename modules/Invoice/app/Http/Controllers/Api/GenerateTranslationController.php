<?php

namespace Modules\Invoice\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class GenerateTranslationController extends Controller
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
