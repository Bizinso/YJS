<?php

namespace App\Services\Invoice;

use App\Models\Order;
use Illuminate\Support\Facades\Storage;

/**
 * Invoice Service
 *
 * Generates invoice data and HTML for orders.
 * PDF generation requires barryvdh/laravel-dompdf package.
 */
class InvoiceService
{
    /**
     * Company details for invoice header.
     */
    private array $companyDetails = [
        'name' => 'YJS Jewellers',
        'address' => 'Shop No. 123, Gold Market',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'pincode' => '400001',
        'country' => 'India',
        'phone' => '+91 9876543210',
        'email' => 'support@yjsjewellers.com',
        'gstin' => 'GSTIN1234567890',
        'pan' => 'AAACY1234Z',
    ];

    /**
     * Generate invoice data for an order.
     *
     * @param Order $order
     * @return array
     */
    public function generateInvoiceData(Order $order): array
    {
        $order->load([
            'customer:id,first_name,last_name,email,phone',
            'shippingAddress',
            'billingAddress',
            'orderProducts.product:id,name,sku,hsn_code',
            'orderOffer',
        ]);

        $invoiceNumber = $this->generateInvoiceNumber($order);

        return [
            'invoice_number' => $invoiceNumber,
            'invoice_date' => now()->format('d-m-Y'),
            'order_number' => $order->custom_order_code,
            'order_date' => $order->order_date?->format('d-m-Y'),
            'company' => $this->getCompanyDetails(),
            'customer' => $this->getCustomerDetails($order),
            'shipping_address' => $this->getAddressDetails($order->shippingAddress),
            'billing_address' => $this->getAddressDetails($order->billingAddress ?? $order->shippingAddress),
            'items' => $this->getOrderItems($order),
            'subtotal' => (float) $order->order_subtotal,
            'discount' => (float) ($order->orderOffer->applied_discount ?? 0),
            'discount_description' => $order->orderOffer ? $order->orderOffer->offer_title : null,
            'shipping_charges' => (float) $order->shipping_charges,
            'taxes' => $this->getTaxBreakdown($order),
            'total_taxes' => (float) $order->total_taxes,
            'total_charges' => (float) $order->total_charges,
            'grand_total' => (float) $order->order_total,
            'payment_method' => ucfirst($order->payment_method),
            'payment_status' => ucfirst($order->payment_status),
            'amount_in_words' => $this->amountInWords($order->order_total),
            'notes' => $this->getInvoiceNotes($order),
            'terms' => $this->getInvoiceTerms(),
        ];
    }

    /**
     * Generate invoice HTML.
     *
     * @param Order $order
     * @return string
     */
    public function generateInvoiceHtml(Order $order): string
    {
        $data = $this->generateInvoiceData($order);
        return view('invoices.order', $data)->render();
    }

    /**
     * Generate unique invoice number.
     *
     * @param Order $order
     * @return string
     */
    public function generateInvoiceNumber(Order $order): string
    {
        $prefix = 'INV';
        $year = date('y');
        $month = date('m');
        return "{$prefix}{$year}{$month}-{$order->id}";
    }

    /**
     * Get company details.
     *
     * @return array
     */
    private function getCompanyDetails(): array
    {
        return [
            'name' => config('app.company_name', $this->companyDetails['name']),
            'address' => config('app.company_address', $this->companyDetails['address']),
            'city' => config('app.company_city', $this->companyDetails['city']),
            'state' => config('app.company_state', $this->companyDetails['state']),
            'pincode' => config('app.company_pincode', $this->companyDetails['pincode']),
            'country' => 'India',
            'phone' => config('app.company_phone', $this->companyDetails['phone']),
            'email' => config('app.company_email', $this->companyDetails['email']),
            'gstin' => config('app.company_gstin', $this->companyDetails['gstin']),
            'pan' => config('app.company_pan', $this->companyDetails['pan']),
        ];
    }

    /**
     * Get customer details.
     *
     * @param Order $order
     * @return array
     */
    private function getCustomerDetails(Order $order): array
    {
        return [
            'name' => trim(($order->customer?->first_name ?? '') . ' ' . ($order->customer?->last_name ?? '')),
            'email' => $order->email ?? $order->customer?->email,
            'phone' => $order->customer?->phone,
        ];
    }

    /**
     * Get address details.
     *
     * @param mixed $address
     * @return array|null
     */
    private function getAddressDetails($address): ?array
    {
        if (!$address) {
            return null;
        }

        return [
            'name' => trim(($address->first_name ?? '') . ' ' . ($address->last_name ?? '')),
            'address_line_1' => $address->address_line_1 ?? $address->address ?? '',
            'address_line_2' => $address->address_line_2 ?? '',
            'city' => $address->city ?? '',
            'state' => $address->state ?? '',
            'pincode' => $address->pincode ?? $address->postal_code ?? '',
            'country' => $address->country ?? 'India',
            'phone' => $address->phone ?? '',
        ];
    }

