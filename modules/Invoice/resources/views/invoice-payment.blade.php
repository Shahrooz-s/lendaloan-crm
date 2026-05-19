<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />

    <title>{{  config('app.name') . ' - ' . __('invoice::invoice.payment') }}</title>

    <link rel="stylesheet" href="{{ asset('modules/invoice/app.css') }}">
    <style>
        .StripeElement {
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            background-color: white;
            min-height: 40px;
        }

        .StripeElement--focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 1px #3b82f6;
        }

        .StripeElement--invalid {
            border-color: #ef4444;
        }

        .spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
        }

        .btn-primary {
            align-items: center;
            background: rgb(67, 56, 202) none;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            box-sizing: border-box;
            color: rgb(255, 255, 255);
            column-gap: 8px;
            cursor: pointer;
            display: inline-flex;
            font: 600 14px/24px "Inter var", ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            height: 36px;
            isolation: isolate;
            justify-content: center;
            margin: 0;
            outline: 2px solid rgba(0, 0, 0, 0);
            outline-offset: 2px;
            padding: 5px 11px;
            position: relative;
            scrollbar-color: auto;
            scrollbar-width: auto;
            tab-size: 4;
            text-align: center;
            text-indent: 0;
            text-rendering: auto;
            text-shadow: none;
            text-transform: none;
            text-size-adjust: 100%;
        }

        .btn-primary:hover {
            background-color: rgb(79, 70, 229)
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .invoice-extra-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }

        .invoice-extra-grid > p {
            padding: 5px 0;
        }

        .invoice-extra-grid > p:nth-child(even) {
            text-align: right;
        }
    </style>
</head>

<body>

