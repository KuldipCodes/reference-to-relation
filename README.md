# Check Model Relations Command

This package provides a Laravel Artisan command to analyze your database's foreign key constraints and suggest the necessary relationships (e.g., `belongsTo`, `hasMany`) for your Eloquent models.

## Features

- Scans your database schema for foreign key constraints.
- Identifies missing relationships in your Eloquent models.
- Suggests the necessary `belongsTo` and `hasMany` relationship methods.

## Installation

1. Clone or download the repository.
2. Place the `CheckModelRelations` class in your `app/Console/Commands` directory.
3. Register the command in your `App\Console\Kernel.php` file:

   ```php
   protected $commands = [
       \App\Console\Commands\CheckModelRelations::class,
   ];

##  Usage
- php artisan check:model-relations {database}
