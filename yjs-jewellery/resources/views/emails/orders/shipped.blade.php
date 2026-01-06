@extends('emails.layouts.base')

@section('title', 'Order Shipped')
@section('header', 'Your Order is On Its Way!')

@section('content')
    <h2>Great news! Your order has shipped.</h2>

    <p>Dear {{ $order->customer->first_name ?? 'Valued Customer' }},</p>

    <p>Your order <strong>#{{ $order->order_code ?? $order->custom_order_code }}</strong> has been shipped and is on its way to you!</p>

    <div class="highlight-box">
        <strong>Tracking Number:</strong> {{ $trackingNumber ?? 'Will be updated shortly' }}<br>
        <strong>Carrier:</strong> {{ $carrier ?? 'Shiprocket' }}<br>
        <strong>Estimated Delivery:</strong> {{ $estimatedDelivery ?? '3-5 business days' }}
    </div>

    @if($trackingUrl ?? false)
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $trackingUrl }}" class="btn">Track Your Package</a>
    </div>
    @endif

    <h3 style="color: #333; margin-top: 30px;">Shipping Details</h3>

    @if($order->shippingAddress)
    <p>
        <strong>Delivering to:</strong><br>
        {{ $order->shippingAddress->full_name }}<br>
        {{ $order->shippingAddress->address_line_1 }}<br>
        @if($order->shippingAddress->address_line_2)
        {{ $order->shippingAddress->address_line_2 }}<br>
        @endif
        {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }} {{ $order->shippingAddress->postal_code }}
    </p>
    @endif

    <h3 style="color: #333; margin-top: 30px;">Order Summary</h3>

    <table class="order-table">
        <thead>
            <tr>
                <th>Item</th>
                <th style="text-align: center;">Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product->name ?? 'Product' }}</td>
                <td style="text-align: center;">{{ $item->quantity }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 30px;">
        <strong>Tips for delivery:</strong>
    </p>
    <ul>
        <li>Ensure someone is available to receive the package</li>
        <li>Keep your phone accessible for delivery updates</li>
        <li>Check the package for any damage before signing</li>
    </ul>

    <p>If you have any questions, please contact our customer support.</p>

    <p>Thank you for shopping with YJS Jewellers!</p>
@endsection
