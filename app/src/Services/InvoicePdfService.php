<?php

namespace App\Services;

class InvoicePdfService
{
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;

    private const COLOR_BG = [0.996, 0.980, 0.957];
    private const COLOR_PAPER = [1.000, 1.000, 1.000];
    private const COLOR_INK = [0.078, 0.071, 0.059];
    private const COLOR_MUTED = [0.427, 0.388, 0.345];
    private const COLOR_LINE = [0.906, 0.867, 0.816];
    private const COLOR_BRAND = [0.812, 0.090, 0.247];
    private const COLOR_BRAND_DARK = [0.659, 0.067, 0.200];
    private const COLOR_ACCENT = [0.953, 0.737, 0.102];

    public function generate(array $payload): string
    {
        $invoiceNumber = (string) ($payload['invoice_number'] ?? 'INV-0');
        $orderReference = (string) ($payload['order_reference'] ?? 'n/a');
        $issuedAt = (string) ($payload['issued_at'] ?? date('Y-m-d H:i:s'));
        $customerName = (string) ($payload['customer_name'] ?? 'Customer');
        $customerEmail = (string) ($payload['customer_email'] ?? '');
        $customerAddress = (string) ($payload['customer_address'] ?? '');
        $customerPhone = (string) ($payload['customer_phone'] ?? '');
        $currency = strtoupper((string) ($payload['currency'] ?? 'EUR'));
        $totalPrice = (float) ($payload['total_price'] ?? 0);
        $totalTickets = (int) ($payload['total_tickets'] ?? 0);
        $items = array_values((array) ($payload['items'] ?? []));
        $terms = (string) ($payload['terms'] ?? 'Payment confirms your order. Keep this invoice for your records.');

        $pages = [];
        $content = $this->drawPageBase(false);
        $y = 734.0;

        $content .= $this->drawSummaryCard([
            'invoice_number' => $invoiceNumber,
            'order_reference' => $orderReference,
            'issued_at' => $issuedAt,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_address' => $customerAddress,
            'customer_phone' => $customerPhone,
            'currency' => $currency,
            'total_price' => $totalPrice,
            'total_tickets' => $totalTickets,
        ], 36, $y);

        $y -= 184;
        $content .= $this->drawSectionHeader(36, $y, $currency);
        $y -= 30;

        foreach ($items as $index => $item) {
            if ($y < 200) {
                $content .= $this->drawFooter($terms);
                $pages[] = $content;

                $content = $this->drawPageBase(true);
                $y = 734.0;
                $content .= $this->drawSectionHeader(36, $y, $currency);
                $y -= 30;
            }

            $content .= $this->drawInvoiceItemRow(36, $y, $index + 1, $item, $currency);
            $y -= 84;
        }

        if ($items === []) {
            $content .= $this->drawText(36, $y, 'F1', 10, 'No line items available for this invoice.', self::COLOR_MUTED);
            $y -= 20;
        }

        $content .= $this->drawTotalBar(36, max(120, $y - 12), $currency, $totalPrice, $totalTickets);
        $content .= $this->drawFooter($terms);
        $pages[] = $content;

        return $this->buildPdf($pages);
    }

    private function drawPageBase(bool $continued): string
    {
        $content = '';
        $content .= $this->drawFillRect(0, 0, self::PAGE_WIDTH, self::PAGE_HEIGHT, self::COLOR_BG);

        $content .= $this->drawFillRect(24, 760, 547, 58, self::COLOR_PAPER);
        $content .= $this->drawStrokeRect(24, 760, 547, 58, self::COLOR_LINE, 1);

        $content .= $this->drawFillRect(36, 775, 24, 24, self::COLOR_BRAND);
        $content .= $this->drawText(43, 781, 'F2', 12, 'H', self::COLOR_PAPER);

        $title = $continued ? 'Invoice PDF - Continued' : 'Invoice PDF';
        $content .= $this->drawText(70, 792, 'F2', 19, 'Haarlem Festival', self::COLOR_INK);
        $content .= $this->drawText(70, 774, 'F1', 10, $title, self::COLOR_MUTED);

        $content .= $this->drawFillRect(24, 754, 547, 4, self::COLOR_ACCENT);

        return $content;
    }

