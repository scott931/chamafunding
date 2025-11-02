<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>User Management Report - {{ config('app.name') }}</title>
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
        <h1>User Management Report</h1>
        <p>{{ config('app.name') }} - Generated: {{ $data['generated_at']->format('Y-m-d H:i:s') }}</p>
        @if(!empty($data['filters']))
            <p>Filters: {{ implode(', ', array_filter($data['filters'])) }}</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>User ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Date Registered</th>
                <th>User Type</th>
                <th>Projects Created</th>
                <th>Total Pledged</th>
                <th>Account Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['user_data'] as $user)
                <tr>
                    <td>{{ $user['user_id'] }}</td>
                    <td>{{ $user['name'] }}</td>
                    <td>{{ $user['email'] }}</td>
                    <td>{{ $user['date_registered']->format('Y-m-d') }}</td>
                    <td>{{ $user['user_type'] }}</td>
                    <td>{{ $user['projects_created'] }}</td>
                    <td>${{ number_format($user['total_pledged'], 2) }}</td>
                    <td>{{ $user['account_status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Total Users: {{ count($data['user_data']) }} | Generated: {{ $data['generated_at']->format('F j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>

