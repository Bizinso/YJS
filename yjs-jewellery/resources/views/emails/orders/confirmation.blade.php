@extends('emails.layouts.base')

@section('title', 'Order Confirmation')
@section('header', 'Order Confirmed!')

@section('content')
    <h2>Thank you for your order!</h2>

    <p>Dear {{ $order->customer->first_name ?? 'Valued Customer' }},</p>

    <p>We're excited to confirm that your order has been successfully placed. Here are the details:</p>

    <div class="highlight-box">
        <strong>Order Number:</strong> #{{ $order->order_code ?? $order->custom_order_code }}<br>
        <strong>Order Date:</strong> {{ $order->created_at->format('d M Y, h:i A') }}
    </div>

    <h3 style="color: #333; margin-top: 30px;">Order Summary</h3>

    <table class="order-table">
        <thead>
            <tr>
                <th>Item</th>
                <th style="text-align: center;">Qty</th>
                <th style="text-align: right;">Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>
                    <strong>{{ $item->product->name ?? 'Product' }}</strong><br>
                    <small class="text-muted">SKU: {{ $item->product->sku ?? '-' }}</small>
                </td>
                <td style="text-align: center;">{{ $item->quantity }}</td>
                <td style="text-align: right;">{{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px;">
        <div class="info-row">
            <span class="info-label">Subtotal</span>
            <span class="info-value">{{ number_format($order->subtotal, 2) }}</span>
        </div>
        @if($order->discount_amount > 0)
        <div class="info-row">
            <span class="info-label">Discount</span>
            <span class="info-value text-success">-{{ number_format($order->discount_amount, 2) }}</span>
        </div>
        @endif
        @if($order->tax_amount > 0)
        <div class="info-row">
            <span class="info-label">Tax</span>
            <span class="info-value">{{ number_format($order->tax_amount, 2) }}</span>
        </div>
        @endif
        @if($order->shipping_amount > 0)
        <div class="info-row">
            <span class="info-label">Shipping</span>
            <span class="info-value">{{ number_format($order->shipping_amount, 2) }}</span>
        </div>
        @endif
        <div class="info-row" style="border-bottom: none; padding-top: 15px;">
            <span style="font-size: 18px; font-weight: bold;">Total</span>
            <span style="font-size: 18px; font-weight: bold; color: #B44536;">{{ number_format($order->order_total, 2) }}</span>
        </div>
    </div>

    @if($order->shippingAddress)
    <h3 style="color: #333; margin-top: 30px;">Shipping Address</h3>
    <p>
        {{ $order->shippingAddress->full_name }}<br>
        {{ $order->shippingAddress->address_line_1 }}<br>
        @if($order->shippingAddress->address_line_2)
        {{ $order->shippingAddress->address_line_2 }}<br>
        @endif
        {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }} {{ $order->shippingAddress->postal_code }}<br>
        Phone: {{ $order->shippingAddress->phone }}
    </p>
    @endif

    <div style="text-align: center; margin-top: 30px;">
        <a href="{{ config('app.url') }}/orders/{{ $order->id }}" class="btn">Track Your Order</a>
    </div>

    <p style="margin-top: 30px;">
        If you have any questions about your order, please don't hesitate to contact our customer support team.
    </p>

    <p>Thank you for shopping with YJS Jewellers!</p>
@endsection
