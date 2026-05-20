<?php
/**
 * Concord CRM - https://www.concordcrm.com
 *
 * @version   1.7.0
 *
 * @link      Releases - https://www.concordcrm.com/releases
 * @link      Terms Of Service - https://www.concordcrm.com/terms
 *
 * @copyright Copyright (c) 2022-2025 KONKORD DIGITAL
 */

namespace Modules\WebForms\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HtmlString;
use Modules\Activities\Models\Activity;
use Modules\Activities\Models\ActivityType;
use Modules\Contacts\Enums\PhoneType;
use Modules\Contacts\Models\Company;
use Modules\Contacts\Models\Contact;
use Modules\Contacts\Models\Phone;
use Modules\Contacts\Models\Source;
use Modules\Core\Facades\Innoclapps;
use Modules\Deals\Models\Deal;
use Modules\Deals\Models\Pipeline;
use Modules\Notes\Models\Note;
use Modules\Users\Models\User;
use Plank\Mediable\Facades\MediaUploader;
use Throwable;

class OpnFormSubmissionBridge
{
    public function handle(array $payload): array
    {
        return DB::transaction(function () use ($payload) {
            $fields = $this->fieldsFromPayload($payload);
            $owner = $this->owner();
            $source = Source::firstOrCreate(['flag' => 'web-form'], ['name' => 'Web Form']);
            $pipeline = Pipeline::findPrimary();
            $stage = $pipeline->stages()->first();

            $contact = $this->findOrCreateContact($fields, $owner, $source);
            $company = $this->findOrCreateCompany($fields, $owner, $source);
            $deal = $this->createDeal($fields, $owner, $pipeline, $stage);

            $deal->contacts()->syncWithoutDetaching($contact);

            if ($company) {
                $company->contacts()->syncWithoutDetaching($contact);
                $company->deals()->syncWithoutDetaching($deal);
            }

            $note = $this->createSubmissionNote($payload, $fields, $owner, $deal, $contact, $company);
            $activity = $this->createAppointmentActivity($fields, $owner, $deal, $contact, $company);

            $this->attachUploadedFiles($fields, $deal);

            return array_filter([
                'contact_id' => $contact->id,
                'company_id' => $company?->id,
                'deal_id' => $deal->id,
                'note_id' => $note->id,
                'activity_id' => $activity?->id,
            ]);
        });
    }

    protected function fieldsFromPayload(array $payload): array
    {
        $fields = [];

        foreach ((array) Arr::get($payload, 'data', []) as $id => $field) {
            if (! is_array($field)) {
                continue;
            }

            $fields[] = [
                'id' => (string) $id,
                'name' => (string) ($field['name'] ?? $id),
                'type' => (string) ($field['type'] ?? ''),
                'value' => $field['value'] ?? null,
            ];
        }

        foreach ((array) Arr::get($payload, 'submission', []) as $name => $value) {
            if (collect($fields)->contains(fn ($field) => $field['name'] === $name)) {
                continue;
            }

            $fields[] = [
                'id' => (string) $name,
                'name' => (string) $name,
                'type' => '',
                'value' => $value,
            ];
        }

        return $fields;
    }

    protected function owner(): User
    {
        $email = env('OPNFORM_CONCORD_OWNER_EMAIL', 'shahrooz@lendaloan.com.au');

        return User::where('email', $email)->first()
            ?: User::orderBy('id')->firstOrFail();
    }