<div class="h-full min-h-screen dark:bg-neutral-800">
    <div class="w-full border-b border-neutral-200 bg-neutral-100 dark:border-neutral-500/30 dark:bg-neutral-900">
        <div class="m-auto max-w-6xl">
            <div class="p-4 flex justify-between">
                <h5 class="text-lg font-semibold text-neutral-800 dark:text-neutral-200">
                    @if(settings('logo_light'))
                        <img src="{{ settings('logo_light') }}" alt="{{ config('app.name') }}" style="max-height: 50px">
                    @else
                        {{ config('app.name')  }}
                    @endif
                </h5>

                <div>
                    <a href="{{ route('invoices.download', $invoice->uuid) }}" class="btn btn-primary">Download Pdf</a>
                </div>
            </div>
        </div>
    </div>
    <div class="m-auto max-w-6xl">
        <br>
        <br>
        <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden">
            <!-- Invoice Header -->
            <div class="bg-gray-800 text-white p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold">INVOICE</h1>
                        <p class="text-gray-400"> #{{ $invoice->invoice_number }} </p>
                    </div>
                    <div class="text-right">
                        <h2 class="text-xl"> {{ settings('company_name') }} </h2>
                        <p class="text-gray-400"> {{ $country }} </p>
                    </div>
                </div>
            </div>

            <!-- Client Info -->
            <div class="p-6 border-b">
                <div class="flex justify-between">
                    <div>
                        <h3 class="text-gray-600 font-semibold">Bill To:</h3>
                        <p class="text-gray-600"> {{ $invoice->customer->full_name }} </p>
                        <p class="text-gray-600">
                            {{ isset($invoice->customer->street) ? "{$invoice->customer->street}, ": "" }}
                            {{ isset($invoice->customer->city)? "{$invoice->customer->city}, ": "" }}
                            {{ isset($invoice->customer->state)? "{$invoice->customer->state}, ": "" }}
                            {{ $invoice->customer?->country?->name ?? "-" }}
                        </p>
                    </div>
                    <div class="text-right">
                        <h3 class="text-gray-600 font-semibold">Invoice Details:</h3>
                        <p><span class="text-gray-600">Date:</span> {{ $invoice->created_at->format('M d, Y') }}</p>
                        <p><span class="text-gray-600">Due Date:</span> {{ $invoice->due_date->format('M d, Y') }} </p>
                    </div>
                </div>
            </div>

            <div class="invoice-extra-grid" style="padding-left: 24px; padding-right: 24px">
                @foreach($extraFields as $field)
                    <p> <span class="text-gray-600 font-semibold" style="text-transform: capitalize">{{ $field['label'] }}</span>: {{ $field['value'] ?? '-' }}</p>
                @endforeach
            </div>

            <!-- Invoice Items -->
            <div class="p-6">
                <table class="w-full mb-8">
                    <thead>
                    <tr class="border-b-2 border-gray-300">
                        <th class="text-left py-3">Description</th>
                        <th class="text-center py-3">Quantity</th>
                        <th class="text-right py-3"></th>
                        <th class="text-right py-3">Amount</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($invoice->items as $item)
                        <tr class="border-b border-gray-200">
                            <td class="py-4"> {{ $item->name }} </td>
                            <td class="text-center"> {{ $item->quantity }} </td>
                            <td class="text-right">
                                {{--                                <x-money amount="{{ $item->unit_price * 100 }}" currency="{{ settings('currency') }}" />--}}
                            </td>
                            <td class="text-right">
                                <x-money amount="{{ $item->total * 100 }}" currency="{{ settings('currency') }}" />
                            </td>
                        </tr>
                    @endforeach

                    </tbody>
                    <tfoot>
                    <tr class="border-t border-gray-300">
                        <td colspan="3" class="text-right py-4 font-semibold">Subtotal:</td>
                        <td class="text-right py-4 font-semibold">
                            <x-money amount="{{ $invoice->taxable_amount * 100 }}"
                                     currency="{{ settings('currency') }}" />
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-right py-2">{{ settings('tax_label') ?? 'TAX' }} :
                        </td>
                        <td class="text-right">
                            <x-money amount="{{ $invoice->total_tax * 100 }}" currency="{{ settings('currency') }}" />
                        </td>
                    </tr>
                    <tr class="bg-gray-100">
                        <td colspan="3" class="text-right py-4 font-bold">Total:</td>
                        <td class="text-right font-bold">
                            <x-money amount="{{ $invoice->total * 100 }}" currency="{{ settings('currency') }}" />
                        </td>
                    </tr>
                    </tfoot>
                </table>

                @if($invoice->status != \Modules\Invoice\Enums\InvoiceStatusEnum::PAID)
                    <!-- Payment Buttons -->
                    <div class="flex justify-center gap-4 mt-8">
                        @if(settings('stripe_api_key') && settings('stripe_secret'))
                            <button onclick="openModal()"
                                    class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors flex items-center">
                                Pay with card
                            </button>
                        @endif

                        @if(settings('paypal_api_key') && settings('paypal_secret'))
                            <form action="{{ route('paypal.processTransaction', $invoice->uuid) }}" method="POST">
                                @csrf
                                <button
                                        class="bg-blue-800 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-900 transition-colors flex items-center">
                                    <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 384 512">
                                        <path
                                                d="M111.4 295.9c-3.5 19.2-17.4 108.7-21.5 134-.3 1.8-1 2.5-3 2.5H12.3c-7.6 0-13.1-6.6-12.1-13.9L58.8 46.6c1.5-9.6 10.1-16.9 20-16.9 152.3 0 165.1-3.7 204 11.4 60.1 23.3 65.6 79.5 44 140.3-21.5 62.6-72.5 89.5-140.1 90.3-43.4 .7-69.5-7-75.3 24.2zM357.1 152c-1.8-1.3-2.5-1.8-3 1.3-2 11.4-5.1 22.5-8.8 33.6-39.9 113.8-150.5 103.9-204.5 103.9-6.1 0-10.1 3.3-10.9 9.4-22.6 140.4-27.1 169.7-27.1 169.7-1 7.1 3.5 12.9 10.6 12.9h63.5c8.6 0 15.7-6.3 17.4-14.9 .7-5.4-1.1 6.1 14.4-91.3 4.6-22 14.3-19.7 29.3-19.7 71 0 126.4-28.8 142.9-112.3 6.5-34.8 4.6-71.4-23.8-92.6z" />
                                    </svg>
                                    Pay with PayPal
                                </button>
                            </form>
                        @endif

                    </div>

                @else
                    <div class="flex justify-center gap-4 mt-8">
                        <h2>Invoice has been paid</h2>
                    </div>
                @endif

                <!-- Notes -->
                <div class="mt-8 text-gray-600 border-t pt-6">
                    <h4 class="font-semibold mb-2">Notes:</h4>
                    <p>Thank you for your business! Payment is due within {{ $invoice->due_date->diffForHumans() }}.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modal-overlay"
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-40">
    <!-- Modal Container -->
    <div id="modal-container"
         class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 z-50"
         role="dialog"
         aria-modal="true">
        <!-- Modal Content -->
        <div class="p-6">
            <!-- Close Button -->
            <div class="flex justify-end">
                <button onclick="closeModal()"
                        class="text-gray-400 hover:text-gray-500 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Header -->
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900">
                    Payment details
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Enter your card information to complete the purchase
                </p>
            </div>

            <!-- Payment Form -->
            <form class="mt-8 space-y-6" method="POST" action="{{ route('stripe.processTransaction', $invoice->uuid) }}"
                  id="stripe-payment-form">
                @csrf
                <!-- Card Number -->
                <div>
                    <label for="card-number" class="block text-sm font-medium text-gray-700">
                        Card number
                    </label>
                    <div class="mt-1" id="card-number"></div>
                </div>

                <!-- Expiry and CVC -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Expiry -->
                    <div>
                        <label for="expiry" class="block text-sm font-medium text-gray-700">
                            Expiry date
                        </label>
                        <div class="mt-1" id="expiry"></div>
                    </div>

                    <!-- CVC -->
                    <div>
                        <label for="cvc" class="block text-sm font-medium text-gray-700">
                            CVC
                        </label>
                        <div class="mt-1" id="cvc"></div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit"
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Pay now
                    </button>
                </div>
            </form>

            <div id="card-element"></div>

            <!-- Security Notice -->
            <div class="mt-6 text-center">
                <p class="text-xs text-gray-500">
                    Payments are secure and encrypted
                </p>

            </div>
        </div>
    </div>
