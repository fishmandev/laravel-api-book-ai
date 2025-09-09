# Product Requirements Document - Simple Book Management API
## Version 1.0 | Date: 2025-09-09

## Executive Summary

Simple Book Management API is a minimal RESTful API for book management with basic authentication and role-based permission system. The system provides simple CRUD operations for books and users with JWT authentication.

**Related Documentation:**
- [Technical Architecture →](./Technical-Architecture.md) - Universal Laravel API architecture with centralized authorization

## Core Features

### Book Management
- **CRUD operations**: create, read, update, delete books
- **Book fields**: Only two fields - title and description  
- **Book listing**: Pagination with 10 records per page

### User Management
- **User fields**: name, email, password, email_verified_at
- **Authentication**: JWT-based login system using email credentials

### Roles & Permissions System
- **Role management**: Create, read, update, delete roles
- **Permission assignment**: Assign one or multiple permissions to roles
- **Exactly 5 permissions**:
  1. Create book
  2. Edit book
  3. Delete book
  4. View book list (shows title only)
  5. View book details (shows title + description)

### Super Administrator
- **System user**: ID=1, email="system@example.com" - bypasses all gates and policies
- **Unrestricted access**: All operations are allowed for super administrator
- **Deletion protection**: System user (ID=1) cannot be deleted

## Technology Stack

| Component | Technology | Version |
|-----------|------------|---------|
| Backend Framework | Laravel | 12.0 |
| PHP | PHP | 8.4+ |
| Database | MySQL | 8.0+ |
| Authentication | JWT | - |
| Container | Docker | - |

**PHP Version Note**: The project uses PHP 8.4, which ensures compatibility with Laravel 12.0 and matches the Docker container configuration (php:8.4-fpm-alpine).

## Database Schema

### Users Table
```sql
- id (primary key)
- name (string)
- email (string, unique)
- password (hashed)
- email_verified_at (timestamp, nullable)
- created_at
- updated_at
```

### Books Table
```sql
- id (primary key)
- title (string)
- description (text)
- created_at
- updated_at
```

### Roles Table
```sql
- id (primary key)
- name (string, unique)
- created_at
- updated_at
```

### Permissions Table
```sql
- id (primary key)
- name (string, unique) // 5 fixed permissions
- created_at
- updated_at
```

### Role_Permission Table (pivot)
```sql
- role_id (foreign key)
- permission_id (foreign key)
```

### User_Role Table (pivot)
```sql
- user_id (foreign key)
- role_id (foreign key)
```

## Development Phases

### Phase 1: MVP Setup (Week 1)
- Laravel 12.0 project setup
- Docker environment configuration
- Basic database schema creation
- User authentication with JWT

### Phase 2: Core Features (Week 2)
- CRUD operations for books
- Basic roles and permissions system
- Role assignment to users
- Super administrator implementation

### Phase 3: Final Polish (Week 3)
- Pagination for book listing
- Basic validation and error handling
- Simple testing
- Documentation cleanup

## API Endpoints

### Authentication
- **POST** `/api/login` - Login to system
- **POST** `/api/logout` - Logout from system

### Books (full CRUD)
- **GET** `/api/books` - Paginated book list (10 per page)
- **GET** `/api/books/{id}` - Get book details
- **POST** `/api/books` - Create new book
- **PUT** `/api/books/{id}` - Edit book
- **DELETE** `/api/books/{id}` - Delete book

### Users (CRUD with restrictions)
- **GET** `/api/users` - List all users
- **GET** `/api/users/{id}` - Get user details
- **POST** `/api/users` - Create new user
- **PUT** `/api/users/{id}` - Edit user
- **DELETE** `/api/users/{id}` - Delete user (EXCEPT ID=1)
- **POST** `/api/users/{id}/roles` - Assign role to user

### Roles (full CRUD)
- **GET** `/api/roles` - List all roles
- **GET** `/api/roles/{id}` - Get role details
- **POST** `/api/roles` - Create new role
- **PUT** `/api/roles/{id}` - Edit role
- **DELETE** `/api/roles/{id}` - Delete role
- **POST** `/api/roles/{id}/permissions` - Assign permissions to role
- **DELETE** `/api/roles/{id}/permissions/{permissionId}` - Remove permission from role

### Permissions (read only)
- **GET** `/api/permissions` - List all 5 permissions

**Note**: System user with ID=1 and email="system@example.com" cannot be deleted via API.

---
*Document updated: 2025-09-09*  
*Version: 1.0*  
*Status: Simplified scope*