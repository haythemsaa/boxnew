<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }
        .header-left, .header-right {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }
        .header-right {
            text-align: right;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 10px;
        }
        .invoice-meta {
            margin-top: 15px;
        }
        .invoice-meta div {
            margin-bottom: 5px;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        .addresses {
            display: table;
            width: 100%;
            margin: 40px 0;
        }
        .bill-to, .ship-to {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #4b5563;
            margin-bottom: 10px;
            text-transform: uppercase;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 5px;
        }
        .address-content {
            padding: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }
        table thead {
            background-color: #2563eb;
            color: white;
        }
        table thead th {
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
        }
        table tbody td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        table tbody tr:hover {
            background-color: #f9fafb;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals {
            float: right;
            width: 300px;
            margin-top: 20px;
        }
        .totals table {
            margin: 0;
        }
        .totals tbody td {
            padding: 8px;
        }
        .totals .subtotal {
            font-weight: bold;
        }
        .totals .total {
            font-size: 16px;
            font-weight: bold;
            background-color: #2563eb;
            color: white;
        }
        .notes {
            clear: both;
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .notes-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .footer {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        .status-draft {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .status-sent {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-paid {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-overdue {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="company-name">{{ $tenant->name ?? 'Boxibox Storage' }}</div>
                <div>{{ $tenant->address ?? '' }}</div>
                <div>{{ $tenant->city ?? '' }}, {{ $tenant->postal_code ?? '' }}</div>
                <div>{{ $tenant->country ?? '' }}</div>
                @if($tenant->phone ?? null)
                <div>Phone: {{ $tenant->phone }}</div>
                @endif
                @if($tenant->email ?? null)
                <div>Email: {{ $tenant->email }}</div>
                @endif
            </div>
            <div class="header-right">
                <div class="invoice-title">{{ $invoice->type === 'credit_note' ? 'CREDIT NOTE' : ($invoice->type === 'proforma' ? 'PROFORMA INVOICE' : 'INVOICE') }}</div>
                <div class="invoice-meta">
                    <div><span class="label">Invoice #:</span> {{ $invoice->invoice_number }}</div>
                    <div><span class="label">Date:</span> {{ $invoice->invoice_date->format('F j, Y') }}</div>
                    <div><span class="label">Due Date:</span> {{ $invoice->due_date->format('F j, Y') }}</div>
                    <div><span class="label">Status:</span> <span class="status-badge status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span></div>
                </div>
            </div>
        </div>

        <!-- Customer Information -->
        <div class="addresses">
            <div class="bill-to">
                <div class="section-title">Bill To</div>
                <div class="address-content">
                    <strong>
                        @if($invoice->customer->type === 'company')
                            {{ $invoice->customer->company_name }}
                        @else
                            {{ $invoice->customer->first_name }} {{ $invoice->customer->last_name }}
                        @endif
                    </strong><br>
                    {{ $invoice->customer->address }}<br>
                    {{ $invoice->customer->city }}, {{ $invoice->customer->postal_code }}<br>
                    {{ $invoice->customer->country }}<br>
                    @if($invoice->customer->email)
                    Email: {{ $invoice->customer->email }}<br>
                    @endif
                    @if($invoice->customer->phone)
                    Phone: {{ $invoice->customer->phone }}
                    @endif
                </div>
            </div>
            @if($invoice->contract)
            <div class="ship-to">
                <div class="section-title">Contract Details</div>
                <div class="address-content">
                    <strong>{{ $invoice->contract->contract_number }}</strong><br>
                    Box: {{ $invoice->contract->box->name }}<br>
                    Site: {{ $invoice->contract->site->name }}<br>
                    Period: {{ $invoice->period_start?->format('M j, Y') }} - {{ $invoice->period_end?->format('M j, Y') }}
                </div>
            </div>
            @endif
        </div>

        <!-- Line Items -->
        <table>
            <thead>
                <tr>
                    <th style="width: 50%">Description</th>
                    <th class="text-center" style="width: 15%">Quantity</th>
                    <th class="text-right" style="width: 15%">Unit Price</th>
                    <th class="text-right" style="width: 20%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item['description'] }}</td>
                    <td class="text-center">{{ $item['quantity'] }}</td>
                    <td class="text-right">€{{ number_format($item['unit_price'], 2) }}</td>
                    <td class="text-right">€{{ number_format($item['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <table>
                <tbody>
                    <tr class="subtotal">
                        <td>Subtotal</td>
                        <td class="text-right">€{{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    @if($invoice->discount_amount > 0)
                    <tr>
                        <td>Discount</td>
                        <td class="text-right">-€{{ number_format($invoice->discount_amount, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td>Tax ({{ $invoice->tax_rate }}%)</td>
                        <td class="text-right">€{{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                    <tr class="total">
                        <td><strong>TOTAL</strong></td>
                        <td class="text-right"><strong>€{{ number_format($invoice->total, 2) }}</strong></td>
                    </tr>
                    @if($invoice->paid_amount > 0)
                    <tr>
                        <td>Paid</td>
                        <td class="text-right">€{{ number_format($invoice->paid_amount, 2) }}</td>
                    </tr>
                    <tr class="total">
                        <td><strong>BALANCE DUE</strong></td>
                        <td class="text-right"><strong>€{{ number_format($invoice->total - $invoice->paid_amount, 2) }}</strong></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Notes -->
        @if($invoice->notes)
        <div class="notes">
            <div class="notes-title">Notes:</div>
            <div>{{ $invoice->notes }}</div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>This is a computer-generated document. No signature is required.</p>
        </div>
    </div>
</body>
</html>
