<?php

namespace  Modules\Sms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\Sms\Traits\TwilioSettingTrait;
use Twilio\Security\RequestValidator;

class TwilioRequestIsValid
{
    use TwilioSettingTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $twilioToken = settings()->get('twilio_auth_token');
        $requestValidator = new RequestValidator($twilioToken);

        $requestData = $request->toArray();

        // Switch to the body content if this is a JSON request.
        if (array_key_exists('bodySHA256', $requestData)) {
            $requestData = $request->getContent();
        }

        $isValid = $requestValidator->validate(
            $request->header('X-Twilio-Signature'),
            $request->fullUrl(),
            $requestData
        );

        if ($isValid) {
            return $next($request);
        } else {
            abort(Response::HTTP_NOT_FOUND);
        }
    }
}
