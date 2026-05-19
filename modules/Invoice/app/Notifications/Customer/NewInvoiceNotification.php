<?php

namespace Modules\Invoice\Notifications\Customer;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Contacts\Models\Contact;
use Modules\Core\MailableTemplate\MailableTemplate;
use Modules\Core\Notification;
use Modules\Invoice\Mail\Customer\NewInvoice as NewInvoiceMailable;
use Modules\Invoice\Models\Invoice;

class NewInvoiceNotification extends Notification implements ShouldQueue
{
    /**
     * Create a new notification instance.
     */
    public function __construct(protected Invoice $invoice, protected Contact $contact){}

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): NewInvoiceMailable&MailableTemplate
    {
        return (new NewInvoiceMailable($this->invoice, $this->contact))->to($notifiable);
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
                'key' => 'invoice::invoice.notifications.new_invoice',
                'attrs' => [
                    'invoice' => $this->invoice->invoice_number,
                ],
            ],
        ];
    }

}
