<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Platform Overview Dashboard - {{ config('app.name') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
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
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #666;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .stat-box {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }
        .stat-box h3 {
            margin: 0 0 10px 0;
            font-size: 12px;
            color: #666;
        }
        .stat-box p {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Platform Overview Dashboard</h1>
        <p>{{ config('app.name') }} - Report Generated: {{ $data['generated_at']->format('Y-m-d H:i:s') }}</p>
    </div>

    <div class="stats-grid">
        <div class="stat-box">
            <h3>Total Money Pledged (All Time)</h3>
            <p>${{ number_format($data['total_pledged_all_time'], 2) }}</p>
        </div>
        <div class="stat-box">
            <h3>Total Money Pledged (This Month)</h3>
            <p>${{ number_format($data['total_pledged_this_month'], 2) }}</p>
        </div>
        <div class="stat-box">
            <h3>Active Projects</h3>
            <p>{{ $data['active_projects'] }}</p>
        </div>
        <div class="stat-box">
            <h3>Successful Projects</h3>
            <p>{{ $data['successful_projects'] }}</p>
        </div>
        <div class="stat-box">
            <h3>Platform Fees (All Time)</h3>
            <p>${{ number_format($data['platform_fees_all_time'], 2) }}</p>
        </div>
        <div class="stat-box">
            <h3>Platform Fees (This Month)</h3>
            <p>${{ number_format($data['platform_fees_this_month'], 2) }}</p>
        </div>
        <div class="stat-box">
            <h3>New User Registrations (This Week)</h3>
            <p>{{ $data['new_users_this_week'] }}</p>
        </div>
    </div>

    <div class="footer">
        <p>This report was generated on {{ $data['generated_at']->format('F j, Y \a\t g:i A') }} by {{ config('app.name') }}</p>
    </div>
</body>
</html>

