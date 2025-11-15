# Kubernetes Deployment Guide

This directory contains Kubernetes manifests for deploying the Laravel Crowdfunding application.

## Files Overview

- `deployment.yaml` - Main application deployment with versioning
- `configmap.yaml` - Non-sensitive configuration
- `secrets.yaml` - Sensitive data (templates - never commit real secrets!)
- `persistent-volumes.yaml` - Storage claims for persistent data
- `mysql-deployment.yaml` - MySQL database deployment
- `redis-deployment.yaml` - Redis cache/queue deployment
- `ingress.yaml` - Ingress configuration for external access

## Versioning Strategy

### Image Tags
Update the image tag in `deployment.yaml` for each release:
```yaml
image: myregistry/laravel-app:v1.2.3
```

### Asset Versioning
Update `ASSET_VERSION` in `configmap.yaml` to bust browser cache:
```yaml
ASSET_VERSION: "123456789"
```

## Deployment Steps

### 1. Build and Push Docker Image
```bash
# Build with version tag
docker build -t myregistry/laravel-app:v1.2.3 .

# Push to registry
docker push myregistry/laravel-app:v1.2.3
```

### 2. Update Secrets
**IMPORTANT**: Never commit real secrets. Use one of these approaches:
- Use `kubectl create secret` directly
- Use Sealed Secrets operator
- Use External Secrets operator
- Use a secrets management system (HashiCorp Vault, AWS Secrets Manager, etc.)

```bash
# Create secrets manually
kubectl create secret generic laravel-secrets \
  --from-literal=APP_KEY='base64:...' \
  --from-literal=DB_PASSWORD='secure-password'
```

### 3. Apply Configurations
```bash
# Apply in order
kubectl apply -f persistent-volumes.yaml
kubectl apply -f secrets.yaml  # Or use your secrets management
kubectl apply -f configmap.yaml
kubectl apply -f mysql-deployment.yaml
kubectl apply -f redis-deployment.yaml
kubectl apply -f deployment.yaml
kubectl apply -f ingress.yaml
```

### 4. Rolling Update
When deploying a new version:
```bash
# Update image tag in deployment.yaml, then:
kubectl set image deployment/laravel-app app=myregistry/laravel-app:v1.2.4
kubectl rollout status deployment/laravel-app
```

## Environment-Specific Deployments

### Development
- Use `imagePullPolicy: IfNotPresent`
- Enable `APP_DEBUG: "true"` in configmap
- Use smaller resource limits

### Production
- Use `imagePullPolicy: Always`
- Disable `APP_DEBUG: "false"`
- Use appropriate resource limits
- Enable ingress with TLS
- Use persistent volumes for storage

## Monitoring

Check deployment status:
```bash
kubectl get deployments
kubectl get pods
kubectl describe deployment laravel-app
kubectl logs -f deployment/laravel-app
```

## Rollback

If something goes wrong:
```bash
kubectl rollout undo deployment/laravel-app
kubectl rollout history deployment/laravel-app
```

