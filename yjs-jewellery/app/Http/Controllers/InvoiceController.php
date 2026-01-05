<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Invoice\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Invoice Controller
 *
 * Handles invoice generation for orders.
 * Supports JSON data, HTML preview, and PDF download.
 */
class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoiceService) {}

    /**
     * Get invoice data for an order (Customer).
     *
     * @param Order $order
     * @return JsonResponse
     */
    public function getInvoice(Order $order): JsonResponse
    {
        $userId = auth()->id();

        if ($order->customer_id !== $userId) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], 403);
        }

        if ($order->payment_status !== 'paid') {
            return response()->json([
                'success' => false,
                'error' => 'Invoice available only for paid orders',
            ], 400);
        }

        $invoiceData = $this->invoiceService->generateInvoiceData($order);

        return response()->json([
            'success' => true,
            'invoice' => $invoiceData,
        ]);
    }

    /**
     * Get invoice HTML preview (Customer).
     *
     * @param Order $order
     * @return Response|JsonResponse
     */
    public function getInvoiceHtml(Order $order): Response|JsonResponse
    {
        $userId = auth()->id();

        if ($order->customer_id !== $userId) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], 403);
        }

        if ($order->payment_status !== 'paid') {
            return response()->json([
                'success' => false,
                'error' => 'Invoice available only for paid orders',
            ], 400);
        }

        $invoiceData = $this->invoiceService->generateInvoiceData($order);

        // Render simple HTML invoice
        $html = $this->renderInvoiceHtml($invoiceData);

        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * Download invoice PDF (Customer).
     *
     * @param Order $order
     * @return Response|JsonResponse
     */
    public function downloadInvoice(Order $order): Response|JsonResponse
    {
        $userId = auth()->id();

        if ($order->customer_id !== $userId) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], 403);
        }

        if ($order->payment_status !== 'paid') {
            return response()->json([
                'success' => false,
                'error' => 'Invoice available only for paid orders',
            ], 400);
        }

        $invoiceData = $this->invoiceService->generateInvoiceData($order);

        // Check if PDF library is available
        if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.order', $invoiceData);
            return $pdf->download("Invoice-{$invoiceData['invoice_number']}.pdf");
        }

        // Fallback to HTML if PDF library not installed
        $html = $this->renderInvoiceHtml($invoiceData);

        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', "attachment; filename=Invoice-{$invoiceData['invoice_number']}.html");
    }

    /**
     * Admin: Get invoice data for any order.
     *
     * @param Order $order
     * @return JsonResponse
     */
    public function adminGetInvoice(Order $order): JsonResponse
    {
        $invoiceData = $this->invoiceService->generateInvoiceData($order);

        return response()->json([
            'success' => true,
            'invoice' => $invoiceData,
        ]);
    }

    /**
     * Admin: Download invoice PDF for any order.
     *
     * @param Order $order
     * @return Response|JsonResponse
     */
    public function adminDownloadInvoice(Order $order): Response|JsonResponse
    {
        $invoiceData = $this->invoiceService->generateInvoiceData($order);

        // Check if PDF library is available
        if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.order', $invoiceData);
            return $pdf->download("Invoice-{$invoiceData['invoice_number']}.pdf");
        }

        // Fallback to HTML if PDF library not installed
        $html = $this->renderInvoiceHtml($invoiceData);

        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', "attachment; filename=Invoice-{$invoiceData['invoice_number']}.html");
    }

    /**
     * Admin: Bulk generate invoices.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkGetInvoices(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_ids' => 'required|array|min:1|max:50',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        $invoices = [];

        $orders = Order::whereIn('id', $validated['order_ids'])
            ->where('payment_status', 'paid')
            ->get();

        foreach ($orders as $order) {
            $invoices[] = $this->invoiceService->generateInvoiceData($order);
        }

        return response()->json([
            'success' => true,
            'invoices' => $invoices,
            'count' => count($invoices),
        ]);
    }

    /**
     * Render simple HTML invoice (inline method).
     *
     * @param array $data
     * @return string
     */
    private function renderInvoiceHtml(array $data): string
    {
        $itemsHtml = '';
        foreach ($data['items'] as $item) {
            $unitPrice = number_format($item['unit_price'], 2);
            $total = number_format($item['total'], 2);
            $itemsHtml .= "<tr>
                <td>{$item['sr_no']}</td>
                <td>{$item['name']}<br><small>SKU: {$item['sku']} | HSN: {$item['hsn_code']}</small></td>
                <td style='text-align: right;'>{$item['quantity']}</td>
                <td style='text-align: right;'>{$unitPrice}</td>
                <td style='text-align: right;'>{$total}</td>
            </tr>";
        }

        $taxesHtml = '';
        foreach ($data['taxes'] as $tax) {
            $taxAmount = number_format($tax['amount'], 2);
            $taxesHtml .= "<tr>
                <td colspan='4' style='text-align: right;'>{$tax['name']} ({$tax['rate']}%)</td>
                <td style='text-align: right;'>{$taxAmount}</td>
            </tr>";
        }

        $discountRow = '';
        if ($data['discount'] > 0) {
            $discountAmount = number_format($data['discount'], 2);
            $discountRow = "<tr>
                <td colspan='4' style='text-align: right;'>Discount ({$data['discount_description']})</td>
                <td style='text-align: right;'>-{$discountAmount}</td>
            </tr>";
        }

        $shippingRow = '';
        if ($data['shipping_charges'] > 0) {
            $shippingAmount = number_format($data['shipping_charges'], 2);
            $shippingRow = "<tr>
                <td colspan='4' style='text-align: right;'>Shipping Charges</td>
                <td style='text-align: right;'>{$shippingAmount}</td>
            </tr>";
        }

        $shippingAddress = $data['shipping_address'];
        $addressLine2 = ($shippingAddress && $shippingAddress['address_line_2']) ? $shippingAddress['address_line_2'] . '<br>' : '';
        $addressHtml = $shippingAddress ? "
            <strong>{$shippingAddress['name']}</strong><br>
            {$shippingAddress['address_line_1']}<br>
            {$addressLine2}
            {$shippingAddress['city']}, {$shippingAddress['state']} - {$shippingAddress['pincode']}<br>
            Phone: {$shippingAddress['phone']}
        " : 'N/A';

        $subtotalFormatted = number_format($data['subtotal'], 2);
        $grandTotalFormatted = number_format($data['grand_total'], 2);

        $termsHtml = '';
        foreach ($data['terms'] as $term) {
            $termsHtml .= "<li>{$term}</li>";
        }

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice - {$data['invoice_number']}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        h1 { color: #333; margin-bottom: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .company-details { max-width: 300px; }
        .invoice-details { text-align: right; }
        .addresses { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .address-block { width: 45%; }
        .address-block h4 { margin-bottom: 10px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f5f5f5; text-align: left; }
        .totals th { text-align: right; font-weight: normal; }
        .grand-total { font-weight: bold; font-size: 14px; background-color: #f0f0f0; }
        .amount-words { margin: 20px 0; font-style: italic; }
        .footer { margin-top: 30px; font-size: 11px; color: #666; }
        .terms { margin-top: 20px; }
        .terms li { margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-details">
            <h1 style="margin: 0;">{$data['company']['name']}</h1>
            <p>
                {$data['company']['address']}<br>
                {$data['company']['city']}, {$data['company']['state']} - {$data['company']['pincode']}<br>
                Phone: {$data['company']['phone']}<br>
                Email: {$data['company']['email']}<br>
                GSTIN: {$data['company']['gstin']}<br>
                PAN: {$data['company']['pan']}
            </p>
        </div>
        <div class="invoice-details">
            <h2 style="margin: 0;">TAX INVOICE</h2>
            <p>
                <strong>Invoice No:</strong> {$data['invoice_number']}<br>
                <strong>Invoice Date:</strong> {$data['invoice_date']}<br>
                <strong>Order No:</strong> {$data['order_number']}<br>
                <strong>Order Date:</strong> {$data['order_date']}
            </p>
        </div>
    </div>

    <div class="addresses">
        <div class="address-block">
            <h4>Bill To:</h4>
            <p>
                <strong>{$data['customer']['name']}</strong><br>
                Email: {$data['customer']['email']}<br>
                Phone: {$data['customer']['phone']}
            </p>
        </div>
        <div class="address-block">
            <h4>Ship To:</h4>
            <p>{$addressHtml}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">#</th>
                <th>Description</th>
                <th style="width: 60px; text-align: right;">Qty</th>
                <th style="width: 100px; text-align: right;">Unit Price</th>
                <th style="width: 100px; text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            {$itemsHtml}
        </tbody>
        <tfoot class="totals">
            <tr>
                <td colspan="4" style="text-align: right;"><strong>Subtotal</strong></td>
                <td style="text-align: right;"><strong>₹ {$subtotalFormatted}</strong></td>
            </tr>
            {$discountRow}
            {$taxesHtml}
            {$shippingRow}
            <tr class="grand-total">
                <td colspan="4" style="text-align: right;"><strong>Grand Total</strong></td>
                <td style="text-align: right;"><strong>₹ {$grandTotalFormatted}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="amount-words">
        <strong>Amount in Words:</strong> {$data['amount_in_words']}
    </div>

    <div class="footer">
        <div class="terms">
            <strong>Terms & Conditions:</strong>
            <ol>
            {$termsHtml}
            </ol>
        </div>
        <p style="text-align: center; margin-top: 50px;">
            <strong>This is a computer-generated invoice. No signature required.</strong>
        </p>
    </div>
</body>
</html>
HTML;

        return $html;
    }
}
