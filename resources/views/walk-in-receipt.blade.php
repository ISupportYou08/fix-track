<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Walk-in receipt {{ $entry->reference }}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f5; color: #18181b; margin: 0; padding: 2rem; }
        main { max-width: 40rem; margin: auto; background: white; padding: 2rem; border: 1px solid #d4d4d8; border-radius: 1rem; }
        header, .row { display: flex; justify-content: space-between; gap: 1rem; }
        header { border-bottom: 2px solid #18181b; padding-bottom: 1rem; }
        h1 { margin: 0; font-size: 1.5rem; }
        .muted { color: #71717a; }
        .row { padding: .75rem 0; border-bottom: 1px solid #e4e4e7; }
        .total { font-size: 1.3rem; font-weight: bold; }
        button { margin-top: 1.5rem; padding: .7rem 1.2rem; background: #18181b; color: white; border: 0; border-radius: .5rem; cursor: pointer; }
        @media print { body { background: white; padding: 0; } main { border: 0; padding: 0; } button { display: none; } }
    </style>
</head>
<body>
    <main>
        <header><div><h1>FixTrack</h1><div class="muted">Walk-in cash receipt</div></div><strong>{{ $entry->reference }}</strong></header>
        <div class="row"><span>Queue ticket</span><strong>{{ $entry->queue_number }}</strong></div>
        <div class="row"><span>Customer</span><strong>{{ $entry->customer_name }}</strong></div>
        <div class="row"><span>Shop / technician</span><strong>{{ $entry->technician?->name ?: 'Service shop' }}</strong></div>
        <div class="row"><span>Service</span><strong>{{ \Illuminate\Support\Str::headline($entry->service_type) }}</strong></div>
        <div class="row"><span>Paid on</span><strong>{{ $entry->payment->paid_at?->timezone('Asia/Manila')->format('M d, Y · g:i A') }}</strong></div>
        <div class="row"><span>Payment method</span><strong>Cash</strong></div>
        <div class="row total"><span>Total paid</span><span>PHP {{ number_format((float) $entry->payment->amount, 2) }}</span></div>
        <p class="muted">Received by {{ $entry->payment->receiver?->name ?: 'FixTrack' }}. Keep this receipt for your records.</p>
        <button type="button" onclick="window.print()">Print receipt</button>
    </main>
</body>
</html>
