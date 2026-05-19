<?php

namespace Modules\Sms\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Sms\Services\TwilioService;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $to;
    protected $body;
    protected $smsId;

    /**
     * Create a new job instance.
     *
     * @param string $to
     * @param array $body
     * @param int $smsId
     */
    public function __construct(string $to, array $body, int $smsId)
    {
        $this->to = $to;
        $this->body = $body;
        $this->smsId = $smsId;
    }

    /**
     * Execute the job.
     *
     * @param TwilioService $twilioService
     * @return void
     */
    public function handle(TwilioService $twilioService)
    {
        try {
            $response = $twilioService->sendMessage($this->to, $this->body, $this->smsId);
            Log::info('SendSmsJob executed successfully', ['response' => $response]);
        } catch (Exception $e) {
            Log::error('SendSmsJob failed', [
                'error_message' => $e->getMessage(),
                'to' => $this->to,
                'body' => $this->body,
                'smsId' => $this->smsId,
            ]);
        }
    }
}
