<?php

namespace Modules\Sms\Http\Controllers\Api;

use Modules\Core\Http\Controllers\ApiController;
use Modules\Activities\Models\Activity;
use Modules\Activities\Models\ActivityType;


class ActivityController extends ApiController
{
    public function activityTypes()
    {
        return response()->json(ActivityType::get());
    }

    public function getAllByActivityTypeId(string $activityTypeId)
    {
        $activities = Activity::query()->where(["activity_type_id" => $activityTypeId])->whereHas('contacts.phones')->with('contacts.phones')->get();

        return response()->json($activities);
    }
}
