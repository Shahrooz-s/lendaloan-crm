<?php

namespace Modules\Sms\Console;

use Illuminate\Console\Command;
use Modules\Sms\Models\Schedules;
use Illuminate\Support\Carbon;
use Modules\Sms\Services\TwilioService;

class SendScheduledSms extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'send:scheduled-sms';

    /**
     * The console command description.
     */
    protected $description = 'Sends scheduled SMS.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentDate = Carbon::now()->toDateTimeString('minutes');
        $scheduledSms = Schedules::where('scheduled_at', '>=', $currentDate)->get();
        $twilioService = new TwilioService();
        $scheduledSms->each(function ($scheduledSms) use ($currentDate, $twilioService) {
            $scheduleDate = Carbon::parse($scheduledSms["scheduled_at"])->toDateTimeString("minute");

            if ($currentDate == $scheduleDate) {
                $data = json_decode($scheduledSms->data, true);
                $response = $twilioService->sendMessage($data["to"], $data, $data["id"]);
                if ($response["success"]) {
                    $scheduledSms->delete();
                }
            }
        });
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [];
    }
}
