# Render Deployment Checklist for ChamaFunding

This checklist verifies that your Laravel + Next.js application is ready for deployment to Render.

## ✅ Pre-Deployment Checklist

### 1. Project Structure
- [x] Laravel backend in root directory
- [x] Next.js frontend in `frontend/` directory
- [x] `render.yaml` configured
- [x] `Dockerfile` present and updated
- [x] `scripts/start.sh` updated to run both services

### 2. Build Configuration

#### Dockerfile
- [x] Builds Laravel dependencies (Composer)
- [x] Builds Laravel assets (Vite)
- [x] Builds Next.js frontend (`npm run build` in `frontend/`)
- [x] Next.js configured with `output: 'standalone'`

#### Next.js Configuration (`frontend/next.config.js`)
- [x] `output: 'standalone'` enabled
- [x] API rewrites configured to proxy to Laravel
- [x] Environment variables configured

### 3. Environment Variables (render.yaml)

#### Required Variables
- [x] `APP_KEY` - Laravel encryption key
- [x] `APP_URL` - Public URL of your application
- [x] `NEXT_PUBLIC_API_URL` - Set to `/api` (relative URL)
- [x] `NEXT_PUBLIC_API_URL_INTERNAL` - Set to `http://localhost:8000/api`
- [x] `RUN_FRONTEND` - Set to `true`
- [x] `LARAVEL_API_PORT` - Set to `8000`
- [x] Database connection variables (from database service)

#### Database Configuration
- [x] `DB_CONNECTION` - `pgsql`
- [x] `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` - From database service

### 4. Startup Script (`scripts/start.sh`)

The script should:
- [x] Verify `APP_KEY` is set
- [x] Set up cache directories
- [x] Run database migrations
- [x] Start Laravel API on port 8000 (background)
- [x] Start Next.js frontend on Render's PORT (foreground)
- [x] Set environment variables for Next.js

### 5. Service Architecture

#### Web Service
- **Type**: Web Service (Docker)
- **Port**: Uses Render's PORT environment variable
- **Start Command**: `scripts/start.sh` (via entrypoint.sh)
- **Health Check**: `/up` endpoint

#### Services Running in Container
1. **Laravel API** - Port 8000 (internal)
   - Handles all `/api/*` requests
   - Database connections
   - Authentication (Sanctum)

2. **Next.js Frontend** - Port $PORT (external)
   - Serves React application
   - Proxies `/api/*` requests to Laravel
   - Handles client-side routing

### 6. API Routing Flow

```
Client Request → Next.js (PORT) → /api/* → Rewrite Proxy → Laravel (8000) → Response
```

- Client-side requests: Browser → Next.js → Laravel (via rewrite)
- Server-side requests: Next.js SSR → Laravel (via rewrite)

## 🚀 Deployment Steps

1. **Push to Repository**
   ```bash
   git add .
   git commit -m "Ready for Render deployment"
   git push origin main
   ```

2. **Deploy on Render**
   - Render will automatically detect `render.yaml`
   - It will build the Docker image
   - Start the web service

3. **Monitor Deployment**
   - Check build logs for errors
   - Verify both services start correctly
   - Check health check endpoint

## 🔍 Post-Deployment Verification

### Health Checks
- [ ] Visit `https://your-app.onrender.com/up` - Should return 200 OK
- [ ] Visit `https://your-app.onrender.com` - Should show Next.js frontend
- [ ] Check browser console for errors

### API Endpoints
- [ ] Test API endpoint: `https://your-app.onrender.com/api/health` (if exists)
- [ ] Verify API responses include proper CORS headers
- [ ] Test authentication endpoints

### Frontend Functionality
- [ ] Frontend loads correctly
- [ ] API calls work (check Network tab)
- [ ] Client-side routing works
- [ ] Authentication flow works

### Database
- [ ] Migrations ran successfully (check logs)
- [ ] Database connections work
- [ ] Can create/read data

## 🐛 Troubleshooting

### Build Fails
- Check Dockerfile syntax
- Verify all dependencies are in `package.json`/`composer.json`
- Check build logs for specific errors

### Frontend Not Loading
- Verify Next.js build completed successfully
- Check that `frontend/.next` directory exists
- Verify PORT environment variable is set
- Check startup logs for Next.js errors

### API Not Working
- Verify Laravel API is running on port 8000
- Check `NEXT_PUBLIC_API_URL_INTERNAL` is set correctly
- Verify Next.js rewrites are configured
- Check Laravel logs for API errors

### Database Connection Issues
- Verify database service is running
- Check database environment variables
- Verify migrations completed
- Check database connection in logs

### Port Conflicts
- Ensure Laravel uses port 8000 (internal)
- Ensure Next.js uses PORT env var (external)
- Check no other services use these ports

## 📝 Environment Variable Reference

### Required for Web Service
```yaml
APP_KEY: base64:... (Laravel encryption key)
APP_URL: https://your-app.onrender.com
NEXT_PUBLIC_API_URL: /api
NEXT_PUBLIC_API_URL_INTERNAL: http://localhost:8000/api
RUN_FRONTEND: true
LARAVEL_API_PORT: 8000
DB_CONNECTION: pgsql
DB_HOST: (from database service)
DB_PORT: (from database service)
DB_DATABASE: (from database service)
DB_USERNAME: (from database service)
DB_PASSWORD: (from database service)
```

## ✅ Deployment Ready

Your application is ready for deployment if:
- ✅ All checklist items are verified
- ✅ Environment variables are set in `render.yaml`
- ✅ Both Laravel and Next.js build successfully
- ✅ Startup script runs both services
- ✅ Health check endpoint responds

## 📚 Additional Resources

- [Render Docker Documentation](https://render.com/docs/docker)
- [Next.js Standalone Mode](https://nextjs.org/docs/advanced-features/output-file-tracing)
- [Laravel Deployment Guide](https://laravel.com/docs/deployment)

