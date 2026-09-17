<?php

namespace App\Support;

use App\Models\WalkInEntry;
use Illuminate\Support\Str;

class QueueTicketPdf
{
    public function generate(WalkInEntry $ticket): string
    {
        $storeName = $this->text((string) $ticket->technician?->name.' Service Store');
        $queueNumber = $this->text((string) $ticket->queue_number);
        $reference = $this->text((string) $ticket->reference);
        $service = $this->text(Str::headline((string) $ticket->service_type));
        $issuedAt = $this->text($ticket->checked_in_at?->timezone('Asia/Manila')->format('F j, Y g:i A') ?? 'Not available');
        $addressLines = $this->wrappedLines(
            (string) $ticket->technician?->technicianVerification?->address,
            52,
            2,
        );

        $content = implode("\n", [
            '0.973 0.969 1 rg 0 0 420 595 re f',
            '1 1 1 rg 28 42 364 493 re f',
            '0.365 0.149 0.929 rg 28 475 364 60 re f',
            '0.91 0.89 0.98 RG 1 w 28 42 364 493 re S',
            $this->textCommand('FixTrack', 48, 507, 18, 'F2', '1 1 1'),
            $this->textCommand('WALK-IN QUEUE TICKET', 281, 505, 8, 'F2', '0.91 0.88 1'),
            $this->textCommand('Your Queue Number', 0, 441, 11, 'F1', '0.39 0.39 0.46', true),
            $this->textCommand($queueNumber, 0, 389, 38, 'F2', '0.365 0.149 0.929', true),
            '0.91 0.89 0.98 RG 1 w 56 362 m 364 362 l S',
            $this->textCommand('SERVICE STORE', 56, 332, 8, 'F2', '0.45 0.43 0.52'),
            $this->textCommand($storeName, 56, 311, 13, 'F2', '0.12 0.11 0.16'),
            $this->textCommand('SERVICE', 56, 278, 8, 'F2', '0.45 0.43 0.52'),
            $this->textCommand($service, 56, 258, 11, 'F1', '0.12 0.11 0.16'),
            $this->textCommand('REFERENCE NUMBER', 56, 225, 8, 'F2', '0.45 0.43 0.52'),
            $this->textCommand($reference, 56, 205, 11, 'F1', '0.12 0.11 0.16'),
            $this->textCommand('SHOP ADDRESS', 56, 172, 8, 'F2', '0.45 0.43 0.52'),
            $this->textCommand($addressLines[0] ?? 'Not available', 56, 152, 10, 'F1', '0.12 0.11 0.16'),
            $this->textCommand($addressLines[1] ?? '', 56, 137, 10, 'F1', '0.12 0.11 0.16'),
            $this->textCommand('ISSUED ON', 56, 105, 8, 'F2', '0.45 0.43 0.52'),
            $this->textCommand($issuedAt, 56, 85, 10, 'F1', '0.12 0.11 0.16'),
            $this->textCommand('Please keep this ticket and wait for your number to be called.', 0, 59, 8, 'F1', '0.45 0.43 0.52', true),
        ]);

        return $this->document($content);
    }

    private function document(string $content): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 420 595] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
            6 => '<< /Length '.strlen($content)." >>\nstream\n{$content}\nendstream",
        ];

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];

        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 7\n0000000000 65535 f \n";

        for ($number = 1; $number <= 6; $number++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }

        return $pdf."trailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";
    }

    private function textCommand(
        string $text,
        float $x,
        float $y,
        float $size,
        string $font,
        string $color,
        bool $centered = false,
    ): string {
        if ($text === '') {
            return '';
        }

        if ($centered) {
            $estimatedWidth = strlen($text) * $size * 0.52;
            $x = max(28, (420 - $estimatedWidth) / 2);
        }

        return sprintf(
            'BT /%s %.2F Tf %s rg %.2F %.2F Td (%s) Tj ET',
            $font,
            $size,
            $color,
            $x,
            $y,
            $this->escape($text),
        );
    }

    /** @return list<string> */
    private function wrappedLines(string $text, int $width, int $limit): array
    {
        $lines = explode("\n", wordwrap($this->text($text), $width, "\n", true));

        return array_slice(array_values(array_filter($lines)), 0, $limit);
    }

    private function text(string $text): string
    {
        return trim(Str::ascii($text));
    }

    private function escape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
