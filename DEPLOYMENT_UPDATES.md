# Deployment Configuration Updates

This document summarizes the changes made to prepare the Laravel + Next.js application for Render deployment.

## Changes Made

### 1. Updated `scripts/start.sh`
**Purpose**: Run both Laravel API and Next.js frontend in the same container

**Changes**:
- Added logic to detect if Next.js frontend should run
- Start Laravel API server on port 8000 (background process)
- Start Next.js frontend server on Render's PORT (foreground process)
- Set environment variables for Next.js API routing
- Added fallback to Laravel-only mode if frontend not available

**Key Features**:
- Laravel API runs on internal port 8000
- Next.js frontend runs on external PORT (Render's port)
- Proper error handling and process verification
- Environment variable configuration for API routing

### 2. Updated `render.yaml`
**Purpose**: Configure environment variables for Render deployment

**Changes**:
- Added `NEXT_PUBLIC_API_URL` set to `/api` (relative URL for client-side)
- Added `NEXT_PUBLIC_API_URL_INTERNAL` set to `http://localhost:8000/api` (for server-side proxy)
- Added `RUN_FRONTEND` set to `true` (enable frontend)
- Added `LARAVEL_API_PORT` set to `8000` (internal API port)

### 3. Updated `frontend/next.config.js`
**Purpose**: Configure Next.js to properly proxy API requests to Laravel

**Changes**:
- Updated `rewrites()` to use `NEXT_PUBLIC_API_URL_INTERNAL` for server-side proxying
- Maintains support for both internal and external API URLs
- Ensures API requests are properly routed to Laravel backend

## Architecture

### Service Flow
```
┌─────────────────────────────────────────┐
│         Render Container                │
│                                         │
│  ┌─────────────────────────────────┐   │
│  │   Next.js Frontend (PORT)       │   │
│  │   - Serves React App            │   │
│  │   - Proxies /api/* → Laravel    │   │
│  └──────────────┬──────────────────┘   │
│                 │                       │
│                 │ /api/* requests       │
│                 │                       │
│  ┌──────────────▼──────────────────┐   │
│  │   Laravel API (8000)            │   │
│  │   - Handles API requests        │   │
│  │   - Database connections        │   │
│  │   - Authentication              │   │
│  └─────────────────────────────────┘   │
└─────────────────────────────────────────┘
```

### Request Flow
1. **Client Request** → Next.js (PORT)
2. **API Request** (`/api/*`) → Next.js rewrites → Laravel (8000)
3. **Response** → Laravel → Next.js → Client

## Environment Variables

### Required Variables
- `APP_KEY` - Laravel encryption key
- `APP_URL` - Public URL of application
- `NEXT_PUBLIC_API_URL` - Client-side API URL (`/api`)
- `NEXT_PUBLIC_API_URL_INTERNAL` - Server-side API URL (`http://localhost:8000/api`)
- `RUN_FRONTEND` - Enable frontend (`true`)
- `LARAVEL_API_PORT` - Laravel API port (`8000`)
- Database variables (from Render database service)

## Deployment Process

1. **Build Phase** (Dockerfile):
   - Install PHP dependencies (Composer)
   - Build Laravel assets (Vite)
   - Build Next.js frontend (`npm run build`)
   - Configure permissions

2. **Start Phase** (start.sh):
   - Verify environment variables
   - Set up cache directories
   - Run database migrations
   - Start Laravel API (background)
   - Start Next.js frontend (foreground)

## Verification

After deployment, verify:
- ✅ Health check endpoint: `https://your-app.onrender.com/up`
- ✅ Frontend loads: `https://your-app.onrender.com`
- ✅ API endpoints work: Check Network tab for `/api/*` requests
- ✅ Database migrations completed: Check logs
- ✅ Both services running: Check process logs

## Troubleshooting

### Frontend Not Loading
- Check that `frontend/.next` directory exists after build
- Verify PORT environment variable is set
- Check Next.js startup logs

### API Not Working
- Verify Laravel is running on port 8000
- Check `NEXT_PUBLIC_API_URL_INTERNAL` is set correctly
- Verify Next.js rewrites configuration
- Check Laravel API logs

### Database Issues
- Verify database service is running
- Check database environment variables
- Verify migrations completed successfully

## Notes

- Next.js uses standalone mode for optimal production performance
- Laravel API runs on internal port (not exposed externally)
- All API requests go through Next.js proxy for CORS and routing
- Health check uses Laravel's `/up` endpoint

