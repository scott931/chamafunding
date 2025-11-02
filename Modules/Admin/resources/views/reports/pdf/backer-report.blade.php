<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Backer Report - {{ config('app.name') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
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
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
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
        <h1>Detailed Backer Report</h1>
        <p>{{ config('app.name') }} - Generated: {{ $data['generated_at']->format('Y-m-d H:i:s') }}</p>
        @if(!empty($data['filters']))
            <p>Filters: {{ implode(', ', array_filter($data['filters'])) }}</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Backer Name</th>
                <th>Backer Email</th>
                <th>Project Name</th>
                <th>Project ID</th>
                <th>Pledge Amount</th>
                <th>Reward Tier</th>
                <th>Pledge Date</th>
                <th>Payment Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['backer_data'] as $backer)
                <tr>
                    <td>{{ $backer['backer_name'] }}</td>
                    <td>{{ $backer['backer_email'] }}</td>
                    <td>{{ $backer['project_name'] }}</td>
                    <td>{{ $backer['project_id'] }}</td>
                    <td>${{ number_format($backer['pledge_amount'], 2) }}</td>
                    <td>{{ $backer['reward_tier'] }}</td>
                    <td>{{ $backer['pledge_date']->format('Y-m-d H:i') }}</td>
                    <td>{{ $backer['payment_status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Total Backers: {{ count($data['backer_data']) }} | Generated: {{ $data['generated_at']->format('F j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>

