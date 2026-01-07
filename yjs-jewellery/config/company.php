<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Company Information
    |--------------------------------------------------------------------------
    |
    | These values are used for invoices, emails, and other business documents.
    | Set these in your .env file for production.
    |
    */

    'name' => env('COMPANY_NAME', 'YJS Jewellers'),
    'address' => env('COMPANY_ADDRESS', 'Shop No. 123, Gold Market'),
    'city' => env('COMPANY_CITY', 'Mumbai'),
    'state' => env('COMPANY_STATE', 'Maharashtra'),
    'pincode' => env('COMPANY_PINCODE', '400001'),
    'country' => env('COMPANY_COUNTRY', 'India'),
    'phone' => env('COMPANY_PHONE', '+91 9876543210'),
    'email' => env('COMPANY_EMAIL', 'support@yjsjewellers.com'),
    'website' => env('COMPANY_WEBSITE', 'https://www.yjsjewellers.com'),

    /*
    |--------------------------------------------------------------------------
    | Tax Registration Numbers
    |--------------------------------------------------------------------------
    |
    | GSTIN and PAN are required for GST invoices in India.
    | These MUST be set in production.
    |
    */

    'gstin' => env('COMPANY_GSTIN', ''),
    'pan' => env('COMPANY_PAN', ''),

    /*
    |--------------------------------------------------------------------------
    | Bank Details (for invoices)
    |--------------------------------------------------------------------------
    */

    'bank_name' => env('COMPANY_BANK_NAME', ''),
    'bank_account' => env('COMPANY_BANK_ACCOUNT', ''),
    'bank_ifsc' => env('COMPANY_BANK_IFSC', ''),
    'bank_branch' => env('COMPANY_BANK_BRANCH', ''),
];
