<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract {{ $contract->contract_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #2563eb;
        }
        .company-name {
            font-size: 26px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .contract-title {
            font-size: 28px;
            font-weight: bold;
            color: #1e40af;
            margin: 20px 0 10px 0;
        }
        .contract-number {
            font-size: 14px;
            color: #6b7280;
        }
        .section {
            margin: 25px 0;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 12px;
            padding-bottom: 5px;
            border-bottom: 2px solid #2563eb;
            text-transform: uppercase;
        }
        .details-grid {
            display: table;
            width: 100%;
            margin: 10px 0;
        }
        .detail-row {
            display: table-row;
        }
        .detail-label {
            display: table-cell;
            padding: 8px 10px 8px 0;
            font-weight: bold;
            width: 180px;
            vertical-align: top;
        }
        .detail-value {
            display: table-cell;
            padding: 8px 0;
            vertical-align: top;
        }
        .party-box {
            background-color: #f3f4f6;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #2563eb;
        }
        .party-title {
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 8px;
            font-size: 13px;
        }
        .terms-list {
            list-style-type: decimal;
            margin-left: 20px;
            margin-top: 10px;
        }
        .terms-list li {
            margin-bottom: 15px;
            line-height: 1.6;
        }
        .terms-list strong {
            color: #1e40af;
        }
        .price-box {
            background-color: #dbeafe;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
            border-radius: 8px;
        }
        .price-label {
            font-size: 12px;
            color: #1e40af;
            text-transform: uppercase;
            font-weight: bold;
        }
        .price-amount {
            font-size: 32px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 10px 0;
        }
        .price-period {
            font-size: 11px;
            color: #6b7280;
        }
        .signatures {
            display: table;
            width: 100%;
            margin-top: 50px;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            padding: 0 10px;
        }
        .signature-line {
            border-top: 2px solid #000;
            margin-top: 60px;
            padding-top: 10px;
            text-align: center;
        }
        .signature-label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 9px;
            color: #6b7280;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        .status-draft {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .status-active {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-expired {
            background-color: #f3f4f6;
            color: #6b7280;
        }
        .status-terminated {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .amenities-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .amenity {
            background-color: #e0e7ff;
            color: #3730a3;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 10px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">{{ $tenant->name ?? 'Boxibox Storage' }}</div>
            <div>{{ $tenant->address ?? '' }} • {{ $tenant->city ?? '' }} • {{ $tenant->postal_code ?? '' }}</div>
            @if($tenant->phone ?? null)
            <div>Phone: {{ $tenant->phone }} • Email: {{ $tenant->email ?? '' }}</div>
            @endif
            <div class="contract-title">STORAGE RENTAL CONTRACT</div>
            <div class="contract-number">Contract Number: <strong>{{ $contract->contract_number }}</strong></div>
            <div style="margin-top: 10px;">
                Status: <span class="status-badge status-{{ $contract->status }}">{{ ucfirst($contract->status) }}</span>
            </div>
        </div>

        <!-- Parties -->
        <div class="section">
            <div class="section-title">Parties to the Agreement</div>

            <div class="party-box">
                <div class="party-title">PROVIDER (Lessor)</div>
                <div>{{ $tenant->name ?? 'Boxibox Storage' }}</div>
                <div>{{ $tenant->address ?? '' }}, {{ $tenant->city ?? '' }} {{ $tenant->postal_code ?? '' }}</div>
                @if($tenant->siret ?? null)
                <div>SIRET: {{ $tenant->siret }}</div>
                @endif
            </div>

            <div class="party-box">
                <div class="party-title">CUSTOMER (Lessee)</div>
                <div>
                    <strong>
                        @if($contract->customer->type === 'company')
                            {{ $contract->customer->company_name }}
                        @else
                            {{ $contract->customer->civility ?? '' }} {{ $contract->customer->first_name }} {{ $contract->customer->last_name }}
                        @endif
                    </strong>
                </div>
                <div>{{ $contract->customer->address }}, {{ $contract->customer->city }} {{ $contract->customer->postal_code }}</div>
                <div>Phone: {{ $contract->customer->phone }} • Email: {{ $contract->customer->email }}</div>
                @if($contract->customer->type === 'company' && $contract->customer->siret)
                <div>SIRET: {{ $contract->customer->siret }}</div>
                @endif
            </div>
        </div>

        <!-- Storage Unit Details -->
        <div class="section">
            <div class="section-title">Storage Unit Details</div>
            <div class="details-grid">
                <div class="detail-row">
                    <div class="detail-label">Storage Site:</div>
                    <div class="detail-value"><strong>{{ $contract->site->name }}</strong></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Site Address:</div>
                    <div class="detail-value">{{ $contract->site->address }}, {{ $contract->site->city }} {{ $contract->site->postal_code }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Storage Box:</div>
                    <div class="detail-value"><strong>{{ $contract->box->name }}</strong> ({{ $contract->box->code }})</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Dimensions:</div>
                    <div class="detail-value">{{ $contract->box->length }} x {{ $contract->box->width }} x {{ $contract->box->height }} m ({{ $contract->box->volume }} m³)</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Floor:</div>
                    <div class="detail-value">{{ $contract->box->floor }}</div>
                </div>
                @if($contract->box->description)
                <div class="detail-row">
                    <div class="detail-label">Description:</div>
                    <div class="detail-value">{{ $contract->box->description }}</div>
                </div>
                @endif
            </div>

            @if($contract->box->amenities && count($contract->box->amenities) > 0)
            <div style="margin-top: 15px;">
                <strong>Amenities:</strong>
                <div class="amenities-list">
                    @foreach($contract->box->amenities as $amenity)
                    <span class="amenity">{{ ucfirst(str_replace('_', ' ', $amenity)) }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Contract Terms -->
        <div class="section">
            <div class="section-title">Contract Terms & Duration</div>
            <div class="details-grid">
                <div class="detail-row">
                    <div class="detail-label">Contract Type:</div>
                    <div class="detail-value"><strong>{{ ucfirst(str_replace('_', ' ', $contract->type ?? 'Standard')) }}</strong></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Start Date:</div>
                    <div class="detail-value">{{ $contract->start_date->format('F j, Y') }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">End Date:</div>
                    <div class="detail-value">{{ $contract->end_date->format('F j, Y') }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Duration:</div>
                    <div class="detail-value">{{ $contract->start_date->diffInMonths($contract->end_date) }} months</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Auto-Renewal:</div>
                    <div class="detail-value">{{ $contract->auto_renew ? 'Yes' : 'No' }}
                        @if($contract->auto_renew && $contract->renewal_period)
                            ({{ $contract->renewal_period }} months)
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing -->
        <div class="section">
            <div class="section-title">Pricing & Payment Terms</div>

            <div class="price-box">
                <div class="price-label">Monthly Rental Fee</div>
                <div class="price-amount">€{{ number_format($contract->monthly_price, 2) }}</div>
                <div class="price-period">Billing Frequency: {{ ucfirst(str_replace('_', ' ', $contract->billing_frequency ?? 'monthly')) }}</div>
            </div>

            <div class="details-grid">
                <div class="detail-row">
                    <div class="detail-label">Deposit Amount:</div>
                    <div class="detail-value">€{{ number_format($contract->deposit_amount ?? 0, 2) }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Billing Day:</div>
                    <div class="detail-value">{{ $contract->billing_day ?? 1 }} of each month</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Payment Method:</div>
                    <div class="detail-value">{{ ucfirst($contract->payment_method ?? 'Bank Transfer') }}</div>
                </div>
            </div>
        </div>

        <!-- Access & Keys -->
        @if($contract->key_given || $contract->access_code || $contract->access_instructions)
        <div class="section">
            <div class="section-title">Access Information</div>
            <div class="details-grid">
                @if($contract->access_code)
                <div class="detail-row">
                    <div class="detail-label">Access Code:</div>
                    <div class="detail-value"><strong>{{ $contract->access_code }}</strong></div>
                </div>
                @endif
                @if($contract->key_given)
                <div class="detail-row">
                    <div class="detail-label">Key Given:</div>
                    <div class="detail-value">Yes - {{ $contract->key_given_date ? $contract->key_given_date->format('F j, Y') : 'N/A' }}</div>
                </div>
                @endif
                @if($contract->access_instructions)
                <div class="detail-row">
                    <div class="detail-label">Access Instructions:</div>
                    <div class="detail-value">{{ $contract->access_instructions }}</div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Terms and Conditions -->
        <div class="section">
            <div class="section-title">Terms and Conditions</div>
            <ol class="terms-list">
                <li><strong>Payment:</strong> The Customer agrees to pay the monthly rental fee by the {{ $contract->billing_day ?? 1 }}th of each month.</li>
                <li><strong>Access:</strong> The Customer shall have 24/7 access to the storage unit during the contract period.</li>
                <li><strong>Prohibited Items:</strong> Hazardous materials, perishables, living things, and illegal items are strictly prohibited.</li>
                <li><strong>Insurance:</strong> The Customer is responsible for insuring their stored items. The Provider is not liable for loss or damage.</li>
                <li><strong>Termination:</strong> Either party may terminate this agreement with 30 days written notice.</li>
                <li><strong>Late Payments:</strong> Late payments may result in access suspension and additional fees.</li>
                <li><strong>Maintenance:</strong> The Customer must maintain the unit in good condition and report any damage immediately.</li>
            </ol>
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line">
                    <div class="signature-label">Customer Signature</div>
                    @if($contract->signed_by_customer)
                    <div>Signed on: {{ $contract->signed_at ? $contract->signed_at->format('F j, Y') : 'N/A' }}</div>
                    @else
                    <div style="color: #dc2626;">Pending signature</div>
                    @endif
                </div>
            </div>
            <div class="signature-box">
                <div class="signature-line">
                    <div class="signature-label">Provider Representative</div>
                    @if($contract->signed_by_staff)
                    <div>{{ $contract->signed_by_staff }}</div>
                    <div>Signed on: {{ $contract->staff_signed_at ? $contract->staff_signed_at->format('F j, Y') : 'N/A' }}</div>
                    @else
                    <div style="color: #dc2626;">Pending signature</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This contract is governed by the laws of {{ $tenant->country ?? 'France' }}.</p>
            <p>For questions or concerns, please contact us at {{ $tenant->email ?? 'contact@boxibox.com' }} or {{ $tenant->phone ?? '+33 1 23 45 67 89' }}</p>
            <p>Document generated on {{ now()->format('F j, Y \a\t H:i') }}</p>
        </div>
    </div>
</body>
</html>
