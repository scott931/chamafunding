<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Financial Summary Report - {{ config('app.name') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #333;
            margin: 20px;
        }
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        td {
            text-align: right;
        }
        td:first-child, th:first-child {
            text-align: left;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tfoot {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Financial Summary Report</h1>
        <p>{{ config('app.name') }} - Period: {{ $data['start_date'] }} to {{ $data['end_date'] }}</p>
        <p>Fee Structure: {{ $data['platform_fee_percentage'] }}% + ${{ number_format($data['platform_fee_fixed'], 2) }} per transaction</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Project Name</th>
                <th>Project ID</th>
                <th>Status</th>
                <th>Gross Pledges</th>
                <th>Platform Fees</th>
                <th>Payout Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['financial_data'] as $item)
                <tr>
                    <td>{{ $item['campaign']->title }}</td>
                    <td>{{ $item['campaign']->id }}</td>
                    <td>{{ ucfirst($item['campaign']->status) }}</td>
                    <td>${{ number_format($item['gross_pledges'], 2) }}</td>
                    <td>${{ number_format($item['platform_fees'], 2) }}</td>
                    <td>${{ number_format($item['payout_amount'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"><strong>TOTAL</strong></td>
                <td><strong>${{ number_format($data['total_gross'], 2) }}</strong></td>
                <td><strong>${{ number_format($data['total_fees'], 2) }}</strong></td>
                <td><strong>${{ number_format($data['total_payouts'], 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Generated: {{ $data['generated_at']->format('F j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>

