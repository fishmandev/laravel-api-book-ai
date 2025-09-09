# Claude Code Instructions

## Docker Environment
- Project runs in Docker containers
- Laravel is served through Docker Compose
- Application container is named `api`
- Run commands as user ID 1000: `docker exec -u1000 api [command]`
- For Artisan commands use: `docker exec -u1000 api php artisan [command]`
- For Composer commands use: `docker exec -u1000 api composer [command]`
- Database runs in container named `db`

## Useful Docker Commands
- `docker compose up -d` - start the project
- `docker exec -u1000 api sh` - enter application container (use sh, not bash)
- `docker exec -u1000 api php artisan migrate` - run migrations
- `docker exec -u1000 api php artisan test` - run tests

## Project Structure
- This is a Laravel API project for book AI functionality
- Uses MySQL in Docker container
- PHP 8.2+ and Laravel 12.0