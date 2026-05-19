<?php

namespace Modules\Sms\Http\Controllers\Api;

use Modules\Core\Facades\Module;
use Modules\Sms\Http\Requests\ModuleActivationRequest;
use Modules\Sms\Services\ModuleInitializationService;

class ModuleActivationController
{
    public function __construct(
        protected ModuleInitializationService $service
    ){}

    public function __invoke(ModuleActivationRequest $request)
    {
        $moduleName = 'sms';
        $module = Module::findOrFail($moduleName);

        $res = $this->service->handle($moduleName, $request->input('sms_activation_code'));

        return response()->json([
            'message' => $res['message'],
        ], $res['status_code']);
    }
}
