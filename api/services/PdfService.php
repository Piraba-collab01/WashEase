<?php
// washease-api/services/PdfService.php

require_once __DIR__ . '/../libs/fpdf.php';

class PdfService {

    public static function generateReportPDF($title, $headers, $data) {
        try {
            // Check if TCPDF exists, otherwise use FPDF
            $autoloadPath = __DIR__ . '/../vendor/autoload.php';
            if (file_exists($autoloadPath)) {
                require_once $autoloadPath;
            }

            if (class_exists('TCPDF')) {
                $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetCreator(PDF_CREATOR);
                $pdf->SetTitle($title);
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
                $pdf->AddPage();

                $html = "
                <div style='font-family: Arial, sans-serif; padding: 20px;'>
                    <h2 style='color: #4A154B; text-align: center;'>WashEase - System Report</h2>
                    <h3 style='text-align: center; color: #666;'>$title</h3>
                    <p style='text-align: right;'>Generated: " . date('Y-m-d H:i:s') . "</p>
                    <table style='width: 100%; border-collapse: collapse; margin-top: 15px;'>
                        <thead>
                            <tr style='background: #4A154B; color: white;'>";
                foreach ($headers as $header) {
                    $html .= "<th style='border: 1px solid #ddd; padding: 8px;'>$header</th>";
                }
                $html .= "</tr></thead><tbody>";
                foreach ($data as $row) {
                    $html .= "<tr>";
                    foreach ($row as $val) {
                        $html .= "<td style='border: 1px solid #ddd; padding: 6px;'>$val</td>";
                    }
                    $html .= "</tr>";
                }
                $html .= "</tbody></table></div>";
                $pdf->writeHTML($html, true, false, true, false, '');
                return $pdf->Output('report.pdf', 'S');
            }

            // Fallback to native FPDF generator
            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->AddPage();
            
            // Title Header Banner (Purple #4A154B)
            $pdf->SetFillColor(74, 21, 75);
            $pdf->Rect(10, 10, 190, 20, 'F');
            $pdf->SetFont('Helvetica', 'B', 16);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetXY(10, 15);
            $pdf->Cell(190, 10, 'WashEase - System Report', 0, 1, 'C');
            
            $pdf->Ln(10);
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->SetTextColor(74, 21, 75);
            $pdf->Cell(0, 8, $title, 0, 1, 'C');
            
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(0, 6, 'Generated Date: ' . date('Y-m-d H:i:s'), 0, 1, 'R');
            $pdf->Ln(4);
            
            // Table Headers
            $pdf->SetFillColor(74, 21, 75);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Helvetica', 'B', 9);
            
            $colWidth = 190 / max(1, count($headers));
            foreach ($headers as $header) {
                $pdf->Cell($colWidth, 8, ' ' . $header, 1, 0, 'L', true);
            }
            $pdf->Ln();
            
            // Table Rows
            $pdf->SetTextColor(40, 40, 40);
            $pdf->SetFont('Helvetica', '', 8);
            $fill = false;
            
            foreach ($data as $row) {
                $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
                foreach ($row as $val) {
                    $pdf->Cell($colWidth, 7, ' ' . substr((string)$val, 0, 28), 1, 0, 'L', true);
                }
                $pdf->Ln();
                $fill = !$fill;
            }
            
            return $pdf->Output('S', 'report.pdf');
        } catch (Exception $e) {
            error_log("PDF Report Generation Failed: " . $e->getMessage());
            return self::generateHTMLReport($title, $headers, $data);
        }
    }

