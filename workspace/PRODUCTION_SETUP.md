# Production Environment Setup Guide

This guide explains how to run TrueTrack in a production-like environment locally on your laptop for testing before actual cloud deployment.

---

## 📋 What You Get

### Two Separate Environments

| Environment | URL | Database Port | Redis Port | Purpose |
|------------|-----|---------------|------------|---------|
| **Development** | http://localhost | 5432 | 6379 | Daily development |
| **Production** | http://localhost:8080 | 5433 | 6380 | Production testing |

### Key Features

✅ **Completely Isolated**
- Separate databases (no data conflicts)
- Separate Redis instances
- Different Docker networks
- Independent configurations

✅ **Production Optimizations**
- OPcache enabled
- Route/config/view caching
- Optimized Composer autoloader
- Nginx reverse proxy
- Background queue workers
- Scheduled task runner

✅ **Easy Switching**
- Both environments run simultaneously
- Switch with environment variables
- Independent deployment scripts

---

## 🚀 Quick Start

### Step 1: Create Production Environment File

```powershell
# Windows PowerShell
cd workspace
Copy-Item .env.production.example .env.production
```

```bash
# Linux/macOS
cd workspace
cp .env.production.example .env.production
```

### Step 2: Edit Production Settings

Edit `.env.production`:

```env
# Change these at minimum:
DB_PASSWORD=YOUR_STRONG_PASSWORD_HERE
APP_KEY=  # Will be auto-generated

# Optional (for email testing):
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

### Step 3: Initialize Production Environment

**Windows:**
```powershell
.\deploy-local-prod.ps1 init
```

**Linux/macOS:**
```bash
chmod +x deploy-local-prod.sh
./deploy-local-prod.sh init
```

This will:
1. Build production Docker images
2. Start all services
3. Generate application key
4. Run database migrations
5. Optimize for production
6. Create storage symlinks

### Step 4: Access Production Environment

Open your browser:
- **Application:** http://localhost:8080
- **Database:** localhost:5433 (PostgreSQL)
- **Cache:** localhost:6380 (Redis)

---

## 📖 Common Tasks

### Check Status

```powershell
.\deploy-local-prod.ps1 status
```

Output shows:
- Running containers
- Health check status
- Resource usage

### View Logs

```powershell
# Live tail (Ctrl+C to exit)
.\deploy-local-prod.ps1 logs

# Or view specific service
docker compose -f compose.yaml -f compose.production.yaml logs -f truetrack-prod
docker compose -f compose.yaml -f compose.production.yaml logs -f nginx-prod
docker compose -f compose.yaml -f compose.production.yaml logs -f queue-worker-prod
```

### Deploy Code Changes

After making code changes:

```powershell
.\deploy-local-prod.ps1 update
```

This will:
1. Rebuild Docker images
2. Restart services
3. Run new migrations
4. Clear caches
5. Rebuild optimizations

### Restart Services

If services are acting up:

```powershell
.\deploy-local-prod.ps1 restart
```

### Stop Production Environment

When you're done testing:

```powershell
.\deploy-local-prod.ps1 down
```

This stops all production containers but keeps data in volumes.

### Access Production Container

Need to run commands manually?

```powershell
# Enter container shell
docker compose -f compose.yaml -f compose.production.yaml exec truetrack-prod bash

# Run artisan commands
docker compose -f compose.yaml -f compose.production.yaml exec truetrack-prod php artisan migrate
docker compose -f compose.yaml -f compose.production.yaml exec truetrack-prod php artisan tinker
```

---

## 🔧 Configuration Details

### Environment Variables

Key differences between `.env` (dev) and `.env.production`:

| Setting | Development | Production |
|---------|------------|------------|
| `APP_ENV` | local | production |
| `APP_DEBUG` | true | false |
| `DB_HOST` | pgsql | pgsql-prod |
| `REDIS_HOST` | redis | redis-prod |
| `SESSION_DRIVER` | database | redis |
| `CACHE_DRIVER` | database | redis |
| `QUEUE_CONNECTION` | database | redis |

### Docker Services

Production environment includes:

1. **truetrack-prod** - Main Laravel application (PHP-FPM)
2. **nginx-prod** - Web server (reverse proxy)
3. **pgsql-prod** - PostgreSQL database
4. **redis-prod** - Cache and queue storage
5. **queue-worker-prod** - Background job processor
6. **scheduler-prod** - Cron job runner

### Storage Volumes

Data persists in Docker volumes:
- `truetrack-postgres-prod-data` - Database files
- `truetrack-redis-prod-data` - Cache/queue data

Shared with host:
- `./storage` - User uploads, logs
- `./bootstrap/cache` - Laravel cache files

---

## 🧪 Testing Production Features

### Test OFX Import

1. Access http://localhost:8080
2. Login with production user
3. Upload OFX file (should process in background)
4. Monitor logs: `.\deploy-local-prod.ps1 logs`

### Test Queue Workers

```powershell
# Dispatch a test job
docker compose -f compose.yaml -f compose.production.yaml exec truetrack-prod php artisan tinker
>>> dispatch(new \App\Jobs\ProcessOfxImport(...));

