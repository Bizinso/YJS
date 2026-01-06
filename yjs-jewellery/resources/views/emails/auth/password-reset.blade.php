@extends('emails.layouts.base')

@section('title', 'Reset Your Password')
@section('header', 'Password Reset Request')

@section('content')
    <h2>Reset Your Password</h2>

    <p>Dear {{ $user->first_name ?? 'User' }},</p>

    <p>We received a request to reset the password for your YJS Jewellers account. Click the button below to set a new password:</p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $resetUrl }}" class="btn">Reset Password</a>
    </div>

    <div class="highlight-box">
        <strong>Important:</strong> This password reset link will expire in {{ $expireMinutes ?? 60 }} minutes.
    </div>

    <p>If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.</p>

    <p style="margin-top: 30px;">
        <strong>Security Tips:</strong>
    </p>
    <ul>
        <li>Never share your password with anyone</li>
        <li>Use a strong, unique password</li>
        <li>Enable two-factor authentication if available</li>
    </ul>

    <p style="margin-top: 30px;">
        If you're having trouble clicking the button, copy and paste the URL below into your web browser:
    </p>
    <p style="word-break: break-all; font-size: 12px; color: #666;">
        {{ $resetUrl }}
    </p>

    <p>If you did not request this reset or believe your account has been compromised, please contact our support team immediately.</p>

    <p>Best regards,<br><strong>The YJS Jewellers Team</strong></p>
@endsection
