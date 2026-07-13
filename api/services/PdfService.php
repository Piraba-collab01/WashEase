<?php
// washease-api/services/PdfService.php

class PdfService {
    public static function generateInvoicePDF($invoice, $order, $customer, $vendor) {
        $html = "
        <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
            <h2 style='color: #4A154B; text-align: center;'>WashEase - Digital Invoice</h2>
            <hr style='border: 1px solid #ddd;'/>
            <table style='width: 100%; margin-bottom: 20px;'>
                <tr>
                    <td>
                        <b>Invoice Number:</b> {$invoice['invoice_number']}<br/>
                        <b>Date:</b> {$invoice['created_at']}<br/>
                        <b>Order Tracking:</b> {$order['tracking_number']}
                    </td>
                    <td style='text-align: right;'>
                        <b>Shop Name:</b> {$vendor['shop_name']}<br/>
                        <b>Contact:</b> {$vendor['contact_number']}<br/>
                        <b>Owner:</b> {$vendor['owner_name']}
                    </td>
                </tr>
            </table>
            <h4 style='background: #f4f4f4; padding: 8px;'>Customer Details</h4>
            <p>
                <b>Name:</b> {$customer['full_name']}<br/>
                <b>Contact:</b> {$customer['contact_number']}<br/>
                <b>Address:</b> {$order['pickup_address']}
            </p>
            <h4 style='background: #f4f4f4; padding: 8px;'>Order Summary</h4>
            <table style='width: 100%; border-collapse: collapse;'>
                <thead>
                    <tr style='background: #f8f9fa;'>
                        <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Service Type</th>
                        <th style='border: 1px solid #ddd; padding: 8px; text-align: right;'>Weight (kg)</th>
                        <th style='border: 1px solid #ddd; padding: 8px; text-align: right;'>Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style='border: 1px solid #ddd; padding: 8px;'>{$order['service_type']}</td>
                        <td style='border: 1px solid #ddd; padding: 8px; text-align: right;'>{$order['clothes_weight']}</td>
                        <td style='border: 1px solid #ddd; padding: 8px; text-align: right;'>Rs " . number_format($invoice['laundry_charges'] / max(1, $order['clothes_weight']), 0) . " / kg</td>
                    </tr>
                </tbody>
            </table>
            <div style='margin-top: 20px; text-align: right;'>
                <p><b>Laundry Charges:</b> Rs " . number_format($invoice['laundry_charges'], 0) . "</p>
                <p><b>Service Charges:</b> Rs " . number_format($invoice['service_charges'], 0) . "</p>
                <p><b>Taxes (5%):</b> Rs " . number_format($invoice['taxes'], 0) . "</p>
                <h3 style='color: #4A154B;'><b>Total Amount:</b> Rs " . number_format($invoice['total_amount'], 0) . "</h3>
            </div>
            <hr style='border: 1px solid #ddd;'/>
            <p style='text-align: center; font-size: 12px; color: #777;'>Thank you for choosing WashEase!</p>
        </div>";

        // Try TCPDF if Composer autoload exists
        $autoloadPath = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
            try {
                $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetCreator(PDF_CREATOR);
                $pdf->SetAuthor('WashEase');
                $pdf->SetTitle('Invoice ' . $invoice['invoice_number']);
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
                $pdf->AddPage();
                $pdf->writeHTML($html, true, false, true, false, '');
                return $pdf->Output('invoice_' . $invoice['invoice_number'] . '.pdf', 'S'); // Return string representation
            } catch (Exception $e) {
                error_log("TCPDF Generation failed: " . $e->getMessage());
            }
        }

        // HTML Fallback: return HTML representation
        return $html;
    }

    public static function generateReportPDF($title, $headers, $data) {
        $html = "
        <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
            <h2 style='color: #4A154B; text-align: center;'>WashEase - Report</h2>
            <h3 style='text-align: center; color: #666;'>$title</h3>
            <p style='text-align: right;'>Generated on: " . date('Y-m-d H:i:s') . "</p>
            <table style='width: 100%; border-collapse: collapse; margin-top: 15px;'>
                <thead>
                    <tr style='background: #4A154B; color: white;'>";
        foreach ($headers as $header) {
            $html .= "<th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>$header</th>";
        }
        $html .= "  </tr>
                </thead>
                <tbody>";
        foreach ($data as $row) {
            $html .= "<tr>";
            foreach ($row as $val) {
                $html .= "<td style='border: 1px solid #ddd; padding: 8px;'>$val</td>";
            }
            $html .= "</tr>";
        }
        $html .= "  </tbody>
            </table>
        </div>";

        // Try TCPDF if autoload exists
        $autoloadPath = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
            try {
                $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetCreator(PDF_CREATOR);
                $pdf->SetTitle($title);
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
                $pdf->AddPage();
                $pdf->writeHTML($html, true, false, true, false, '');
                return $pdf->Output('report.pdf', 'S');
            } catch (Exception $e) {
                error_log("TCPDF Report failed: " . $e->getMessage());
            }
        }

        return $html;
    }
}
