<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>All Projects Report - {{ config('app.name') }}</title>
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
        <h1>All Projects Report</h1>
        <p>{{ config('app.name') }} - Generated: {{ $data['generated_at']->format('Y-m-d H:i:s') }}</p>
        @if(!empty($data['filters']))
            <p>Filters: {{ implode(', ', array_filter($data['filters'])) }}</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Project Name</th>
                <th>Creator</th>
                <th>Email</th>
                <th>Created</th>
                <th>Deadline</th>
                <th>Goal</th>
                <th>Pledged</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['projects'] as $project)
                <tr>
                    <td>{{ $project->id }}</td>
                    <td>{{ $project->title }}</td>
                    <td>{{ $project->creator->name ?? 'N/A' }}</td>
                    <td>{{ $project->creator->email ?? 'N/A' }}</td>
                    <td>{{ $project->created_at->format('Y-m-d') }}</td>
                    <td>{{ $project->deadline ? $project->deadline->format('Y-m-d') : 'N/A' }}</td>
                    <td>${{ number_format($project->goal_amount / 100, 2) }}</td>
                    <td>${{ number_format($project->raised_amount / 100, 2) }}</td>
                    <td>{{ ucfirst($project->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Total Projects: {{ $data['projects']->count() }} | Generated: {{ $data['generated_at']->format('F j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>