    public static function generateInvoicePDF($invoice, $order, $customer, $vendor) {
        try {
            $autoloadPath = __DIR__ . '/../vendor/autoload.php';
            if (file_exists($autoloadPath)) {
                require_once $autoloadPath;
            }

            if (class_exists('TCPDF')) {
                $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetCreator(PDF_CREATOR);
                $pdf->SetTitle('Invoice ' . $invoice['invoice_number']);
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
                $pdf->AddPage();

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
                                <b>Contact:</b> " . ($vendor['contact_number'] ?? 'N/A') . "<br/>
                                <b>Owner:</b> " . ($vendor['owner_name'] ?? 'N/A') . "
                            </td>
                        </tr>
                    </table>
                    <h4 style='background: #f4f4f4; padding: 8px;'>Customer Details</h4>
                    <p>
                        <b>Name:</b> " . ($customer['full_name'] ?? $customer['username'] ?? 'Customer') . "<br/>
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
                </div>";

                $pdf->writeHTML($html, true, false, true, false, '');
                return $pdf->Output('invoice_' . $invoice['invoice_number'] . '.pdf', 'S');
            }

            // Fallback to FPDF native PDF binary
            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->AddPage();
            
            // Purple Header Banner
            $pdf->SetFillColor(74, 21, 75);
            $pdf->Rect(10, 10, 190, 22, 'F');
            $pdf->SetFont('Helvetica', 'B', 18);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetXY(10, 16);
            $pdf->Cell(190, 10, 'WashEase - Digital Invoice', 0, 1, 'C');
            
            $pdf->Ln(12);
            
            // Invoice Metadata Table
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetTextColor(40, 40, 40);
            
            $pdf->Cell(95, 6, 'Invoice No: ' . $invoice['invoice_number'], 0, 0, 'L');
            $pdf->Cell(95, 6, 'Shop Name: ' . $vendor['shop_name'], 0, 1, 'R');
            
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->Cell(95, 5, 'Date: ' . $invoice['created_at'], 0, 0, 'L');
            $pdf->Cell(95, 5, 'Contact: ' . ($vendor['contact_number'] ?? 'N/A'), 0, 1, 'R');
            
            $pdf->Cell(95, 5, 'Tracking ID: ' . $order['tracking_number'], 0, 0, 'L');
            $pdf->Cell(95, 5, 'Owner: ' . ($vendor['owner_name'] ?? 'N/A'), 0, 1, 'R');
            
            $pdf->Ln(6);
            $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
            $pdf->Ln(4);
            
            // Customer Info
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell(190, 7, ' Customer Details', 0, 1, 'L', true);
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->Cell(190, 5, ' Name: ' . ($customer['full_name'] ?? $customer['username'] ?? 'Customer'), 0, 1);
            $pdf->Cell(190, 5, ' Address: ' . $order['pickup_address'], 0, 1);
            
            $pdf->Ln(6);
            
            // Items Table
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->SetFillColor(74, 21, 75);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(90, 8, ' Service Type', 1, 0, 'L', true);
            $pdf->Cell(50, 8, ' Weight (kg)', 1, 0, 'R', true);
            $pdf->Cell(50, 8, ' Rate (Rs/kg)', 1, 1, 'R', true);
            
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(40, 40, 40);
            $rate = number_format($invoice['laundry_charges'] / max(1, $order['clothes_weight']), 0);
            $pdf->Cell(90, 8, ' ' . $order['service_type'], 1, 0, 'L');
            $pdf->Cell(50, 8, ' ' . $order['clothes_weight'], 1, 0, 'R');
            $pdf->Cell(50, 8, ' Rs ' . $rate . ' ', 1, 1, 'R');
            
            $pdf->Ln(6);
            
            // Totals Block
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->Cell(130, 6, '', 0, 0);
            $pdf->Cell(60, 6, 'Laundry Charges: Rs ' . number_format($invoice['laundry_charges'], 0), 0, 1, 'R');
            
            $pdf->Cell(130, 6, '', 0, 0);
            $pdf->Cell(60, 6, 'Service Charges: Rs ' . number_format($invoice['service_charges'], 0), 0, 1, 'R');
            
            $pdf->Cell(130, 6, '', 0, 0);
            $pdf->Cell(60, 6, 'Taxes (5%): Rs ' . number_format($invoice['taxes'], 0), 0, 1, 'R');
            
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->SetTextColor(74, 21, 75);
            $pdf->Cell(130, 8, '', 0, 0);
            $pdf->Cell(60, 8, 'Total: Rs ' . number_format($invoice['total_amount'], 0), 0, 1, 'R');
            
            return $pdf->Output('S', 'invoice_' . $invoice['invoice_number'] . '.pdf');
        } catch (Exception $e) {
            error_log("PDF Invoice Generation Failed: " . $e->getMessage());
            return self::generateHTMLInvoice($invoice, $order, $customer, $vendor);
        }
    }

    private static function generateHTMLReport($title, $headers, $data) {
        $html = "<!DOCTYPE html><html><head><title>" . htmlspecialchars($title) . "</title>";
        $html .= "<style>body{font-family:Arial,sans-serif;padding:20px;color:#333;} h2{color:#4A154B;text-align:center;} table{width:100%;border-collapse:collapse;margin-top:15px;} th{background:#4A154B;color:white;padding:10px;text-align:left;} td{border:1px solid #ddd;padding:8px;} @media print{body{padding:0;}}</style>";
        $html .= "</head><body onload='window.print()'>";
        $html .= "<h2>WashEase - System Report</h2><h3 style='text-align:center;'>$title</h3>";
        $html .= "<table><thead><tr>";
        foreach ($headers as $h) {
            $html .= "<th>" . htmlspecialchars($h) . "</th>";
        }
        $html .= "</tr></thead><tbody>";
        foreach ($data as $row) {
            $html .= "<tr>";
            foreach ($row as $val) {
                $html .= "<td>" . htmlspecialchars($val) . "</td>";
            }
            $html .= "</tr>";
        }
        $html .= "</tbody></table></body></html>";
        return $html;
    }

    private static function generateHTMLInvoice($invoice, $order, $customer, $vendor) {
        return "<html><body onload='window.print()'><h2>Invoice {$invoice['invoice_number']}</h2><p>Total: Rs {$invoice['total_amount']}</p></body></html>";
    }
}
