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
- PHP 8.4+ and Laravel 12.0

## Laravel Code Style Guidelines
- **Request Classes**: DO NOT override `authorize()` method - parent FormRequest returns `true` by default
- **Request Classes**: Only define `rules()` method for validation
- **Authorization**: Use Gates in controllers via `Gate::allows()` instead of direct permission checks
- **HTTP Status Codes**: Always use `Response::HTTP_*` constants instead of magic numbers
- **Type Hints**: Always specify return types for controller methods (`JsonResponse`, etc.)
- **Route Model Binding**: Use model objects in controller methods instead of `string $id` (e.g., `Book $book` instead of `string $id`)
- **Gates**: Load permissions dynamically from database via `PermissionService::defineGates()` with fallback for tests
- **Services**: Use service classes in `app/Services/` for business logic and reusable functionality
- **Base Controller**: Use `$this->authorize('permission.name')` method from parent Controller class that throws AuthorizationException for cleaner code
- **API Resources**: Always use JsonResource and AnonymousResourceCollection for API responses instead of raw arrays