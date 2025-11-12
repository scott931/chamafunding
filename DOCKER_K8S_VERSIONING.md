# Docker & Kubernetes Versioning Best Practices

This document outlines the versioning and deployment strategies implemented for the Crowdfunding application.

## Docker Layer Caching

### Problem
Docker caches layers, so code changes may not reflect if layers are cached incorrectly.

### Solution Implemented
The `Dockerfile` now uses strategic COPY commands:

```dockerfile
# Copy composer files first (cached unless composer.json/lock changes)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Copy application files (separate layer)
COPY . /var/www/html
```

**Benefits:**
- Composer dependencies are cached unless `composer.json` or `composer.lock` change
- Faster rebuilds when only code changes
- More efficient CI/CD pipelines

## Docker Compose

### Development (`docker-compose.yml`)
- **Volume mounts**: Live sync for immediate code changes
  ```yaml
  volumes:
    - .:/var/www/html  # Live sync
    - ./storage:/var/www/html/storage
    - /var/www/html/vendor  # Exclude vendor
  ```
- **Use case**: Local development with hot reloading

### Production (`docker-compose.prod.yml`)
- **No code volume mounts**: Immutable containers
- **Only storage volumes**: Persistent data only
- **Use case**: Production deployments where you want immutable containers

## Multi-Stage Builds

### `Dockerfile.multistage`
A production-optimized multi-stage build:

1. **Builder stage**: Installs dependencies, builds assets, caches config
2. **Runtime stage**: Minimal image with only runtime dependencies

**Benefits:**
- Smaller final image
- Optimized for production
- Pre-cached Laravel config and routes

**Note**: Changes won't reflect without rebuild (by design for production)

## Kubernetes Versioning

### Image Tags
Always use specific version tags in `k8s/deployment.yaml`:
```yaml
image: myregistry/laravel-app:v1.2.3
imagePullPolicy: Always  # or IfNotPresent for local
```

### Asset Versioning
Update `ASSET_VERSION` in `k8s/configmap.yaml` to bust browser cache:
```yaml
ASSET_VERSION: "123456789"
```

This works with the `asset_versioned()` helper in `AppServiceProvider.php`.

### Deployment Strategy
- **Rolling updates**: Zero-downtime deployments
- **Version labels**: Track versions in metadata
- **ConfigMaps**: Non-sensitive configuration
- **Secrets**: Sensitive data (use proper secrets management)

## Best Practices Summary

### Docker
1. ✅ Copy dependency files (`composer.json`, `package.json`) before installing
2. ✅ Use `.dockerignore` to exclude unnecessary files
3. ✅ Use multi-stage builds for production
4. ✅ Pin base image versions (e.g., `php:8.4-apache`)

### Docker Compose
1. ✅ Use volume mounts for development (live sync)
2. ✅ Avoid code volume mounts in production
3. ✅ Exclude `vendor` and `node_modules` from volume mounts
4. ✅ Use named volumes for persistent data

### Kubernetes
1. ✅ Always use specific image tags (never `latest`)
2. ✅ Use `imagePullPolicy: Always` in production
3. ✅ Store secrets separately (never in git)
4. ✅ Use ConfigMaps for non-sensitive config
5. ✅ Update `ASSET_VERSION` for cache busting
6. ✅ Use persistent volumes for storage
7. ✅ Implement health checks (liveness/readiness probes)

## Quick Reference

### Build Docker Image
```bash
# Standard build
docker build -t myregistry/laravel-app:v1.2.3 .

# Multi-stage build
docker build -f Dockerfile.multistage -t myregistry/laravel-app:v1.2.3 .
```

### Docker Compose
```bash
# Development
docker-compose up -d

# Production
docker-compose -f docker-compose.prod.yml up -d
```

### Kubernetes Deployment
```bash
# Apply all resources
kubectl apply -f k8s/

# Update image version
kubectl set image deployment/laravel-app app=myregistry/laravel-app:v1.2.4

# Check rollout status
kubectl rollout status deployment/laravel-app

# Rollback if needed
kubectl rollout undo deployment/laravel-app
```

## Version Management Workflow

1. **Update code** → Commit to git
2. **Build image** with new version tag: `v1.2.4`
3. **Push to registry**: `docker push myregistry/laravel-app:v1.2.4`
4. **Update deployment.yaml**: Change image tag to `v1.2.4`
5. **Update configmap.yaml**: Increment `ASSET_VERSION`
6. **Apply Kubernetes manifests**: `kubectl apply -f k8s/`
7. **Monitor rollout**: `kubectl rollout status deployment/laravel-app`

## Security Notes

- ⚠️ **Never commit real secrets** to version control
- ✅ Use Kubernetes Secrets or external secrets management
- ✅ Use `kubectl create secret` or Sealed Secrets operator
- ✅ Rotate secrets regularly
- ✅ Use least-privilege access for service accounts

