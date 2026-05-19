<?php

namespace Modules\Invoice\Mail\Customer;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Contacts\Models\Contact;
use Modules\Core\Common\Placeholders\PrivacyPolicyPlaceholder;
use Modules\Core\MailableTemplate\DefaultMailable;
use Modules\Core\Resource\ResourcePlaceholders;
use Modules\Invoice\Models\Invoice;
use Modules\MailClient\Mail\MailableTemplate;
use Modules\Invoice\Resources\Invoice as InvoiceResource;

class InvoicePaidForCustomer extends MailableTemplate implements ShouldQueue {
    /**
     * Create a new mailable template instance.
     */
    public function __construct(protected Invoice $invoice, protected Contact $customer)
    {
    }

    /**
     * Provide the defined mailable template placeholders.
     */
    public function placeholders(): ResourcePlaceholders
    {
        return ResourcePlaceholders::make(new InvoiceResource, $this->invoice ?? null)
            ->push([
                PrivacyPolicyPlaceholder::make(),
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
        return '<p>Invoice ( {{ invoice.invoice_number }} ) has been marked as paid.<br /></p>
                <p>Thank you for your business!</p>';
    }

    /**
     * Provides the mail template default subject.
     */
    public static function defaultSubject(): string
    {
        return '( {{ invoice.invoice_number }} ) has been marked as paid';
    }
}