</div>
<div id="toast-container" class="fixed top-5 right-5 space-y-2"></div>

<script src="https://js.stripe.com/v3/"></script>

<script>
    // Modal functionality
    const modalOverlay = document.getElementById("modal-overlay");
    const modalContainer = document.getElementById("modal-container");

    function showToast(message, type = "info") {
        const container = document.getElementById("toast-container");
        const colors = {
            info: "bg-blue-500",
            success: "bg-green-500",
            warning: "bg-yellow-500",
            error: "bg-red-500"
        };

        const toast = document.createElement("div");
        toast.className = `px-4 py-2 rounded shadow-md text-white ${colors[type]}`;
        toast.textContent = message;

        container.appendChild(toast);

        // Remove toast after 3 seconds
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    function openModal() {
        modalOverlay.classList.remove("hidden");
        modalOverlay.classList.add("flex");
        // Prevent body scrolling when modal is open
        document.body.style.overflow = "hidden";
    }

    function closeModal() {
        modalOverlay.classList.add("hidden");
        modalOverlay.classList.remove("flex");
        // Restore body scrolling
        document.body.style.overflow = "auto";
    }

    // Close modal when clicking outside
    modalOverlay.addEventListener("click", (e) => {
        if (e.target === modalOverlay) {
            closeModal();
        }
    });

    // Close modal on escape key press
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            closeModal();
        }
    });


    // First, modify the form HTML to add error containers
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.querySelector("#stripe-payment-form");

        const errorContainer = document.createElement("div");
        errorContainer.id = "error-container";
        errorContainer.className = "mt-4 text-red-500 text-sm hidden";
        form.insertBefore(errorContainer, form.firstChild);

        // Initialize Stripe
        const stripe = Stripe('{{ settings('stripe_api_key') }}'); // Replace with your publishable key
        const elements = stripe.elements();

        // Create and mount the Card Element
        const style = {
            base: {
                color: "#32325d",
                fontFamily: "\"Helvetica Neue\", Helvetica, sans-serif",
                fontSmoothing: "antialiased",
                fontSize: "16px",
                "::placeholder": {
                    color: "#aab7c4"
                }
            },
            invalid: {
                color: "#fa755a",
                iconColor: "#fa755a"
            }
        };

        // Create individual elements for card number, expiry, and CVC
        const cardNumber = elements.create("cardNumber", {
            style: style,
            placeholder: "1234 1234 1234 1234"
        });
        const cardExpiry = elements.create("cardExpiry", {
            style: style,
            placeholder: "MM / YY"
        });
        const cardCvc = elements.create("cardCvc", {
            style: style,
            placeholder: "CVC"
        });

        // Mount Stripe Elements
        cardNumber.mount("#card-number");
        cardExpiry.mount("#expiry");
        cardCvc.mount("#cvc");

        // Handle real-time validation errors
        cardNumber.addEventListener("change", function(event) {
            handleValidationError(event, "card-number-error", cardNumber);
        });

        cardExpiry.addEventListener("change", function(event) {
            handleValidationError(event, "card-expiry-error", cardExpiry);
        });

        cardCvc.addEventListener("change", function(event) {
            handleValidationError(event, "card-cvc-error", cardCvc);
        });

        function handleValidationError(event, errorElementId, element) {
            const errorElement = document.getElementById(errorElementId);
            if (!errorElement) {
                const errorDiv = document.createElement("div");
                errorDiv.id = errorElementId;
                errorDiv.className = "text-red-500 text-sm mt-1";
                element._parent.appendChild(errorDiv);
            }

            if (event.error) {
                document.getElementById(errorElementId).textContent = event.error.message;
            } else {
                document.getElementById(errorElementId).textContent = "";
            }
        }

        // Add form submission handler
        form.addEventListener("submit", async function(event) {
            event.preventDefault();
            const submitButton = form.querySelector("button[type=\"submit\"]");
            const originalButtonText = submitButton.innerHTML;

            // Show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = "<div class=\"spinner\"></div> Processing...";

            try {

                // Show success message
                const successMessage = document.createElement("div");
                successMessage.className = "mt-4 text-green-500 text-sm";
                successMessage.textContent = "Processing Payment...";
                form.appendChild(successMessage);

                const result = await stripe.createPaymentMethod({
                    type: "card",
                    card: cardNumber
                });

                if (result.error) {
                    // Handle errors
                    const errorElement = document.getElementById("error-container");
                    errorElement.textContent = result.error.message;
                    errorElement.classList.remove("hidden");
                } else {
                    // Payment method created successfully
                    const paymentMethodId = result.paymentMethod.id;

                    const { token, error } = await stripe.createToken(cardNumber);

                    if (error) {
                        const errorElement = document.getElementById("error-container");
                        errorElement.textContent = error.message;
                        errorElement.classList.remove("hidden");
                    } else {
                        // Append token to the form and submit
                        const hiddenInput = document.createElement("input");
                        hiddenInput.setAttribute("type", "hidden");
                        hiddenInput.setAttribute("name", "stripeToken");
                        hiddenInput.setAttribute("value", token.id);
                        form.appendChild(hiddenInput);

                        // Submit the form
                        form.submit();
                    }

                }
            } catch (error) {
                // Handle unexpected errors
                const errorElement = document.getElementById("error-container");
                errorElement.textContent = "An unexpected error occurred. Please try again.";
                errorElement.classList.remove("hidden");
            } finally {
                // Reset button state
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                successMessage.remove();
            }
        });
    });

    @if (Session::has('success'))
    showToast('{{ Session::get('success') }}', "success");
    @elseif(Session::has('error'))
    showToast('{{ Session::get('error') }}', "error");
    @endif

</script>
</body>

</html>
