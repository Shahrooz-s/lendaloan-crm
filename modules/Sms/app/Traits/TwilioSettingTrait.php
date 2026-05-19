<?php

namespace Modules\Sms\Traits;

use Illuminate\Support\Str;

trait TwilioSettingTrait
{
    public function getTwilioSetting()
    {
        $setting = settings()->all();

        if (
            !isset($setting["twilio_account_sid"]) || empty($setting["twilio_account_sid"]) ||
            !isset($setting["twilio_auth_token"]) ||  empty($setting["twilio_auth_token"]) ||
            !isset($setting["twilio_app_sid"]) ||  empty($setting["twilio_app_sid"]) ||
            !isset($setting["twilio_number"]) ||  empty($setting["twilio_number"])
        )
            return false;
        else
            return [
                "twilio_account_sid" => $setting["twilio_account_sid"],
                "twilio_auth_token" => $setting["twilio_auth_token"],
                "twilio_app_sid" => $setting["twilio_app_sid"],
                "twilio_number" => $setting["twilio_number"],
            ];
    }
}