    private function drawSummaryCard(array $summary, float $x, float $yTop): string
    {
        $width = 523;
        $height = 156;
        $y = $yTop - $height;

        $invoiceNumber = (string) ($summary['invoice_number'] ?? 'INV-0');
        $orderReference = (string) ($summary['order_reference'] ?? 'n/a');
        $issuedAt = (string) ($summary['issued_at'] ?? '');
        $customerName = (string) ($summary['customer_name'] ?? 'Customer');
        $customerEmail = (string) ($summary['customer_email'] ?? '');
        $customerAddress = (string) ($summary['customer_address'] ?? '');
        $customerPhone = (string) ($summary['customer_phone'] ?? '');
        $currency = (string) ($summary['currency'] ?? 'EUR');
        $totalPrice = (float) ($summary['total_price'] ?? 0);
        $totalTickets = (int) ($summary['total_tickets'] ?? 0);

        $content = '';
        $content .= $this->drawFillRect($x, $y, $width, $height, self::COLOR_PAPER);
        $content .= $this->drawStrokeRect($x, $y, $width, $height, self::COLOR_LINE, 1);

        $content .= $this->drawText($x + 14, $yTop - 20, 'F2', 13, 'Invoice Summary', self::COLOR_INK);
        $content .= $this->drawText($x + 14, $yTop - 40, 'F1', 10, 'Invoice: ' . $invoiceNumber, self::COLOR_INK);
        $content .= $this->drawText($x + 14, $yTop - 56, 'F1', 10, 'Order: ' . $orderReference, self::COLOR_INK);
        $content .= $this->drawText($x + 14, $yTop - 72, 'F1', 10, 'Issued: ' . $issuedAt, self::COLOR_MUTED);
        $content .= $this->drawText($x + 14, $yTop - 88, 'F1', 10, 'Customer: ' . $customerName, self::COLOR_INK);

        if ($customerEmail !== '') {
            $content .= $this->drawText($x + 14, $yTop - 104, 'F1', 9, 'Email: ' . $customerEmail, self::COLOR_MUTED);
        }

        if ($customerAddress !== '') {
            $addressLines = PdfTextSanitizer::wrap('Address: ' . $customerAddress, 62);
            $addressY = $yTop - 118;
            foreach (array_slice($addressLines, 0, 2) as $line) {
                $content .= $this->drawText($x + 14, $addressY, 'F1', 9, $line, self::COLOR_MUTED);
                $addressY -= 12;
            }
        }

        if ($customerPhone !== '') {
            $content .= $this->drawText($x + 14, $yTop - 142, 'F1', 9, 'Phone: ' . $customerPhone, self::COLOR_MUTED);
        }

        $pillX = $x + $width - 176;
        $pillY = $y + 26;
        $content .= $this->drawFillRect($pillX, $pillY, 162, 84, [0.996, 0.930, 0.945]);
        $content .= $this->drawStrokeRect($pillX, $pillY, 162, 84, [0.953, 0.737, 0.790], 1);
        $content .= $this->drawText($pillX + 12, $pillY + 63, 'F1', 9, 'Total Tickets', self::COLOR_BRAND_DARK);
        $content .= $this->drawText($pillX + 12, $pillY + 46, 'F2', 14, (string) $totalTickets, self::COLOR_BRAND);
        $content .= $this->drawText($pillX + 12, $pillY + 26, 'F1', 9, 'Invoice Total', self::COLOR_BRAND_DARK);
        $content .= $this->drawText($pillX + 12, $pillY + 10, 'F2', 13, $currency . ' ' . number_format($totalPrice, 2), self::COLOR_BRAND);

        return $content;
    }

