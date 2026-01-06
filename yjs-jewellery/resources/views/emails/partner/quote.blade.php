@extends('emails.layouts.base')

@section('content')
<h1 style="color: #1a1a2e; margin-bottom: 20px;">Quote Ready for Review</h1>

<p>Dear Partner,</p>

<p>We have prepared a quote for your inquiry. Please review the details below:</p>

<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <p style="margin: 5px 0;"><strong>Inquiry Code:</strong> {{ $inquiry->inquiry_code }}</p>
    <p style="margin: 5px 0;"><strong>Quoted Total:</strong> ₹{{ number_format($inquiry->quoted_total ?? 0, 2) }}</p>
    <p style="margin: 5px 0;"><strong>Valid Until:</strong> {{ $inquiry->quote_valid_until ? \Carbon\Carbon::parse($inquiry->quote_valid_until)->format('d M Y') : 'N/A' }}</p>
</div>

@if($inquiry->items && $inquiry->items->count() > 0)
<h3 style="color: #1a1a2e; margin-top: 25px;">Quote Details</h3>
<table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
    <thead>
        <tr style="background: #1a1a2e; color: #ffffff;">
            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Item</th>
            <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Qty</th>
            <th style="padding: 12px; text-align: right; border: 1px solid #ddd;">Unit Price</th>
            <th style="padding: 12px; text-align: right; border: 1px solid #ddd;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($inquiry->items as $item)
        <tr>
            <td style="padding: 12px; border: 1px solid #ddd;">{{ $item->product->name ?? $item->product_name ?? 'Product' }}</td>
            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">{{ $item->quantity }}</td>
            <td style="padding: 12px; text-align: right; border: 1px solid #ddd;">₹{{ number_format($item->quoted_price ?? 0, 2) }}</td>
            <td style="padding: 12px; text-align: right; border: 1px solid #ddd;">₹{{ number_format(($item->quoted_price ?? 0) * $item->quantity, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr style="background: #f8f9fa; font-weight: bold;">
            <td colspan="3" style="padding: 12px; text-align: right; border: 1px solid #ddd;">Grand Total:</td>
            <td style="padding: 12px; text-align: right; border: 1px solid #ddd;">₹{{ number_format($inquiry->quoted_total ?? 0, 2) }}</td>
        </tr>
    </tfoot>
</table>
@endif

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ url('/partner/inquiries/' . $inquiry->id) }}" style="background: linear-gradient(135deg, #b8860b 0%, #daa520 100%); color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">Review & Accept Quote</a>
</div>

<p style="color: #666; font-size: 14px;">Please note that this quote is valid until {{ $inquiry->quote_valid_until ? \Carbon\Carbon::parse($inquiry->quote_valid_until)->format('d M Y') : 'the specified date' }}. After this date, prices may be subject to change.</p>

<p>If you have any questions, please don't hesitate to contact our B2B team.</p>
@endsection
