<?php

namespace Modules\Sms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Contacts\Models\Phone;

class SmsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'contacts' => ['required', 'array'],
            'contacts.*' => ['string', 'distinct',function ($attribute, $value, $fail) {
                if (!Phone::where('number', $value)->exists()) {
                    $fail("The phone number $value does not exist in the system.");
                }
            }],
            "message" => ['required', 'string'],
            "activity_type_id" => ['required'],
            "activities" => ['required','array'],
            "activities.*" => ['distinct','exists:activities,id'],
            "scheduled_at" => ['nullable', 'sometimes', 'date', 'after:'.now()->addMinute()->format('Y-m-d H:i')],

        ];
    }

    public function messages()
    {
        return [
            'scheduled_at.after' => "The scheduled date must be after one minute of current time."
        ];
    }
}
