<?php

namespace Modules\Invoice\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Modules\Core\Facades\Module;
use Modules\Deals\Models\Pipeline;
use Modules\Deals\Models\Stage;
use Modules\Invoice\Http\Requests\ModuleActivationRequest;
use Modules\Invoice\Services\ModuleInitializationService;

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
        $moduleName = 'invoice';
        $module = Module::findOrFail($moduleName);
        $res = $this->service->handle($moduleName, $request->input('invoice_activation_code'));

        if ($res['status_code'] == 200) {
            $this->addStage();
        }

        return response()->json([
            'message' => $res['message'],
        ], $res['status_code']);
    }

    private function addStage()
    {
        $pipeline = Pipeline::where('name', 'Sales Pipeline')->first();
        $latestStage = Stage::orderBy('display_order', 'desc')->first();

        try
        {
            Stage::create(['name' => 'Invoice Paid', 'pipeline_id' => $pipeline->id, 'display_order' => $latestStage->display_order + 1]);
        } catch (QueryException $e)
        {
            Log::error("Error while creating stage: " . $e->getMessage());
        }

    }
}
