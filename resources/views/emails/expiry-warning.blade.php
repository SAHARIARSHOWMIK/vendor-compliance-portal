<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Document Expiry Warning</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #1e293b; margin: 0; padding: 0; background: #f8fafc; }
        .container { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08); overflow: hidden; }
        .header { padding: 24px 32px; color: #fff; }
        .header-urgent { background: #dc2626; }
        .header-warning { background: #d97706; }
        .header-info { background: #0f172a; }
        .header h1 { margin: 0; font-size: 18px; font-weight: 600; }
        .body { padding: 32px; }
        .countdown { font-size: 36px; font-weight: 700; text-align: center; padding: 24px; border-radius: 8px; margin: 20px 0; }
        .countdown-urgent { background: #fee2e2; color: #dc2626; }
        .countdown-warning { background: #fef3c7; color: #d97706; }
        .countdown-info { background: #dbeafe; color: #1e40af; }
        .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #0f172a; color: #fff; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 500; }
        .footer { padding: 16px 32px; background: #f8fafc; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        @php
            $isExpired = $daysLeft <= 0;
            $isUrgent  = $daysLeft <= 7 && $daysLeft > 0;
            $headerClass    = $isExpired || $isUrgent ? 'header-urgent' : ($daysLeft <= 30 ? 'header-warning' : 'header-info');
            $countdownClass = $isExpired || $isUrgent ? 'countdown-urgent' : ($daysLeft <= 30 ? 'countdown-warning' : 'countdown-info');
        @endphp
        <div class="header {{ $headerClass }}">
            <h1>{{ $isExpired ? 'Document Expired' : 'Document Expiry Warning' }}</h1>
        </div>
        <div class="body">
            <p>Hello,</p>
            @if ($isExpired)
                <p>The following compliance document for <strong>{{ $document->vendor->name }}</strong> has <strong>expired</strong> and must be renewed immediately to maintain compliance.</p>
            @else
                <p>The following compliance document for <strong>{{ $document->vendor->name }}</strong> is expiring soon and requires your attention.</p>
            @endif

            <div class="countdown {{ $countdownClass }}">
                @if ($isExpired)
                    EXPIRED
                @else
                    {{ $daysLeft }} day{{ $daysLeft === 1 ? '' : 's' }} remaining
                @endif
            </div>

            <table style="width:100%;font-size:14px;border-collapse:collapse;margin-bottom:16px;">
                <tr>
                    <td style="padding:6px 0;color:#64748b;width:40%">Document</td>
                    <td style="padding:6px 0;font-weight:500">{{ $document->documentType->name }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#64748b">Vendor</td>
                    <td style="padding:6px 0">{{ $document->vendor->name }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#64748b">Expiry date</td>
                    <td style="padding:6px 0;{{ $isExpired ? 'color:#dc2626;font-weight:600' : '' }}">{{ $document->expiry_date?->format('d M Y') }}</td>
                </tr>
            </table>

            <p style="font-size:14px;color:#475569;">
                Please log in to the vendor portal to upload a renewed document as soon as possible.
                @if ($isExpired)
                    Your vendor account may be flagged as non-compliant until this document is renewed and approved.
                @endif
            </p>

            <a href="{{ config('app.url') }}/vendor-portal/checklist" class="btn">Upload Renewed Document</a>
        </div>
        <div class="footer">
            This is an automated message from the Vendor Compliance Portal. Please do not reply to this email.
        </div>
    </div>
</body>
</html>
