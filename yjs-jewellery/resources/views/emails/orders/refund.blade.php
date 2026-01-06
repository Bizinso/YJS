@extends('emails.layouts.base')

@section('title', 'Refund Processed')
@section('header', 'Refund Processed Successfully')

@section('content')
    <h2>Your Refund Has Been Processed</h2>

    <p>Dear {{ $order->customer->first_name ?? 'Valued Customer' }},</p>

    <p>We have successfully processed your refund for order <strong>#{{ $order->order_code ?? $order->custom_order_code }}</strong>.</p>

    <div class="highlight-box">
        <strong>Refund Amount:</strong> {{ number_format($refundAmount ?? $order->order_total, 2) }}<br>
        <strong>Refund Method:</strong> {{ $refundMethod ?? 'Original Payment Method' }}<br>
        <strong>Reference ID:</strong> {{ $refundId ?? 'Will be updated' }}
    </div>

    <h3 style="color: #333; margin-top: 30px;">Refund Details</h3>

    <div style="padding: 15px; background: #f8f9fa; border-radius: 6px;">
        <div class="info-row">
            <span class="info-label">Order Total</span>
            <span class="info-value">{{ number_format($order->order_total, 2) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Refund Amount</span>
            <span class="info-value text-success">{{ number_format($refundAmount ?? $order->order_total, 2) }}</span>
        </div>
        <div class="info-row" style="border-bottom: none;">
            <span class="info-label">Processing Time</span>
            <span class="info-value">5-7 business days</span>
        </div>
    </div>

    <p style="margin-top: 30px;">
        <strong>Please note:</strong> The refund will be credited to your original payment method. Depending on your bank or payment provider, it may take 5-7 business days to reflect in your account.
    </p>

    <h3 style="color: #333; margin-top: 30px;">Refund Timeline</h3>

    <ul>
        <li><strong>Credit/Debit Cards:</strong> 5-7 business days</li>
        <li><strong>UPI/Net Banking:</strong> 3-5 business days</li>
        <li><strong>Wallet:</strong> 24-48 hours</li>
    </ul>

    <p>If you don't see the refund after the specified time, please contact your bank first. If the issue persists, reach out to our customer support.</p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ config('app.url') }}/support" class="btn">Contact Support</a>
    </div>

    <p>We apologize for any inconvenience caused and hope to serve you better in the future.</p>

    <p>Best regards,<br><strong>The YJS Jewellers Team</strong></p>
@endsection
