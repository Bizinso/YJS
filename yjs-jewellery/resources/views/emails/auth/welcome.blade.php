@extends('emails.layouts.base')

@section('title', 'Welcome to YJS Jewellers')
@section('header', 'Welcome to YJS Jewellers!')

@section('content')
    <h2>Hello {{ $user->first_name ?? 'there' }}!</h2>

    <p>Welcome to YJS Jewellers! We're thrilled to have you join our family of jewellery enthusiasts.</p>

    <p>Your account has been successfully created and you're all set to explore our exquisite collection of fine jewellery.</p>

    <div class="highlight-box">
        <strong>Your Account Details:</strong><br>
        Email: {{ $user->email }}<br>
        Member Since: {{ $user->created_at->format('d M Y') }}
    </div>

    <h3 style="color: #333; margin-top: 30px;">What's Next?</h3>

    <ul>
        <li><strong>Complete Your Profile</strong> - Add your details for a personalized experience</li>
        <li><strong>Browse Our Collection</strong> - Explore rings, necklaces, earrings, and more</li>
        <li><strong>Save Your Favorites</strong> - Add items to your wishlist for later</li>
        <li><strong>Join Our Loyalty Program</strong> - Earn points with every purchase</li>
    </ul>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ config('app.url') }}/shop" class="btn">Start Shopping</a>
    </div>

    <h3 style="color: #333; margin-top: 30px;">Exclusive Benefits</h3>

    <p>As a registered member, you enjoy:</p>
    <ul>
        <li>Early access to new collections</li>
        <li>Exclusive member-only discounts</li>
        <li>Order tracking and history</li>
        <li>Wishlist and saved items</li>
        <li>Loyalty points on every purchase</li>
    </ul>

    <p style="margin-top: 30px;">
        If you have any questions or need assistance, our customer support team is always here to help.
    </p>

    <p>Happy Shopping!</p>
    <p><strong>The YJS Jewellers Team</strong></p>
@endsection