    private function drawSectionHeader(float $x, float $y, string $currency): string
    {
        $content = '';
        $content .= $this->drawText($x, $y, 'F2', 12, 'Invoice Items', self::COLOR_INK);
        $content .= $this->drawText($x + 406, $y, 'F1', 9, 'Currency: ' . $currency, self::COLOR_MUTED);
        $content .= $this->drawFillRect($x, $y - 8, 523, 2, self::COLOR_LINE);
        return $content;
    }

    private function drawInvoiceItemRow(float $x, float $yTop, int $lineNumber, array $item, string $currency): string
    {
        $width = 523;
        $height = 72;
        $y = $yTop - $height;

        $eventName = (string) ($item['event_name'] ?? 'Event');
        $eventDate = (string) ($item['event_date'] ?? '-');
        $eventTime = (string) ($item['event_time'] ?? '-');
        $venue = (string) ($item['venue'] ?? '-');
        $quantity = (int) ($item['quantity'] ?? 0);
        $unitPrice = (float) ($item['unit_price_value'] ?? 0);
        $lineTotal = (float) ($item['line_total_value'] ?? 0);

        $content = '';
        $content .= $this->drawFillRect($x, $y, $width, $height, self::COLOR_PAPER);
        $content .= $this->drawStrokeRect($x, $y, $width, $height, self::COLOR_LINE, 1);
        $content .= $this->drawFillRect($x, $y, 4, $height, self::COLOR_BRAND);

        $lineLabel = 'Line ' . $lineNumber;
        $content .= $this->drawText($x + 12, $yTop - 16, 'F1', 9, $lineLabel, self::COLOR_BRAND_DARK);

        $nameLines = PdfTextSanitizer::wrap($eventName, 46);
        $nameLines = array_slice($nameLines, 0, 2);

        $nameY = $yTop - 30;
        foreach ($nameLines as $line) {
            $content .= $this->drawText($x + 12, $nameY, 'F2', 11, $line, self::COLOR_INK);
            $nameY -= 13;
        }

        $detailY = $yTop - 56;
        if (count($nameLines) > 1) {
            $detailY -= 10;
        }

        $content .= $this->drawText($x + 12, $detailY, 'F1', 9, $eventDate . ' | ' . $eventTime, self::COLOR_MUTED);
        $content .= $this->drawText($x + 12, $detailY - 13, 'F1', 9, 'Venue: ' . $venue, self::COLOR_MUTED);

        $metricsX = $x + 344;
        $content .= $this->drawText($metricsX, $yTop - 20, 'F1', 8, 'Qty', self::COLOR_MUTED);
        $content .= $this->drawText($metricsX, $yTop - 34, 'F2', 10, (string) $quantity, self::COLOR_INK);

        $content .= $this->drawText($metricsX + 52, $yTop - 20, 'F1', 8, 'Unit', self::COLOR_MUTED);
        $content .= $this->drawText($metricsX + 52, $yTop - 34, 'F2', 10, $currency . ' ' . number_format($unitPrice, 2), self::COLOR_INK);

        $content .= $this->drawText($metricsX + 128, $yTop - 20, 'F1', 8, 'Line Total', self::COLOR_MUTED);
        $content .= $this->drawText($metricsX + 128, $yTop - 34, 'F2', 10, $currency . ' ' . number_format($lineTotal, 2), self::COLOR_BRAND_DARK);

        return $content;
    }

    private function drawTotalBar(float $x, float $y, string $currency, float $totalPrice, int $totalTickets): string
    {
        $width = 523;
        $height = 52;

        $content = '';
        $content .= $this->drawFillRect($x, $y, $width, $height, [0.996, 0.930, 0.945]);
        $content .= $this->drawStrokeRect($x, $y, $width, $height, [0.953, 0.737, 0.790], 1);

        $content .= $this->drawText($x + 12, $y + 31, 'F1', 9, 'Total tickets: ' . $totalTickets, self::COLOR_BRAND_DARK);
        $content .= $this->drawText($x + 12, $y + 15, 'F2', 11, 'Amount due: ' . $currency . ' ' . number_format($totalPrice, 2), self::COLOR_BRAND_DARK);

        return $content;
    }

