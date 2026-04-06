<?php

namespace App\Services;

use DateTimeImmutable;

class TicketPdfService
{
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;

    private const COLOR_BG = [0.996, 0.980, 0.957];      // #fffaf4
    private const COLOR_PAPER = [1.000, 1.000, 1.000];   // #ffffff
    private const COLOR_INK = [0.078, 0.071, 0.059];     // #14120f
    private const COLOR_MUTED = [0.427, 0.388, 0.345];   // #6d6358
    private const COLOR_LINE = [0.906, 0.867, 0.816];    // #e7ddd0
    private const COLOR_BRAND = [0.812, 0.090, 0.247];   // #cf173f
    private const COLOR_BRAND_DARK = [0.659, 0.067, 0.200]; // #a81133
    private const COLOR_ACCENT = [0.953, 0.737, 0.102];  // #f3bc1a

    public function generate(array $payload): string
    {
        $customerName = (string) ($payload['customer_name'] ?? 'Customer');
        $purchaseReference = (string) ($payload['purchase_reference'] ?? 'n/a');
        $issuedAt = (string) ($payload['issued_at'] ?? date('Y-m-d H:i:s'));
        $tickets = array_values((array) ($payload['tickets'] ?? []));
        $terms = (string) ($payload['terms'] ?? 'Tickets are non-refundable. Bring a valid ID and this ticket PDF for entry.');

        $pages = [];
        $content = $this->drawPageBase(false);
        $y = 734.0;

        $content .= $this->drawSummaryCard($customerName, $purchaseReference, $issuedAt, count($tickets), 36, $y);
        $y -= 132;

        $content .= $this->drawSectionTitle(36, $y, 'Purchased Tickets');
        $y -= 28;

        $ticketNumber = 1;
        foreach ($tickets as $ticket) {
            if ($y < 186) {
                $content .= $this->drawFooter($terms);
                $pages[] = $content;

                $content = $this->drawPageBase(true);
                $y = 734.0;
                $content .= $this->drawSectionTitle(36, $y, 'Purchased Tickets (continued)');
                $y -= 28;
            }

            $content .= $this->drawTicketCard(36, $y, $ticketNumber, $ticket);
            $ticketNumber++;
            $y -= 136;
        }

        $content .= $this->drawFooter($terms);
        $pages[] = $content;

        return $this->buildPdf($pages);
    }

    private function drawPageBase(bool $continued): string
    {
        $content = '';
        $content .= $this->drawFillRect(0, 0, self::PAGE_WIDTH, self::PAGE_HEIGHT, self::COLOR_BG);

        // Top bar inspired by the main site topbar (light with subtle border).
        $content .= $this->drawFillRect(24, 760, 547, 58, self::COLOR_PAPER);
        $content .= $this->drawStrokeRect(24, 760, 547, 58, self::COLOR_LINE, 1);

        // Brand mark block.
        $content .= $this->drawFillRect(36, 775, 24, 24, self::COLOR_BRAND);
        $content .= $this->drawText(43, 781, 'F2', 12, 'H', self::COLOR_PAPER);

        $content .= $this->drawText(70, 792, 'F2', 19, 'Haarlem Festival', self::COLOR_INK);
        $subtitle = $continued ? 'Event Ticket PDF - Continued' : 'Event Ticket PDF';
        $content .= $this->drawText(70, 774, 'F1', 10, $subtitle, self::COLOR_MUTED);

        // Accent divider similar to primary accent usage.
        $content .= $this->drawFillRect(24, 754, 547, 4, self::COLOR_ACCENT);

        return $content;
    }

    private function drawSummaryCard(string $customerName, string $purchaseReference, string $issuedAt, int $ticketCount, float $x, float $yTop): string
    {
        $width = 523;
        $height = 104;
        $y = $yTop - $height;

        $content = '';
        $content .= $this->drawFillRect($x, $y, $width, $height, self::COLOR_PAPER);
        $content .= $this->drawStrokeRect($x, $y, $width, $height, self::COLOR_LINE, 1);

        $content .= $this->drawText($x + 14, $yTop - 20, 'F2', 13, 'Ticket Purchase Confirmation', self::COLOR_INK);
        $content .= $this->drawText($x + 14, $yTop - 40, 'F1', 10, 'Customer: ' . $customerName, self::COLOR_INK);
        $content .= $this->drawText($x + 14, $yTop - 56, 'F1', 10, 'Purchase Reference: ' . $purchaseReference, self::COLOR_INK);
        $content .= $this->drawText($x + 14, $yTop - 72, 'F1', 10, 'Issued: ' . $issuedAt, self::COLOR_MUTED);

        $pillX = $x + $width - 154;
        $pillY = $y + 24;
        $content .= $this->drawFillRect($pillX, $pillY, 140, 58, [0.996, 0.930, 0.945]);
        $content .= $this->drawStrokeRect($pillX, $pillY, 140, 58, [0.953, 0.737, 0.790], 1);
        $content .= $this->drawText($pillX + 12, $pillY + 37, 'F1', 9, 'Tickets Purchased', self::COLOR_BRAND_DARK);
        $content .= $this->drawText($pillX + 12, $pillY + 18, 'F2', 16, (string) $ticketCount, self::COLOR_BRAND);

        return $content;
    }

