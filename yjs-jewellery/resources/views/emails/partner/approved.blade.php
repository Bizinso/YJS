@extends('emails.layouts.base')

@section('title', 'Partner Application Approved')
@section('header', 'Congratulations! You\'re Approved!')

@section('content')
    <h2>Welcome to YJS Jewellers B2B Partner Program!</h2>

    <p>Dear {{ $partner->business_name ?? 'Partner' }},</p>

    <p>We are pleased to inform you that your application to become a YJS Jewellers B2B Partner has been <strong style="color: #28a745;">approved</strong>!</p>

    <div class="highlight-box">
        <strong>Partner Details:</strong><br>
        Business Name: {{ $partner->business_name }}<br>
        Partner ID: {{ $partner->partner_code ?? $partner->id }}<br>
        Approved On: {{ now()->format('d M Y') }}
    </div>

    <h3 style="color: #333; margin-top: 30px;">What You Can Do Now</h3>

    <ul>
        <li><strong>Access B2B Portal</strong> - Login to your dedicated partner dashboard</li>
        <li><strong>Browse Wholesale Catalog</strong> - View our complete product range with B2B pricing</li>
        <li><strong>Create Inquiries</strong> - Submit bulk order requests</li>
        <li><strong>Download Brochures</strong> - Access product catalogs and marketing materials</li>
        <li><strong>Track Orders</strong> - Monitor your orders and shipments</li>
    </ul>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ config('app.url') }}/partner/login" class="btn">Access Partner Portal</a>
    </div>

    <h3 style="color: #333; margin-top: 30px;">Partner Benefits</h3>

    <ul>
        <li>Exclusive wholesale pricing</li>
        <li>Bulk order discounts</li>
        <li>Dedicated account manager</li>
        <li>Priority support</li>
        <li>Early access to new collections</li>
        <li>Custom order capabilities</li>
    </ul>

    <h3 style="color: #333; margin-top: 30px;">Your Account Manager</h3>

    <p>
        You have been assigned a dedicated account manager who will assist you with all your needs:<br>
        <strong>Contact:</strong> partners@yjsjewellers.com<br>
        <strong>Phone:</strong> +91 XXXXX XXXXX
    </p>

    <p style="margin-top: 30px;">
        We're excited to have you as our partner and look forward to a successful business relationship!
    </p>

    <p>Best regards,<br><strong>The YJS Jewellers B2B Team</strong></p>
@endsection
