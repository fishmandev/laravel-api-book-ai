# Simple Book Management API
## Minimal RESTful API for Book Management

A simple Laravel-based REST API for managing books with basic JWT authentication and role-based permissions. Provides CRUD operations for books, users, and roles with a minimal feature set.

## Documentation

### Core Documents

1. **[Product Requirements Document (PRD-Main.md)](./PRD-Main.md)**
   - Complete project requirements and scope
   - Database schema and API endpoints
   - Development roadmap and specifications

2. **[Technical Architecture (Technical-Architecture.md)](./Technical-Architecture.md)**
   - Universal Laravel API architecture patterns
   - Design principles and best practices
   - Centralized authorization approach

## Quick Start

### Prerequisites
- Docker and Docker Compose
- Git

### Local Development Setup

```bash
# Start Docker containers
docker compose up -d

# Run migrations
docker exec -u1000 api php artisan migrate

# Seed the database
docker exec -u1000 api php artisan db:seed

# Test the API
curl http://localhost/api/books
```

## Core Features Overview

*For detailed technical specifications and technology stack, see [PRD-Main.md](./PRD-Main.md)*

- **Book Management**: CRUD operations for books (title + description)
- **User Management**: JWT authentication with username/password
- **Role-Based Permissions**: 5 fixed permissions for book operations
- **System Administrator**: Protected system user (ID=1)

*For complete feature specifications, see [PRD-Main.md](./PRD-Main.md)*

## API Overview

The API provides RESTful endpoints for:
- **Authentication**: Login/logout with JWT
- **Books**: Full CRUD operations with pagination
- **Users**: User management with role assignment
- **Roles**: Role management with permission assignment
- **Permissions**: Read-only access to 5 fixed permissions

*For complete API specifications with request/response examples, see [PRD-Main.md](./PRD-Main.md)*


## Development Status

*Current project status and development phases detailed in [PRD-Main.md](./PRD-Main.md)*

---
*Simple Book Management API - Minimal Documentation*