    /**
     * Get order items with details.
     *
     * @param Order $order
     * @return array
     */
    private function getOrderItems(Order $order): array
    {
        return $order->orderProducts->map(function ($item, $index) {
            return [
                'sr_no' => $index + 1,
                'name' => $item->product?->name ?? $item->product_name ?? 'Product',
                'sku' => $item->product?->sku ?? 'N/A',
                'hsn_code' => $item->product?->hsn_code ?? '7113',
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->price,
                'discount' => (float) ($item->discount ?? 0),
                'taxable_value' => (float) ($item->price * $item->quantity - ($item->discount ?? 0)),
                'gst_rate' => (float) ($item->gst_rate ?? 3),
                'gst_amount' => (float) ($item->gst_amount ?? 0),
                'total' => (float) ($item->total ?? ($item->price * $item->quantity)),
            ];
        })->toArray();
    }

    /**
     * Get tax breakdown.
     *
     * @param Order $order
     * @return array
     */
    private function getTaxBreakdown(Order $order): array
    {
        $taxes = [];
        $totalTax = (float) $order->total_taxes;

        if ($totalTax > 0) {
            // For jewellery, GST is typically 3%
            // Split into CGST and SGST for intra-state, or IGST for inter-state
            $isInterState = $this->isInterStateOrder($order);

            if ($isInterState) {
                $taxes[] = [
                    'name' => 'IGST',
                    'rate' => 3,
                    'amount' => $totalTax,
                ];
            } else {
                $taxes[] = [
                    'name' => 'CGST',
                    'rate' => 1.5,
                    'amount' => round($totalTax / 2, 2),
                ];
                $taxes[] = [
                    'name' => 'SGST',
                    'rate' => 1.5,
                    'amount' => round($totalTax / 2, 2),
                ];
            }
        }

        return $taxes;
    }

    /**
     * Check if order is inter-state.
     *
     * @param Order $order
     * @return bool
     */
    private function isInterStateOrder(Order $order): bool
    {
        $companyState = config('app.company_state', 'Maharashtra');
        $customerState = $order->shippingAddress?->state ?? '';

        return strtolower($companyState) !== strtolower($customerState);
    }

    /**
     * Convert amount to words.
     *
     * @param float $amount
     * @return string
     */
    private function amountInWords(float $amount): string
    {
        $ones = [
            '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
            'Seventeen', 'Eighteen', 'Nineteen',
        ];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $amount = round($amount);

        if ($amount == 0) {
            return 'Zero Rupees Only';
        }

        $words = [];

        // Crores
        if ($amount >= 10000000) {
            $crores = (int) ($amount / 10000000);
            $words[] = $this->convertLessThanHundred($crores, $ones, $tens) . ' Crore';
            $amount %= 10000000;
        }

        // Lakhs
        if ($amount >= 100000) {
            $lakhs = (int) ($amount / 100000);
            $words[] = $this->convertLessThanHundred($lakhs, $ones, $tens) . ' Lakh';
            $amount %= 100000;
        }

        // Thousands
        if ($amount >= 1000) {
            $thousands = (int) ($amount / 1000);
            $words[] = $this->convertLessThanHundred($thousands, $ones, $tens) . ' Thousand';
            $amount %= 1000;
        }

        // Hundreds
        if ($amount >= 100) {
            $hundreds = (int) ($amount / 100);
            $words[] = $ones[$hundreds] . ' Hundred';
            $amount %= 100;
        }

        // Less than hundred
        if ($amount > 0) {
            $words[] = $this->convertLessThanHundred($amount, $ones, $tens);
        }

        return implode(' ', $words) . ' Rupees Only';
    }

    /**
     * Convert number less than 100 to words.
     *
     * @param int $number
     * @param array $ones
     * @param array $tens
     * @return string
     */
    private function convertLessThanHundred(int $number, array $ones, array $tens): string
    {
        if ($number < 20) {
            return $ones[$number];
        }

        $ten = (int) ($number / 10);
        $one = $number % 10;

        return $tens[$ten] . ($one > 0 ? ' ' . $ones[$one] : '');
    }

    /**
     * Get invoice notes.
     *
     * @param Order $order
     * @return array
     */
    private function getInvoiceNotes(Order $order): array
    {
        $notes = [];

        if ($order->orderOffer) {
            $notes[] = "Discount Applied: {$order->orderOffer->offer_title}";
        }

        if ($order->notes) {
            $notes[] = $order->notes;
        }

        return $notes;
    }

    /**
     * Get invoice terms and conditions.
     *
     * @return array
     */
    private function getInvoiceTerms(): array
    {
        return [
            'Goods once sold will not be taken back.',
            'Subject to Mumbai jurisdiction only.',
            'E. & O.E.',
            'All disputes are subject to arbitration.',
        ];
    }
}
