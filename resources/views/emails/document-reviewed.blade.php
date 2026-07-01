<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Document Review Update</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #1e293b; margin: 0; padding: 0; background: #f8fafc; }
        .container { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08); overflow: hidden; }
        .header { padding: 24px 32px; background: #0f172a; color: #fff; }
        .header h1 { margin: 0; font-size: 18px; font-weight: 600; }
        .body { padding: 32px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; margin-bottom: 16px; }
        .badge-approved { background: #dcfce7; color: #166534; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .badge-correction { background: #ffedd5; color: #9a3412; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .comment { background: #f8fafc; border-left: 3px solid #e2e8f0; padding: 12px 16px; border-radius: 4px; margin: 16px 0; font-size: 14px; color: #475569; }
        .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #0f172a; color: #fff; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 500; }
        .footer { padding: 16px 32px; background: #f8fafc; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Vendor Compliance Portal</h1>
        </div>
        <div class="body">
            <p>Hello,</p>
            <p>A review decision has been made on one of your compliance documents.</p>

            @php
                $badgeClass = match($review->decision) {
                    'approved'             => 'badge-approved',
                    'rejected'             => 'badge-rejected',
                    'correction_requested' => 'badge-correction',
                    default                => 'badge-info',
                };
                $decisionLabel = ucwords(str_replace('_', ' ', $review->decision));
            @endphp

            <span class="badge {{ $badgeClass }}">{{ $decisionLabel }}</span>

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
                    <td style="padding:6px 0;color:#64748b">Version</td>
                    <td style="padding:6px 0">v{{ $document->version_number }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#64748b">Reviewed by</td>
                    <td style="padding:6px 0">{{ $review->reviewer?->name }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#64748b">Date</td>
                    <td style="padding:6px 0">{{ $review->reviewed_at?->format('d M Y H:i') }}</td>
                </tr>
            </table>

            @if ($review->comment)
                <div class="comment">
                    <strong>Reviewer comment:</strong><br>
                    {{ $review->comment }}
                </div>
            @endif

            @if (in_array($review->decision, ['rejected', 'correction_requested']))
                <p style="font-size:14px;color:#dc2626;font-weight:500">
                    Action required: Please log in to the portal to view the comments and reupload a corrected document.
                </p>
            @endif

            <a href="{{ config('app.url') }}/vendor-portal/checklist" class="btn">View My Documents</a>
        </div>
        <div class="footer">
            This is an automated message from the Vendor Compliance Portal. Please do not reply to this email.
        </div>
    </div>
</body>
</html>
