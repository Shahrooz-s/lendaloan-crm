<?php
/**
 * Concord CRM - https://www.concordcrm.com
 *
 * @version   1.7.0
 *
 * @link      Releases - https://www.concordcrm.com/releases
 * @link      Terms Of Service - https://www.concordcrm.com/terms
 *
 * @copyright Copyright (c) 2022-2025 KONKORD DIGITAL
 */

namespace Modules\WebForms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\WebForms\Services\OpnFormSubmissionBridge;

class OpnFormWebhookController extends Controller
{
    public function __invoke(Request $request, OpnFormSubmissionBridge $bridge): JsonResponse
    {
        $token = (string) env('OPNFORM_CONCORD_WEBHOOK_TOKEN', '');

        $providedToken = (string) ($request->bearerToken()
            ?: $request->header('X-Concord-Webhook-Token')
            ?: $request->query('token'));

        abort_if($token === '' || ! hash_equals($token, $providedToken), 403);

        $result = $bridge->handle($request->all());

        return response()->json($result, 201);
    }
}