    private function drawFooter(string $terms): string
    {
        $content = '';

        $content .= $this->drawFillRect(24, 82, 547, 1, self::COLOR_LINE);
        $content .= $this->drawText(36, 66, 'F2', 10, 'Invoice Terms', self::COLOR_INK);

        $lines = PdfTextSanitizer::wrap($terms, 102);
        $y = 50;
        foreach ($lines as $line) {
            $content .= $this->drawText(36, $y, 'F1', 8.5, $line, self::COLOR_MUTED);
            $y -= 11;
            if ($y < 18) {
                break;
            }
        }

        return $content;
    }

    private function drawFillRect(float $x, float $y, float $width, float $height, array $rgb): string
    {
        [$r, $g, $b] = $rgb;
        return sprintf("q %.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f Q\n", $r, $g, $b, $x, $y, $width, $height);
    }

    private function drawStrokeRect(float $x, float $y, float $width, float $height, array $rgb, float $lineWidth): string
    {
        [$r, $g, $b] = $rgb;
        return sprintf("q %.3f %.3f %.3f RG %.2f w %.2f %.2f %.2f %.2f re S Q\n", $r, $g, $b, $lineWidth, $x, $y, $width, $height);
    }

    private function drawText(float $x, float $y, string $fontAlias, float $fontSize, string $text, array $rgb): string
    {
        [$r, $g, $b] = $rgb;
        $escaped = PdfTextSanitizer::normalize($text);
        $escaped = str_replace('\\', '\\\\', $escaped);
        $escaped = str_replace('(', '\\(', $escaped);
        $escaped = str_replace(')', '\\)', $escaped);

        return sprintf(
            "BT /%s %.2f Tf %.3f %.3f %.3f rg 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n",
            $fontAlias,
            $fontSize,
            $r,
            $g,
            $b,
            $x,
            $y,
            $escaped
        );
    }

    private function buildPdf(array $pageContents): string
    {
        $objects = [];
        $addObject = static function (array &$target, string $content): int {
            $target[] = $content;
            return count($target);
        };

        $fontRegular = $addObject($objects, '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');
        $fontBold = $addObject($objects, '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>');

        $pageObjectNumbers = [];
        $contentObjectNumbers = [];

        foreach ($pageContents as $streamData) {
            $contentObj = $addObject(
                $objects,
                "<< /Length " . strlen($streamData) . " >>\nstream\n" . $streamData . "\nendstream"
            );
            $contentObjectNumbers[] = $contentObj;
            $pageObjectNumbers[] = $addObject($objects, '');
        }

        $pagesObj = $addObject($objects, '');
        $catalogObj = $addObject($objects, '<< /Type /Catalog /Pages ' . $pagesObj . ' 0 R >>');

        foreach ($pageObjectNumbers as $index => $pageObj) {
            $contentObj = $contentObjectNumbers[$index];
            $objects[$pageObj - 1] =
                '<< /Type /Page /Parent ' . $pagesObj . ' 0 R /MediaBox [0 0 ' . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . '] ' .
                '/Resources << /Font << /F1 ' . $fontRegular . ' 0 R /F2 ' . $fontBold . ' 0 R >> >> ' .
                '/Contents ' . $contentObj . ' 0 R >>';
        }

        $kids = implode(' ', array_map(static fn (int $obj): string => $obj . ' 0 R', $pageObjectNumbers));
        $objects[$pagesObj - 1] = '<< /Type /Pages /Kids [ ' . $kids . ' ] /Count ' . count($pageObjectNumbers) . ' >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $objectContent) {
            $objectNumber = $index + 1;
            $offsets[$objectNumber] = strlen($pdf);
            $pdf .= $objectNumber . " 0 obj\n" . $objectContent . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= '0 ' . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i]) . "\n";
        }

        $pdf .= "trailer\n";
        $pdf .= '<< /Size ' . (count($objects) + 1) . ' /Root ' . $catalogObj . ' 0 R >>' . "\n";
        $pdf .= "startxref\n";
        $pdf .= $xrefOffset . "\n";
        $pdf .= '%%EOF';

        return $pdf;
    }
}
