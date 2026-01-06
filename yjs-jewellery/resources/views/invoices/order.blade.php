<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #c9a227;
            padding-bottom: 15px;
        }
        .header-left, .header-right {
            display: table-cell;
            vertical-align: top;
        }
        .header-right {
            text-align: right;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #c9a227;
            margin-bottom: 5px;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .invoice-details {
            margin-bottom: 5px;
        }
        .addresses {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .address-box {
            display: table-cell;
            width: 33%;
            padding: 10px;
            vertical-align: top;
        }
        .address-box h4 {
            font-size: 12px;
            font-weight: bold;
            color: #666;
            margin-bottom: 8px;
            text-transform: uppercase;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .address-box p {
            margin-bottom: 3px;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items th {
            background-color: #c9a227;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        table.items td {
            padding: 10px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }
        table.items tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals {
            width: 100%;
            margin-bottom: 20px;
        }
        .totals-table {
            width: 300px;
            float: right;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .totals-table .label {
            text-align: left;
            color: #666;
        }
        .totals-table .value {
            text-align: right;
            font-weight: bold;
        }
        .totals-table .grand-total {
            background-color: #c9a227;
            color: white;
        }
        .totals-table .grand-total td {
            font-size: 14px;
            border: none;
        }
        .amount-words {
            clear: both;
            padding: 15px;
            background-color: #f5f5f5;
            margin-bottom: 20px;
            border-left: 4px solid #c9a227;
        }
        .amount-words strong {
            color: #333;
        }
        .footer {
            margin-top: 30px;
            display: table;
            width: 100%;
        }
        .footer-left, .footer-right {
            display: table-cell;
            vertical-align: top;
        }
        .footer-left {
            width: 60%;
        }
        .footer-right {
            width: 40%;
            text-align: right;
        }
        .terms {
            font-size: 10px;
            color: #666;
        }
        .terms h4 {
            font-size: 11px;
            margin-bottom: 5px;
            color: #333;
        }
        .terms ul {
            list-style: none;
            padding: 0;
        }
        .terms li {
            margin-bottom: 3px;
        }
        .signature {
            margin-top: 40px;
            padding-top: 10px;
            border-top: 1px solid #333;
            width: 150px;
            text-align: center;
            font-size: 10px;
        }
        .notes {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
        }
        .notes h4 {
            font-size: 11px;
            margin-bottom: 5px;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div class="header-left">
                <div class="company-name">{{ $company['name'] }}</div>
                <p>{{ $company['address'] }}</p>
                <p>{{ $company['city'] }}, {{ $company['state'] }} - {{ $company['pincode'] }}</p>
                <p>Phone: {{ $company['phone'] }}</p>
                <p>Email: {{ $company['email'] }}</p>
                <p>GSTIN: {{ $company['gstin'] }}</p>
                <p>PAN: {{ $company['pan'] }}</p>
            </div>
            <div class="header-right">
                <div class="invoice-title">TAX INVOICE</div>
                <p class="invoice-details"><strong>Invoice No:</strong> {{ $invoice_number }}</p>
                <p class="invoice-details"><strong>Invoice Date:</strong> {{ $invoice_date }}</p>
                <p class="invoice-details"><strong>Order No:</strong> {{ $order_number }}</p>
                <p class="invoice-details"><strong>Order Date:</strong> {{ $order_date }}</p>
            </div>
        </div>

        <div class="addresses">
            <div class="address-box">
                <h4>Bill To</h4>
                @if($billing_address)
                    <p><strong>{{ $billing_address['name'] }}</strong></p>
                    <p>{{ $billing_address['address_line_1'] }}</p>
                    @if($billing_address['address_line_2'])
                        <p>{{ $billing_address['address_line_2'] }}</p>
                    @endif
                    <p>{{ $billing_address['city'] }}, {{ $billing_address['state'] }}</p>
                    <p>{{ $billing_address['pincode'] }}, {{ $billing_address['country'] }}</p>
                    @if($billing_address['phone'])
                        <p>Phone: {{ $billing_address['phone'] }}</p>
                    @endif
                @endif
            </div>
            <div class="address-box">
                <h4>Ship To</h4>
                @if($shipping_address)
                    <p><strong>{{ $shipping_address['name'] }}</strong></p>
                    <p>{{ $shipping_address['address_line_1'] }}</p>
                    @if($shipping_address['address_line_2'])
                        <p>{{ $shipping_address['address_line_2'] }}</p>
                    @endif
                    <p>{{ $shipping_address['city'] }}, {{ $shipping_address['state'] }}</p>
                    <p>{{ $shipping_address['pincode'] }}, {{ $shipping_address['country'] }}</p>
                    @if($shipping_address['phone'])
                        <p>Phone: {{ $shipping_address['phone'] }}</p>
                    @endif
                @endif
            </div>
            <div class="address-box">
                <h4>Customer Details</h4>
                <p><strong>{{ $customer['name'] }}</strong></p>
                <p>{{ $customer['email'] }}</p>
                <p>{{ $customer['phone'] }}</p>
                <br>
                <p><strong>Payment:</strong> {{ $payment_method }}</p>
                <p><strong>Status:</strong> {{ $payment_status }}</p>
            </div>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th style="width: 5%">Sr.</th>
                    <th style="width: 30%">Description</th>
                    <th style="width: 10%">HSN</th>
                    <th style="width: 8%" class="text-center">Qty</th>
                    <th style="width: 12%" class="text-right">Unit Price</th>
                    <th style="width: 10%" class="text-right">Taxable</th>
                    <th style="width: 10%" class="text-center">GST %</th>
                    <th style="width: 15%" class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item['sr_no'] }}</td>
                    <td>
                        {{ $item['name'] }}
                        <br><small style="color: #666">SKU: {{ $item['sku'] }}</small>
                    </td>
                    <td>{{ $item['hsn_code'] }}</td>
                    <td class="text-center">{{ $item['quantity'] }}</td>
                    <td class="text-right">{{ number_format($item['unit_price'], 2) }}</td>
                    <td class="text-right">{{ number_format($item['taxable_value'], 2) }}</td>
                    <td class="text-center">{{ $item['gst_rate'] }}%</td>
                    <td class="text-right">{{ number_format($item['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals clearfix">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="value">{{ number_format($subtotal, 2) }}</td>
                </tr>
                @if($discount > 0)
                <tr>
                    <td class="label">Discount @if($discount_description)<br><small>({{ $discount_description }})</small>@endif</td>
                    <td class="value">-{{ number_format($discount, 2) }}</td>
                </tr>
                @endif
                @foreach($taxes as $tax)
                <tr>
                    <td class="label">{{ $tax['name'] }} ({{ $tax['rate'] }}%)</td>
                    <td class="value">{{ number_format($tax['amount'], 2) }}</td>
                </tr>
                @endforeach
                @if($shipping_charges > 0)
                <tr>
                    <td class="label">Shipping</td>
                    <td class="value">{{ number_format($shipping_charges, 2) }}</td>
                </tr>
                @endif
                <tr class="grand-total">
                    <td class="label">Grand Total</td>
                    <td class="value">{{ number_format($grand_total, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="amount-words">
            <strong>Amount in Words:</strong> {{ $amount_in_words }}
        </div>

        @if(count($notes) > 0)
        <div class="notes">
            <h4>Notes:</h4>
            @foreach($notes as $note)
                <p>{{ $note }}</p>
            @endforeach
        </div>
        @endif

        <div class="footer">
            <div class="footer-left">
                <div class="terms">
                    <h4>Terms & Conditions:</h4>
                    <ul>
                        @foreach($terms as $term)
                            <li>{{ $term }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="footer-right">
                <p>For {{ $company['name'] }}</p>
                <div class="signature">
                    Authorized Signatory
                </div>
            </div>
        </div>
    </div>
</body>
</html>
