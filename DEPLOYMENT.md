# Deployment Guide

This guide explains how to deploy the Globe Backend application using Deployer and Docker.

## Prerequisites

- Deployer installed locally: `composer require --dev deployer/deployer`
- SSH access to the staging/production server
- Docker and Docker Compose installed on the target server
- Git repository access from the target server

## Configuration Files

### deploy.yaml
Main deployment configuration file that defines:
- Repository location
- Deployment paths
- Docker Compose configuration
- Deployment tasks and hooks

### compose.stage.yaml
Staging-specific Docker Compose override file with:
- Staging ports (HTTP: 8080, HTTPS: 444)
- Staging container names
- Staging environment variables

### .env.stage
Environment variables for staging (should be copied to `.env.local` on the server)

## Deployment Commands

### Deploy to Staging
```bash
vendor/bin/dep deploy stage
```

### Deploy to Production
```bash
vendor/bin/dep deploy production
```

### Other Useful Commands

```bash
# Check deployment status
vendor/bin/dep deploy:info stage

# Rollback to previous release
vendor/bin/dep rollback stage

# SSH into the server
vendor/bin/dep ssh stage

# Run a specific task
vendor/bin/dep deploy:docker:build stage
vendor/bin/dep deploy:health:check stage

# View container logs on server
vendor/bin/dep run "cd {{release_or_current_path}} && docker compose -p globe-backend logs -f" stage
```

## First-Time Setup

1. **Prepare the server:**
   ```bash
   # Install Docker
   curl -fsSL https://get.docker.com | sh
   sudo usermod -aG docker $USER
   
   # Install Docker Compose
   sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
   sudo chmod +x /usr/local/bin/docker-compose
   ```

2. **Create deployment directory:**
   ```bash
   sudo mkdir -p /var/www/globe
   sudo chown -R cschorr:cschorr /var/www/globe
   ```

3. **Setup SSH keys for Git access:**
   ```bash
   ssh-keygen -t ed25519 -C "deploy@globe"
   # Add the public key to your GitHub repository as a deploy key
   ```

4. **Initial deployment:**
   ```bash
   vendor/bin/dep deploy stage
   ```

5. **Configure environment variables:**
   After first deployment, edit `/var/www/globe/shared/.env.local` on the server with production secrets:
   - `APP_SECRET`
   - `JWT_PASSPHRASE`
   - `DATABASE_URL`
   - `MYSQL_ROOT_PASSWORD`
   - `MYSQL_PASSWORD`
   - `SENTRY_DSN`

## Docker Container Management

The deployment uses Docker Compose with the following services:

- **globe-backend-stage-php**: FrankenPHP application server (ports 8080/444)
- **globe-backend-stage-database**: MariaDB database (port 3307 for debugging)

### Access containers on the server:

```bash
# View running containers
docker ps

# Access PHP container
docker exec -it globe-backend-stage-php sh

# Access database
docker exec -it globe-backend-stage-database mariadb -u app -p

# View logs
docker logs -f globe-backend-stage-php
docker logs -f globe-backend-stage-database
```

## Deployment Flow

1. **Preparation Phase:**
   - Lock deployment
   - Create new release directory
   - Clone repository

2. **Build Phase:**
   - Copy shared files (.env.local, JWT keys)
   - Create staging compose file if needed
   - Stop previous containers

3. **Deployment Phase:**
   - Build Docker images
   - Start new containers
   - Clear cache
   - Run database kickstart (staging) or migrations (production)
   - Health check

4. **Cleanup Phase:**
   - Create symlink to new release
   - Remove old releases (keeps 3)
   - Unlock deployment

## Troubleshooting

### Container won't start
```bash
# Check logs
vendor/bin/dep run "cd current && docker compose -p globe-backend logs" stage

# Rebuild images
vendor/bin/dep deploy:docker:build stage
```

### Database connection issues
```bash
# Check database is running
vendor/bin/dep run "docker ps | grep database" stage

# Test connection from PHP container
vendor/bin/dep run "docker exec globe-backend-stage-php php bin/console doctrine:query:sql 'SELECT 1'" stage
```

### Permission issues
```bash
# Fix permissions on server
vendor/bin/dep run "chmod -R 755 {{release_or_current_path}}" stage
vendor/bin/dep run "chmod +x {{release_or_current_path}}/bin/*" stage
```

### Rollback
```bash
# Rollback to previous release
vendor/bin/dep rollback stage

# Or manually on server
cd /var/www/globe
rm current
ln -s releases/previous-release current
docker compose -p globe-backend restart
```

## Security Considerations

1. Never commit secrets to the repository
2. Use strong passwords for database
3. Regularly update Docker images
4. Use HTTPS in production
5. Restrict database port access (firewall rules)
6. Regularly backup database:
   ```bash
   docker exec globe-backend-stage-database mysqldump -u app -p app > backup.sql
   ```

## Monitoring

### Health Checks
```bash
# Application health
vendor/bin/dep deploy:health:check stage

# Container status
vendor/bin/dep run "docker compose -p globe-backend ps" stage

# System resources
vendor/bin/dep run "docker stats --no-stream" stage
```

### Logs
```bash
# Application logs
vendor/bin/dep run "docker logs --tail 100 globe-backend-stage-php" stage

# Database logs
vendor/bin/dep run "docker logs --tail 100 globe-backend-stage-database" stage

# Symfony logs (inside container)
vendor/bin/dep run "docker exec globe-backend-stage-php tail -f var/log/prod.log" stage
```