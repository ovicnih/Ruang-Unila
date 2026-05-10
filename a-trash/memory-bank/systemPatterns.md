# System Patterns - Ruang Unila

## Architecture
- Modular MVC-like: Model (classes/), View (includes/), Controller (modules/)
- Entry point: index.php dengan URL rewriting (.htaccess)

## Key Technical Decisions
- PHP 8.x native (bukan framework)
- MySQL 8.0 dengan PDO untuk keamanan
- Prepared statements untuk semua query
- Password hashing dengan bcrypt

## Component Relationships
- config/  includes/  modules/  classes/
- Semua module menggunakan class dari classes/
- Template (header/footer) diincludes oleh semua module

## Critical Implementation Paths
- Auth flow: login.php  session.php  redirect by role
- News flow: create.php  admin validation  list.php
- Event flow: create.php  register.php  payment.php  verification
