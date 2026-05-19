<?php

namespace Modules\Invoice\Mail\Customer;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Contacts\Models\Contact;
use Modules\Core\Common\Placeholders\ActionButtonPlaceholder;
use Modules\Core\Common\Placeholders\PrivacyPolicyPlaceholder;
use Modules\Core\MailableTemplate\DefaultMailable;
use Modules\Core\Resource\ResourcePlaceholders;
use Modules\Invoice\Models\Invoice;
use Modules\MailClient\Mail\MailableTemplate;
use Modules\Invoice\Resources\Invoice as InvoiceResource;

class NewInvoice extends MailableTemplate implements ShouldQueue
{
    /**
     * Create a new mailable template instance.
     */
    public function __construct(protected Invoice $invoice, protected Contact $customer) {}

    /**
     * Provide the defined mailable template placeholders.
     */
    public function placeholders(): ResourcePlaceholders
    {
        return ResourcePlaceholders::make(new InvoiceResource, $this->invoice ?? null)
            ->push([
                PrivacyPolicyPlaceholder::make(),
                ActionButtonPlaceholder::make(fn () => $this->invoice->getOnlinePaymentUrl()),
            ]);
    }

    /**
     * Provides the mail template default configuration.
     */
    public static function default(): DefaultMailable
    {
        return new DefaultMailable(static::defaultHtmlTemplate(), static::defaultSubject());
    }

    /**
     * Provides the mail template default message.
     */
    public static function defaultHtmlTemplate(): string
    {
        return '<p>A new Invoice ( {{ invoice.invoice_number }} ) has been created for you.<br /></p>
                <p>Due date: {{ invoice.due_date }}<br /></p>
                <p>Please ensure that payment is settled soon, after contacting your sales agent.</p>
                {{#action_button}}Pay Online{{/action_button}}';
    }

    /**
     * Provides the mail template default subject.
     */
    public static function defaultSubject(): string
    {
        return '{{ invoice.invoice_number }} has been generated - Payment needed';
    }
}
