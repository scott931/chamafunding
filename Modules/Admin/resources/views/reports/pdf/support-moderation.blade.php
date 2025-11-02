<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Support & Moderation Report - {{ config('app.name') }}</title>
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
        .section {
            margin: 30px 0;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            padding: 5px;
            background-color: #f0f0f0;
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
        <h1>Support & Moderation Report</h1>
        <p>{{ config('app.name') }} - Generated: {{ $data['generated_at']->format('Y-m-d H:i:s') }}</p>
    </div>

    <div class="section">
        <div class="section-title">Projects Pending Review</div>
        <table>
            <thead>
                <tr>
                    <th>Project ID</th>
                    <th>Project Name</th>
                    <th>Creator Name</th>
                    <th>Creator Email</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['pending_projects'] as $project)
                    <tr>
                        <td>{{ $project->id }}</td>
                        <td>{{ $project->title }}</td>
                        <td>{{ $project->creator->name ?? 'N/A' }}</td>
                        <td>{{ $project->creator->email ?? 'N/A' }}</td>
                        <td>{{ $project->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center;">No projects pending review</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Flagged Projects</div>
        <table>
            <thead>
                <tr>
                    <th>Project ID</th>
                    <th>Project Name</th>
                    <th>Creator Name</th>
                    <th>Creator Email</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['flagged_projects'] as $project)
                    <tr>
                        <td>{{ $project->id }}</td>
                        <td>{{ $project->title }}</td>
                        <td>{{ $project->creator->name ?? 'N/A' }}</td>
                        <td>{{ $project->creator->email ?? 'N/A' }}</td>
                        <td>Multiple contributions from same user</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center;">No flagged projects</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Suspicious Activity</div>
        <table>
            <thead>
                <tr>
                    <th>Project ID</th>
                    <th>Project Name</th>
                    <th>Creator Name</th>
                    <th>Creator Email</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['suspicious_campaigns'] as $project)
                    <tr>
                        <td>{{ $project->id }}</td>
                        <td>{{ $project->title }}</td>
                        <td>{{ $project->creator->name ?? 'N/A' }}</td>
                        <td>{{ $project->creator->email ?? 'N/A' }}</td>
                        <td>Many small contributions detected</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center;">No suspicious activity detected</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Generated: {{ $data['generated_at']->format('F j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>

