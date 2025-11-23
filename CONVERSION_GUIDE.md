# Laravel to Next.js Conversion Guide

This project has been converted from a Laravel Blade-based frontend to a Next.js React frontend while maintaining all backend functionality.

## What Changed

### Frontend
- **Before**: Blade templates with Alpine.js
- **After**: Next.js with React and TypeScript
- **Location**: `/frontend` directory

### Backend
- **Status**: Unchanged - Laravel API continues to work as before
- **Routes**: Web routes now serve API only (frontend routes removed)
- **API**: All API endpoints remain functional

## Project Structure

```
chamafunding/
├── frontend/              # Next.js application
│   ├── app/              # Next.js app directory
│   ├── components/       # React components
│   ├── lib/              # API clients and utilities
│   └── package.json      # Next.js dependencies
├── app/                  # Laravel application (unchanged)
├── routes/
│   ├── api.php          # API routes (unchanged)
│   └── web.php          # Updated to API-only
└── render.yaml           # Unchanged - deployment config
```

## Development

### Running Both Services

1. **Laravel Backend** (port 8000):
```bash
php artisan serve
```

2. **Next.js Frontend** (port 3000):
```bash
cd frontend
npm install
npm run dev
```

Or use the combined script:
```bash
npm run dev:all
```

### Environment Variables

Create `frontend/.env.local`:
```
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

## Deployment

### Render.com

The `render.yaml` file is **unchanged** and will continue to work. The Dockerfile has been updated to:
1. Build Laravel assets (as before)
2. Build Next.js frontend (new)
3. Serve both from the same container

### Docker Build

The Dockerfile now:
- Builds Next.js during the Docker build process
- Serves the application via Apache (as before)
- Next.js static files are included in the build

## API Integration

All API calls go through the API client in `frontend/lib/api/client.ts`:
- Automatically handles CSRF tokens
- Manages authentication tokens
- Handles errors and redirects

## Key Features Preserved

✅ All authentication flows (login, register, password reset)
✅ Dashboard functionality
✅ Payment integrations (Stripe, PayPal, M-Pesa)
✅ Campaign management
✅ User management
✅ All API endpoints

## Migration Notes

- **CSRF Tokens**: Handled automatically by the API client
- **Authentication**: Uses cookies and Bearer tokens
- **Session Management**: Handled by Laravel backend
- **File Uploads**: Continue to work through API endpoints

## Next Steps

1. Install frontend dependencies: `cd frontend && npm install`
2. Test locally: Run both Laravel and Next.js
3. Deploy: Push to repository (Render will build automatically)

## Troubleshooting

### Frontend can't connect to API
- Check `NEXT_PUBLIC_API_URL` in `frontend/.env.local`
- Ensure Laravel backend is running on port 8000

### Build fails
- Ensure Node.js 18+ is installed
- Run `npm install` in both root and frontend directories

### CSRF token errors
- The API client handles CSRF automatically
- Ensure Laravel Sanctum is properly configured

