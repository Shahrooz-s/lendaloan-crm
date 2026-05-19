<?php

namespace Modules\Sms\Http\Controllers\Api;

use Illuminate\Support\Facades\Blade;
use Modules\Contacts\Models\Phone;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Sms\Http\Requests\SmsRequest;
use Modules\Sms\Models\SmsTemplate;
use Modules\Sms\Traits\TwilioSettingTrait;
use Exception;
use Modules\Sms\Models\Schedules;
use Modules\Sms\Models\Sms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Sms\Jobs\SendSmsJob;

class SmsController extends ApiController
{
    use TwilioSettingTrait;

    public function sendSms(SmsRequest $request)
    {
        $validatedData = $request->validated();
        $settings = $this->getTwilioSetting();

        $phones = Phone::query()->with('phoneable')->whereIn('number', $validatedData['contacts'])->get();

        if (!$settings)
            return response()->json(['message' => 'Twilio SMS configurations not found!'], 400);

        foreach ($validatedData["contacts"] as $key => $number) {
            $contact = $phones->where('number', $number)->first()->phoneable;

            $validatedData['message'] = $this->replaceMessageVariables($validatedData['message'], [
                'firstName' => $contact['first_name'],
                'lastName' => $contact['last_name'],
                'fullName' => $contact['full_name'],
                'email' => $contact['email'],
                'phoneNumber' => $number,
            ]);

            $sms = Sms::create([
                'direction' => 'sent',
                'from' => $settings['twilio_number'],
                'to' => $number,
                'status' => isset($validatedData["scheduled_at"]) && $validatedData["scheduled_at"] ? 'queued' : 'sending',
                'message' => $validatedData['message'],
                'activity_type_id' => $validatedData['activity_type_id']
            ]);
            if (!empty($validatedData["scheduled_at"])) {

                $scheduleData = ["data" => json_encode($sms), "scheduled_at" => $validatedData["scheduled_at"]];
                Schedules::create($scheduleData);
                $action = "scheduled";
            } else {
                // $sendResult = app('TwilioService')->sendMessage($number, $validatedData["message"]);
                // if (!isset($sendResult['success']) || !$sendResult['success']) {
                //     throw new Exception(($sendResult['message'] ?? ''));
                // }

                dispatch(new SendSmsJob($number, $validatedData, $sms->id));
                $action = "processed";
            }
        }
        return response()->json(['message' => "SMS $action successfully!"], 200);
    }

    public function replaceMessageVariables(string $message, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $placeholder = '{{ ' . $key . ' }}';
            $message = str_replace($placeholder, $value, $message);
        }

        return $message;
    }

    /**
     * This function is a public exposed route that handles twilio requests (from twilio) to inform status changes from messages
     * Format application/x-www-form-urlencoded
     * Method POST
     * Request parameters
     *   SmsSid: SM2xxxxxx
     *   SmsStatus: sent
     *   Body: McAvoy or Stewart? These timelines can get so confusing.
     *   MessageStatus: sent
     *   To: +1512zzzyyyy
     *   MessageSid: SM2xxxxxx
     *   AccountSid: ACxxxxxxx
     *   From: +1512xxxyyyy
     *   ApiVersion: 2010-04-01
     */
    public function statusChanged(Request $request)
    {

        try {

            $logData = [
                'sms_sid' => $request['SmsSid'] ?? null,
                'sms_message_sid' => $request['MessageSid'] ?? null,
                'twilio_sms_id' => null,
                'event' => 'not_categorized',
                'new_status' => $request['MessageStatus'] ?? null,
                'details' => json_encode(($request->all() ?? [])),
            ];

            Log::debug("SMS CONTROLLER STATUS CHANGED REQUEST", ["request" => $logData]);

            try {
                if (!isset($request['SmsSid'])) {
                    throw new Exception('Sid not defined. Could not match with system sms.');
                }

                $sms = Sms::select('id', 'sid', 'status')->where('sid', $request['SmsSid'])->first();

                if (empty($sms->id)) {
                    throw new Exception('Twilio sms sid: ' . $request['SmsSid'] . ' was not found.');
                }

                if (isset($request['SmsStatus']) && $sms->status != $request['SmsStatus']) {
                    $sms->status = $request['SmsStatus'];
                    $sms->save();
                }
            } catch (Exception $ex2) {
                Log::debug("SMS CONTROLLER STATUS CHANGED ERROR", [$ex2->getFile() . ' :: ' . $ex2->getMessage()]);
            }
        } catch (Exception $ex) {
            Log::debug("SMS CONTROLLER STATUS CHANGED ERROR", [$ex2->getFile() . ' :: ' . $ex2->getMessage() . ' :: ' . json_encode(($request->all() ?? []))]);
        }

        return response(['success' => true], 200);
    }

    public function searchTemplate()
    {
        $searchString = request()->get('search');
        if (empty($searchString)) {
            return response(['message' => 'Search string is empty.'], 400);
        }

        $templates = SmsTemplate::query()->where('name', 'like', '%' . $searchString . '%')->get();

        return response(['data' => $templates], 200);
    }
}
