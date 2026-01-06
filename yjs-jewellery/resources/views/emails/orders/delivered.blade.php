@extends('emails.layouts.base')

@section('title', 'Order Delivered')
@section('header', 'Your Order Has Been Delivered!')

@section('content')
    <h2>Your jewellery has arrived!</h2>

    <p>Dear {{ $order->customer->first_name ?? 'Valued Customer' }},</p>

    <p>Great news! Your order <strong>#{{ $order->order_code ?? $order->custom_order_code }}</strong> has been successfully delivered.</p>

    <div class="highlight-box">
        <strong>Delivered On:</strong> {{ now()->format('d M Y, h:i A') }}<br>
        <strong>Delivered To:</strong> {{ $order->shippingAddress->city ?? 'Your address' }}
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
                <td>{{ $item->product->name ?? 'Product' }}</td>
                <td style="text-align: center;">{{ $item->quantity }}</td>
                <td style="text-align: right;">{{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ config('app.url') }}/orders/{{ $order->id }}/review" class="btn">Leave a Review</a>
    </div>

    <h3 style="color: #333; margin-top: 30px;">We'd Love Your Feedback!</h3>

    <p>Your opinion matters to us. Please take a moment to review your purchase and share your experience with other customers.</p>

    <h3 style="color: #333; margin-top: 30px;">Need Help?</h3>

    <p>If there are any issues with your order or if the items don't meet your expectations, you can:</p>
    <ul>
        <li>Initiate a return within 7 days</li>
        <li>Request an exchange</li>
        <li>Contact our customer support</li>
    </ul>

    <p style="margin-top: 30px;">
        Thank you for choosing YJS Jewellers. We hope you enjoy your beautiful new jewellery!
    </p>

    <p>Best regards,<br><strong>The YJS Jewellers Team</strong></p>
@endsection