# Watch queue worker logs
docker compose -f compose.yaml -f compose.production.yaml logs -f queue-worker-prod
```

### Test Scheduler

Scheduled tasks run every minute in production:

```powershell
# View scheduler logs
docker compose -f compose.yaml -f compose.production.yaml logs -f scheduler-prod

# Manually trigger schedule
docker compose -f compose.yaml -f compose.production.yaml exec scheduler-prod php artisan schedule:run
```

### Test Performance

Production has optimizations enabled:
- OPcache (PHP opcode cache)
- Route caching
- Config caching
- View compilation

To verify:

```powershell
docker compose -f compose.yaml -f compose.production.yaml exec truetrack-prod php artisan optimize
docker compose -f compose.yaml -f compose.production.yaml exec truetrack-prod php -i | grep opcache
```

---

## 🐛 Troubleshooting

### Services Won't Start

```powershell
# Check logs for errors
docker compose -f compose.yaml -f compose.production.yaml logs

# Check if ports are already in use
netstat -an | findstr "8080 5433 6380"

# Change ports in .env.production if needed
```

### Database Connection Issues

```powershell
# Test database connectivity
docker compose -f compose.yaml -f compose.production.yaml exec truetrack-prod php artisan tinker
>>> DB::connection()->getPdo();

# Check database logs
docker compose -f compose.yaml -f compose.production.yaml logs pgsql-prod
```

### Permission Errors

```powershell
# Fix storage permissions
docker compose -f compose.yaml -f compose.production.yaml exec truetrack-prod chmod -R 755 storage
docker compose -f compose.yaml -f compose.production.yaml exec truetrack-prod chmod -R 755 bootstrap/cache
```

### Cache Issues

```powershell
# Clear all caches
docker compose -f compose.yaml -f compose.production.yaml exec truetrack-prod php artisan cache:clear
docker compose -f compose.yaml -f compose.production.yaml exec truetrack-prod php artisan config:clear
docker compose -f compose.yaml -f compose.production.yaml exec truetrack-prod php artisan route:clear
docker compose -f compose.yaml -f compose.production.yaml exec truetrack-prod php artisan view:clear

# Rebuild caches
.\deploy-local-prod.ps1 update
```

### Reset Production Database

**⚠️ WARNING: This deletes all production data!**

```powershell
docker compose -f compose.yaml -f compose.production.yaml exec truetrack-prod php artisan migrate:fresh --seed --force
```

---

## 🚀 Next Steps: Cloud Deployment

Once you've tested locally, you can deploy to:

### Option 1: DigitalOcean + Laravel Forge
- Easiest deployment
- One-click setup
- Managed backups
- Cost: ~$25-50/month

### Option 2: AWS EC2 + RDS
- Full control
- Scalable
- More complex
- Cost: ~$80-150/month

### Option 3: Laravel Vapor (Serverless)
- Auto-scaling
- Zero server management
- AWS Lambda-based
- Cost: ~$80-200/month

See main documentation for detailed cloud deployment guides.

---

## 📚 Additional Resources

- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
- [PostgreSQL Docker Hub](https://hub.docker.com/_/postgres)
- [Redis Docker Hub](https://hub.docker.com/_/redis)
- [Nginx Docker Hub](https://hub.docker.com/_/nginx)

---

## ❓ FAQ

**Q: Can I run dev and prod at the same time?**  
A: Yes! They use different ports and networks.

**Q: Will production affect my development data?**  
A: No, completely separate databases and volumes.

**Q: How do I backup production data?**  
A: `docker compose -f compose.yaml -f compose.production.yaml exec pgsql-prod pg_dump -U sail truetrack_prod > backup.sql`

**Q: How do I restore production data?**  
A: `docker compose -f compose.yaml -f compose.production.yaml exec -T pgsql-prod psql -U sail truetrack_prod < backup.sql`

**Q: Can I use a different port than 8080?**  
A: Yes, change `PROD_APP_PORT` in `.env.production`

**Q: Is this suitable for real production?**  
A: This is for **testing production configurations locally**. For real production, use cloud providers with managed services, load balancing, and proper backups.

---

**Last Updated:** January 31, 2026