    private function drawSectionTitle(float $x, float $y, string $title): string
    {
        $content = '';
        $content .= $this->drawText($x, $y, 'F2', 12, $title, self::COLOR_INK);
        $content .= $this->drawFillRect($x, $y - 8, 210, 2, self::COLOR_LINE);
        return $content;
    }

    private function drawTicketCard(float $x, float $yTop, int $ticketNumber, array $ticket): string
    {
        $width = 523;
        $height = 122;
        $y = $yTop - $height;

        $eventName = (string) ($ticket['event_name'] ?? 'Event');
        $eventDate = (string) ($ticket['event_date'] ?? 'Unknown date');
        $eventTime = (string) ($ticket['event_time'] ?? 'Unknown time');
        $venue = (string) ($ticket['venue'] ?? 'Unknown venue');
        $ticketId = (int) ($ticket['ticket_id'] ?? 0);
        $verificationCode = (string) ($ticket['verification_code'] ?? '');

        $content = '';
        $content .= $this->drawFillRect($x, $y, $width, $height, self::COLOR_PAPER);
        $content .= $this->drawStrokeRect($x, $y, $width, $height, self::COLOR_LINE, 1);

        // Brand rail on the left, matching site accent behavior.
        $content .= $this->drawFillRect($x, $y, 5, $height, self::COLOR_BRAND);

        $content .= $this->drawText($x + 14, $yTop - 18, 'F2', 11, 'Ticket #' . $ticketNumber, self::COLOR_BRAND_DARK);
        $content .= $this->drawText($x + 120, $yTop - 18, 'F1', 10, 'Number: ' . ($ticketId > 0 ? ('T-' . $ticketId) : 'TBD'), self::COLOR_MUTED);

        $nameLines = PdfTextSanitizer::wrap($eventName, 40);
        $nameLines = array_slice($nameLines, 0, 2);

        $nameY = $yTop - 38;
        foreach ($nameLines as $line) {
            $content .= $this->drawText($x + 14, $nameY, 'F2', 13, $line, self::COLOR_INK);
            $nameY -= 15;
        }

        $detailsBaseY = $yTop - 74;
        if (count($nameLines) > 1) {
            $detailsBaseY -= 14;
        }

        $content .= $this->drawText($x + 14, $detailsBaseY, 'F1', 10, 'Date: ' . $eventDate . ' | Time: ' . $eventTime, self::COLOR_MUTED);
        $content .= $this->drawText($x + 14, $detailsBaseY - 16, 'F1', 10, 'Venue: ' . $venue, self::COLOR_MUTED);

        // Verification badge uses site brand color family, not jazz palette.
        $badgeX = $x + 356;
        $badgeY = $y + 20;
        $content .= $this->drawFillRect($badgeX, $badgeY, 152, 74, [0.996, 0.930, 0.945]);
        $content .= $this->drawStrokeRect($badgeX, $badgeY, 152, 74, [0.953, 0.737, 0.790], 1);
        $content .= $this->drawText($badgeX + 10, $badgeY + 52, 'F2', 9, 'Verification Code', self::COLOR_BRAND_DARK);

        $codeLines = PdfTextSanitizer::wrap($verificationCode, 16);
        $codeY = $badgeY + 34;
        foreach (array_slice($codeLines, 0, 2) as $line) {
            $content .= $this->drawText($badgeX + 10, $codeY, 'F2', 12, $line, self::COLOR_BRAND);
            $codeY -= 14;
        }

        return $content;
    }

    private function drawFooter(string $terms): string
    {
        $content = '';

        $content .= $this->drawFillRect(24, 82, 547, 1, self::COLOR_LINE);
        $content .= $this->drawText(36, 66, 'F2', 10, 'Ticket Terms & Conditions', self::COLOR_INK);

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

    public function formatEventDate(string $startTime): string
    {
        $date = new DateTimeImmutable($startTime);
        return $date->format('D j M Y');
    }

    public function formatEventTime(string $startTime, string $endTime): string
    {
        $start = new DateTimeImmutable($startTime);
        $end = new DateTimeImmutable($endTime);
        return $start->format('H:i') . ' - ' . $end->format('H:i');
    }
}
