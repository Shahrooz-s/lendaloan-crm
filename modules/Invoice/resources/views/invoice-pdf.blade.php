<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') . ' - ' . __('invoice::invoice.payment') }}</title>
    <style>
        /* PDF-friendly CSS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Helvetica', 'Arial', sans-serif;
        }

        body {
            background-color: #f3f4f6;
            color: #111827;
            font-size: 14px;
            line-height: 1.5;
            padding: 0 0 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Header Styles */
        .invoice-header {
            background: #1f2937;
            color: white;
            padding: 30px;
        }

        .header-table {
            width: 100%;
        }

        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .invoice-number {
            color: #9ca3af;
            font-size: 16px;
        }

        .company-name {
            font-size: 20px;
            margin-bottom: 5px;
            text-align: right;
        }

        .company-country {
            color: #9ca3af;
            text-align: right;
        }

        /* Client Info Styles */
        .client-section {
            padding: 30px;
            border-bottom: 1px solid #e5e7eb;
        }

        .client-table {
            width: 100%;
        }

        .bill-to h3, .invoice-details h3 {
            font-weight: 600;
            margin-bottom: 10px;
            color: #4b5563;
        }

        .invoice-details {
            text-align: right;
        }

        /* Custom Fields Section */
        .custom-fields-section {
            padding: 0 30px 30px;
            border-bottom: 1px solid #e5e7eb;
        }

        .custom-fields-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .custom-fields-grid td {
            padding: 8px 0;
            vertical-align: top;
            width: 50%;
        }

        .custom-fields-grid .field-label {
            color: #6b7280;
            font-weight: 600;
            text-transform: capitalize;
            padding-right: 10px;
        }

        .custom-fields-grid .field-value {
            color: #111827;
        }

        .right-align {
            text-align: right;
        }

        /* Table Styles */
        .items-section {
            padding: 30px;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .invoice-table th {
            text-align: left;
            padding: 12px 0;
            border-bottom: 2px solid #d1d5db;
            font-weight: 600;
            color: #4b5563;
        }

        .invoice-table th.text-center {
            text-align: center;
        }

        .invoice-table th.text-right {
            text-align: right;
        }

        .invoice-table td {
            padding: 15px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .invoice-table td.text-right {
            text-align: right;
        }

        .invoice-table td.text-center {
            text-align: center;
        }

        .invoice-table tfoot tr:first-child td {
            padding-top: 15px;
            border-top: 2px solid #d1d5db;
        }

        .invoice-table tfoot tr:last-child td {
            padding-bottom: 15px;
        }

        .total-row {
            background-color: #f3f4f6;
            font-weight: bold;
        }

        /* Print-specific styles */
        @media print {
            body {
                padding: 0;
                background: none;
            }

            .container {
                box-shadow: none;
                border-radius: 0;
                margin: 0;
                width: 100%;
            }
        }
    </style>
</head>

<body>
<div class="container">
    <!-- Invoice Header -->
    <div class="invoice-header">
        <table class="header-table">
            <tr>
                <td>
                    <h1 class="invoice-title">INVOICE</h1>
                    <p class="invoice-number">#{{ $invoice->invoice_number }}</p>
                </td>
                <td style="text-align: right;">
                    <h2 class="company-name">{{ settings('company_name') }}</h2>
                    <p class="company-country">{{ $country }}</p>
                </td>
            </tr>
        </table>
    </div>

    <!-- Client Info -->
    <div class="client-section">
        <table class="client-table">
            <tr>
                <td width="50%">
                    <div class="bill-to">
                        <h3>Bill To:</h3>
                        <p>{{ $invoice->customer->full_name }}</p>
                        <p>
                            {{ isset($invoice->customer->street) ? "{$invoice->customer->street}, " : "" }}
                            {{ isset($invoice->customer->city) ? "{$invoice->customer->city}, " : "" }}
                            {{ isset($invoice->customer->state) ? "{$invoice->customer->state}, " : "" }}
                            {{ $invoice->customer?->country?->name ?? "-" }}
                        </p>
                    </div>
                </td>
                <td width="50%">
                    <div class="invoice-details">
                        <h3>Invoice Details:</h3>
                        <p><strong>Date:</strong> {{ $invoice->created_at->format('M d, Y') }}</p>
                        <p><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}</p>
                    </div>
                </td>
            </tr>
        </table>
    </div>


    <div class="custom-fields-section">
        <table class="custom-fields-grid">
            @foreach($extraFields as $index => $field)
                @if($index % 2 == 0)
                    <tr>
                        @endif

                        <td class="{{ $index % 2 == 1 ? 'right-align' : '' }}">
                            <p>
                                <span class="field-label">{{ $field['label'] }}:</span>
                                <span class="field-value">{{ $field['value'] ?? '-' }}</span>
                            </p>
                        </td>

                        @if($index % 2 == 1 || $loop->last)
                    </tr>
                @endif
            @endforeach
        </table>
    </div>

    <!-- Invoice Items -->
    <div class="items-section">
        <table class="invoice-table">
            <thead>
            <tr>
                <th class="text-left">Description</th>
                <th class="text-center">Quantity</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Amount</th>
            </tr>
            </thead>
            <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">
                        <x-money amount="{{ $item->unit_price * 100 }}" currency="{{ settings('currency') }}" />
                    </td>
                    <td class="text-right">
                        <x-money amount="{{ $item->total * 100 }}" currency="{{ settings('currency') }}" />
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            <tr>
                <td colspan="3" class="text-right">Subtotal:</td>
                <td class="text-right">
                    <x-money amount="{{ $invoice->taxable_amount * 100 }}" currency="{{ settings('currency') }}" />
                </td>
            </tr>
            <tr>
                <td colspan="3" class="text-right">{{ settings('tax_label') ?? 'TAX' }}:</td>
                <td class="text-right">
                    <x-money amount="{{ $invoice->total_tax * 100 }}" currency="{{ settings('currency') }}" />
                </td>
            </tr>
            <tr class="total-row">
                <td colspan="3" class="text-right">Total:</td>
                <td class="text-right">
                    <x-money amount="{{ $invoice->total * 100 }}" currency="{{ settings('currency') }}" />
                </td>
            </tr>
            </tfoot>
        </table>
    </div>
</div>
</body>

</html>
