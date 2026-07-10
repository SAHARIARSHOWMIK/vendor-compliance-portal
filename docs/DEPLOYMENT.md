# Deployment Notes

## Services

The Docker Compose deployment includes Nginx, PHP-FPM, MySQL, a database queue worker, and the Laravel scheduler.

## Required production settings

- Generate and protect `APP_KEY`.
- Disable application debugging.
- Configure HTTPS at the reverse proxy or load balancer.
- Replace default database passwords.
- Configure SMTP for operational messages.
- Configure S3-compatible private object storage where appropriate.
- Back up the database and private document volume.
- Apply log retention and monitoring.
- Run migrations before directing traffic to a new release.

## Suggested release sequence

```bash
docker compose build
docker compose up -d db
docker compose run --rm app php artisan migrate --force
docker compose up -d
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
```
