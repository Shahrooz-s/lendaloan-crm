<?php

namespace Modules\Invoice\Notifications\SalesAgent;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Contacts\Models\Contact;
use Modules\Core\MailableTemplate\MailableTemplate;
use Modules\Core\Notification;
use Modules\Invoice\Mail\SalesAgent\InvoicePaidForSalesAgent as PaidInvoiceMailable;
use Modules\Invoice\Models\Invoice;

class InvoicePaidNotification  extends Notification implements ShouldQueue
{
    /**
     * Create a new notification instance.
     */
    public function __construct(protected Invoice $invoice, protected Contact $contact){}

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): PaidInvoiceMailable&MailableTemplate
    {
        return (new PaidInvoiceMailable($this->invoice, $this->contact))->to($notifiable);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'path' => $this->invoice->resource()->viewRouteFor($this->invoice),
            'lang' => [
                'key' => 'invoice::invoice.notifications.invoice_paid',
                'attrs' => [
                    'invoice' => $this->invoice->invoice_number,
                ],
            ],
        ];
    }

}