    protected function findOrCreateContact(array $fields, User $owner, Source $source): Contact
    {
        $email = $this->firstByLabel($fields, ['email', 'e-mail']) ?: $this->firstEmail($fields);
        $phone = $this->firstByLabel($fields, ['phone', 'mobile', 'telephone']);
        $fullName = $this->firstByLabel($fields, ['full name', 'name', 'contact name', 'applicant name', 'client name']);
        $firstName = $this->firstByLabel($fields, ['first name', 'given name']);
        $lastName = $this->firstByLabel($fields, ['last name', 'surname', 'family name']);

        if (! $firstName && $fullName) {
            [$firstName, $lastNameFromFullName] = array_pad(preg_split('/\s+/', trim($fullName), 2), 2, null);
            $lastName = $lastName ?: $lastNameFromFullName;
        }

        $contact = $email ? Contact::where('email', $email)->first() : null;

        if (! $contact) {
            $contact = tap(new Contact)->forceFill([
                'first_name' => $firstName ?: $email ?: $phone ?: 'Unknown',
                'last_name' => $lastName,
                'email' => $email,
                'user_id' => $owner->id,
                'source_id' => $source->id,
                'owner_assigned_date' => now(),
            ]);

            $contact->save();
        }

        if ($phone && ! $contact->phones()->where('number', $phone)->exists()) {
            $contact->phones()->save(new Phone([
                'number' => $phone,
                'type' => PhoneType::mobile,
            ]));
        }

        $this->fillCustomFields($contact, [
            'cf_contact_role' => $this->firstByLabel($fields, ['contact role', 'role']),
            'cf_applicant_role' => $this->firstByLabel($fields, ['applicant role', 'borrower role', 'borrower type']),
            'cf_brokerengine_contact_id' => $this->firstByLabel($fields, ['brokerengine contact id', 'broker engine contact id']),
            'cf_afg_flex_contact_id' => $this->firstByLabel($fields, ['afg flex contact id', 'flex contact id']),
            'cf_fact_find_session_id' => $this->firstByLabel($fields, ['fact find session', 'fact find id', 'fact-find id', 'session id']),
            'cf_privacy_consent_status' => $this->firstByLabel($fields, ['privacy consent', 'privacy consent status']),
            'cf_marketing_consent_status' => $this->firstByLabel($fields, ['marketing consent', 'marketing consent status']),
            'cf_date_of_birth' => $this->date($this->firstByLabel($fields, ['date of birth', 'dob', 'birth date'])),
            'cf_preferred_name' => $this->firstByLabel($fields, ['preferred name']),
            'cf_residency_status' => $this->firstByLabel($fields, ['residency status', 'residential status']),
        ]);

        return $contact;
    }

    protected function findOrCreateCompany(array $fields, User $owner, Source $source): ?Company
    {
        $name = $this->firstByLabel($fields, ['company', 'company name', 'business', 'business name', 'employer']);

        if (! $name) {
            return null;
        }

        if ($company = Company::where('name', $name)->first()) {
            $this->fillCustomFields($company, $this->companyCustomFields($fields));

            return $company;
        }

        $company = tap(new Company)->forceFill([
            'name' => $name,
            'email' => $this->firstByLabel($fields, ['company email', 'business email']),
            'domain' => $this->firstByLabel($fields, ['domain', 'website', 'company website']),
            'user_id' => $owner->id,
            'source_id' => $source->id,
            'owner_assigned_date' => now(),
        ]);

        $company->save();

        $this->fillCustomFields($company, $this->companyCustomFields($fields));

        return $company;
    }

    protected function companyCustomFields(array $fields): array
    {
        return [
            'cf_abn' => $this->firstByLabel($fields, ['abn', 'australian business number']),
            'cf_acn' => $this->firstByLabel($fields, ['acn', 'australian company number']),
            'cf_organisation_type' => $this->firstByLabel($fields, ['organisation type', 'organization type', 'company type', 'entity type']),
            'cf_company_role' => $this->firstByLabel($fields, ['company role', 'business role']),
            'cf_external_reference' => $this->firstByLabel($fields, ['external reference', 'external id', 'crm reference']),
        ];
    }

