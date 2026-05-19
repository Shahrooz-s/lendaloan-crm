<?php

namespace Modules\Sms\Services;

use Exception;
use Modules\Sms\Models\Sms;
use Modules\Sms\Traits\TwilioSettingTrait;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;
use Modules\Sms\Models\SmsLog;

class TwilioService
{
    use TwilioSettingTrait;
    /**
     * Twilio Client
     */
    protected $client;

    /**
     * Twilio instance parameters
     */
    protected $sid;
    protected $token;
    protected $from_number;

    /**
     * Status Callback Url
     */
    protected $status_callback_url;

    /**
     *
     * @throws \Twilio\Exceptions\ConfigurationException
     */
    public function __construct()
    {

        $setting = $this->getTwilioSetting();

        $this->sid = $setting["twilio_account_sid"];
        $this->token = $setting["twilio_auth_token"];
        $this->from_number = $setting["twilio_number"];
        $this->status_callback_url = route('api.twilio.status-changed');

        $this->client = new Client($this->sid, $this->token);
    }

    public function sendMessage($to, $body, $smsId): array
    {
        $result = ['success' => false, 'data' => [], 'message' => '', 'sms_id' => null];
        try {

            $options = array();
            $options['body'] = $body["message"];
            $options['from'] = $this->from_number;
            $options['statusCallback'] = $this->status_callback_url;

            $apiResponse = $this->client->messages->create($to, $options);

            $result['data'] = $apiResponse->toArray();
            Log::info("TWILIO SERVICE - SEND MESSAGE",["response" => $result["data"]]);

            if (!empty($result['data']['errorCode'])) {
                throw new Exception('Send SMS request failed');
            }
            $result['success'] = true;
            $result["data"]["status"] = "sent";
            $createdSms = $this->updateSms($smsId, $result);
            $result['sms_id'] = $createdSms->id ?? null;
            $result['message'] = 'SMS request success';
        } catch (Exception $ex) {
            $result['success'] = false;
            $result['message'] = $ex->getMessage();
            $result['data']['error_message'] = $result['message'];
            $result["data"]["status"] = "failed";
            $createdSms = $this->updateSms($smsId, $result);

            Log::error("TWILIO SERVICE ERROR",["error_message" => $ex->getMessage(), "result" => $result]);
            Log::channel('single')->error($ex->getFile().' :: '.$ex->getLine().' :: '.$ex->getMessage());

        }
        return $result;
    }

    /**
     * Get Twilio Client
     * @return Client
     */

    public function getClient()
    {
        return $this->client;
    }

    public function updateSms($id,$result){
        $sms = Sms::find($id);
        if($sms){
            $sms->update([
                'sid' => $result['data']['sid'] ?? null,
                'status' => $result['data']['status']
            ]);
        }
        return $sms;
    }
}
