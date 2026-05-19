<?php

namespace Modules\GoogleWorkspace\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Core\Facades\Module;
use Modules\GoogleWorkspace\Http\Requests\ModuleActivationRequest;
use Modules\GoogleWorkspace\Services\ModuleInitializationService;

class ModuleActivationController extends Controller
{
    public function __construct(
        protected ModuleInitializationService $service
    ){}
    /**
     * Get the deals initial board data.
     */
    public function __invoke(ModuleActivationRequest $request)
    {
        $moduleName = 'googleworkspace';
        $module = Module::findOrFail($moduleName);

        $res = $this->service->handle($moduleName, $request->input('googleworkspace_activation_code'));

        return response()->json([
            'message' => $res['message'],
        ], $res['status_code']);
    }

}