    protected function createDeal(array $fields, User $owner, Pipeline $pipeline, $stage): Deal
    {
        $name = $this->firstByLabel($fields, ['deal', 'deal name', 'loan purpose', 'enquiry', 'subject'])
            ?: $this->firstByLabel($fields, ['full name', 'name', 'contact name', 'applicant name', 'client name'])
            ?: 'OpnForm Submission';

        $deal = tap(new Deal)->forceFill([
            'name' => 'Form - '.$name,
            'amount' => $this->money($this->firstByLabel($fields, ['amount', 'loan amount', 'budget', 'price'])),
            'expected_close_date' => $this->date($this->firstByLabel($fields, ['settlement date', 'close date', 'expected close date'])),
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'user_id' => $owner->id,
            'owner_assigned_date' => now(),
        ]);

        $deal->save();

        $this->fillCustomFields($deal, [
            'cf_deal_type' => $this->firstByLabel($fields, ['deal type', 'application type', 'loan type']),
            'cf_application_status' => $this->firstByLabel($fields, ['application status', 'loan status']),
            'cf_fact_find_status' => $this->firstByLabel($fields, ['fact find status', 'fact-find status']),
            'cf_fact_find_session_id' => $this->firstByLabel($fields, ['fact find session', 'fact find id', 'fact-find id', 'session id']),
            'cf_mortgage_application_id' => $this->firstByLabel($fields, ['mortgage application id', 'application id']),
            'cf_target_settlement_date' => $this->date($this->firstByLabel($fields, ['target settlement date', 'settlement date'])),
            'cf_estimated_property_value' => $this->money($this->firstByLabel($fields, ['estimated property value', 'property value', 'purchase price'])),
            'cf_loan_purpose' => $this->firstByLabel($fields, ['loan purpose', 'purpose']),
            'cf_lixi_stage' => $this->firstByLabel($fields, ['lixi stage', 'application stage']),
            'cf_document_status' => $this->firstByLabel($fields, ['document status', 'documents status', 'docs status']),
            'cf_serviceability_status' => $this->firstByLabel($fields, ['serviceability status', 'servicing status']),
            'cf_product_search_url' => $this->firstByLabel($fields, ['product search url', 'product tool url']),
            'cf_product_search_status' => $this->firstByLabel($fields, ['product search status', 'product status']),
            'cf_brokerengine_deal_id' => $this->firstByLabel($fields, ['brokerengine deal id', 'broker engine deal id']),
            'cf_afg_flex_application_id' => $this->firstByLabel($fields, ['afg flex application id', 'flex application id']),
            'cf_applyonline_reference' => $this->firstByLabel($fields, ['applyonline reference', 'apply online reference']),
            'cf_submission_snapshot_reference' => $this->firstByLabel($fields, ['submission snapshot reference', 'submission snapshot url']),
            'cf_missing_information_count' => $this->integer($this->firstByLabel($fields, ['missing information count', 'missing info count'])),
            'cf_form_submission_reference' => $this->firstByLabel($fields, ['form submission reference', 'submission id']),
            'cf_security_property_address' => $this->firstByLabel($fields, ['security property address', 'property address']),
            'cf_estimated_loan_amount' => $this->money($this->firstByLabel($fields, ['estimated loan amount', 'loan amount', 'borrow amount'])),
            'cf_lvr' => $this->decimal($this->firstByLabel($fields, ['lvr', 'loan to value ratio'])),
        ]);

        return $deal;
    }

    protected function createSubmissionNote(array $payload, array $fields, User $owner, Deal $deal, Contact $contact, ?Company $company): Note
    {
        $lines = [
            '<strong>OpnForm submission synced to Concord</strong>',
            'Form: '.$this->escape((string) ($payload['form_title'] ?? $payload['form_slug'] ?? $payload['form_id'] ?? 'Unknown')),
            'Submission ID: '.$this->escape((string) ($payload['submission_id'] ?? '')),
            '',
        ];

        foreach ($fields as $field) {
            $lines[] = '<strong>'.$this->escape($field['name']).':</strong> '.$this->escape($this->stringValue($field['value']));
        }

        $note = tap(new Note)->forceFill([
            'body' => implode('<br>', $lines),
            'user_id' => $owner->id,
        ]);

        $note->save();

        $note->deals()->attach($deal);
        $note->contacts()->attach($contact);

        if ($company) {
            $note->companies()->attach($company);
        }

        return $note;
    }

