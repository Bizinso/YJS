@extends('emails.layouts.base')

@section('content')
<h1 style="color: #1a1a2e; margin-bottom: 20px;">Payment Received</h1>

<p>Dear {{ $order->customer->name ?? 'Valued Customer' }},</p>

<p>We have successfully received your payment. Thank you for your purchase!</p>

<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <p style="margin: 5px 0;"><strong>Order Number:</strong> {{ $order->custom_order_code }}</p>
    <p style="margin: 5px 0;"><strong>Amount Paid:</strong> ₹{{ number_format($amount, 2) }}</p>
    <p style="margin: 5px 0;"><strong>Payment Date:</strong> {{ now()->format('d M Y, h:i A') }}</p>
    <p style="margin: 5px 0;"><strong>Payment Method:</strong> {{ ucfirst($order->payment_method ?? 'Online') }}</p>
</div>

<p>Your order is now being processed and will be shipped soon. You will receive a shipping confirmation email with tracking details once your order is dispatched.</p>

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ url('/orders/' . $order->id) }}" style="background: linear-gradient(135deg, #b8860b 0%, #daa520 100%); color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">View Order Details</a>
</div>

<p>Thank you for shopping with YJS Jewellers!</p>
@endsection