    protected function createAppointmentActivity(array $fields, User $owner, Deal $deal, Contact $contact, ?Company $company): ?Activity
    {
        $dateValue = $this->firstByLabel($fields, ['appointment', 'appointment date', 'meeting date', 'booking date', 'preferred date']);
        $timeValue = $this->firstByLabel($fields, ['appointment time', 'meeting time', 'booking time', 'preferred time']);
        $date = $this->date($dateValue);

        if (! $date) {
            return null;
        }

        $type = ActivityType::where('flag', 'meeting')->first() ?: ActivityType::first();
        $due = Carbon::parse(trim($date.' '.($timeValue ?: '09:00')));
        $end = $due->copy()->addHour();

        $activity = tap(new Activity)->forceFill([
            'title' => 'Form appointment',
            'description' => 'Appointment request from OpnForm submission.',
            'due_date' => $due->format('Y-m-d'),
            'due_time' => $timeValue ? $due->format('H:i:s') : null,
            'end_date' => $end->format('Y-m-d'),
            'end_time' => $timeValue ? $end->format('H:i:s') : null,
            'activity_type_id' => $type?->id,
            'user_id' => $owner->id,
            'created_by' => $owner->id,
            'owner_assigned_date' => now(),
        ]);

        $activity->save();

        $activity->contacts()->syncWithoutDetaching($contact);
        $activity->deals()->syncWithoutDetaching($deal);

        if ($company) {
            $activity->companies()->syncWithoutDetaching($company);
        }

        return $activity;
    }

    protected function attachUploadedFiles(array $fields, Deal $deal): void
    {
        foreach ($fields as $field) {
            if (! str_contains(strtolower($field['type'].' '.$field['name']), 'file')) {
                continue;
            }

            foreach (Arr::wrap($field['value']) as $url) {
                if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
                    continue;
                }

                $this->attachRemoteFile($url, $deal);
            }
        }
    }

    protected function attachRemoteFile(string $url, Deal $deal): void
    {
        try {
            $response = Http::timeout(20)->get($url);

            if (! $response->successful()) {
                return;
            }

            $name = basename(parse_url($url, PHP_URL_PATH) ?: 'opnform-upload');
            $extension = pathinfo($name, PATHINFO_EXTENSION) ?: 'bin';
            $tmpFile = tmpfile();

            fwrite($tmpFile, $response->body());

            $media = MediaUploader::fromSource($tmpFile)
                ->toDirectory($deal->getMediaDirectory())
                ->onDuplicateIncrement()
                ->useFilename(pathinfo($name, PATHINFO_FILENAME) ?: 'opnform-upload')
                ->setAllowedExtensions(array_unique(array_merge(Innoclapps::allowedUploadExtensions(), [$extension])))
                ->upload();

            $deal->attachMedia($media, $deal->getMediaTags());
        } catch (Throwable $e) {
            Log::warning('Unable to attach OpnForm upload to Concord deal.', [
                'deal_id' => $deal->id,
                'url' => $url,
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function firstByLabel(array $fields, array $needles): mixed
    {
        foreach ($fields as $field) {
            $label = strtolower($field['name']);

            foreach ($needles as $needle) {
                if ($label === $needle || str_contains($label, $needle)) {
                    return $this->scalarValue($field['value']);
                }
            }
        }

        return null;
    }

    protected function firstEmail(array $fields): ?string
    {
        foreach ($fields as $field) {
            if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $this->stringValue($field['value']), $match)) {
                return $match[0];
            }
        }

        return null;
    }

    protected function fillCustomFields($model, array $values): void
    {
        $table = $model->getTable();

        foreach ($values as $field => $value) {
            if ($value === null || $value === '' || ! Schema::hasColumn($table, $field)) {
                continue;
            }

            if (! empty($model->{$field}) && $model->{$field} !== $value) {
                continue;
            }

            $model->{$field} = $value;
        }

        if ($model->isDirty()) {
            $model->save();
        }
    }

    protected function scalarValue(mixed $value): mixed
    {
        return is_array($value) ? Arr::first(Arr::flatten($value)) : $value;
    }

    protected function stringValue(mixed $value): string
    {
        if ($value instanceof HtmlString) {
            return $value->toHtml();
        }

        if (is_array($value)) {
            return implode(', ', Arr::flatten($value));
        }

        return (string) $value;
    }

    protected function money(mixed $value): ?float
    {
        $number = preg_replace('/[^0-9.]/', '', (string) $value);

        return $number === '' ? null : (float) $number;
    }

    protected function decimal(mixed $value): ?float
    {
        $number = preg_replace('/[^0-9.]/', '', (string) $value);

        return $number === '' ? null : (float) $number;
    }

    protected function integer(mixed $value): ?int
    {
        $number = preg_replace('/[^0-9]/', '', (string) $value);

        return $number === '' ? null : (int) $number;
    }

    protected function date(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    protected function escape(string $value): string
    {
        return e($value);
    }
}
