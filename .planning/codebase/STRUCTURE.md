# Directory Structure & Main Entry Points

## Overview
Lumina follows a standard Laravel directory structure with the addition of frontend components located in `resources/js`, utilizing Vue 3, Inertia.js, and Tailwind CSS.

## High-Level Folder Structure
- `app/`: Contains the core logic of the application (Models, Http Controllers, Middleware, Actions).
  - `app/Http/Controllers/`: Handles incoming requests (e.g., Settings controllers).
  - `app/Actions/`: Contains isolated business logic actions, primarily for Fortify authentication flows (e.g., `CreateNewUser`, `ResetUserPassword`).
  - `app/Models/`: Eloquent ORM models representing the database tables.
- `bootstrap/`: Contains the main entry point `app.php`, which configures routing, middleware, and exceptions.
- `config/`: Application configuration files.
- `database/`: Database migrations, seeders, and factories.
- `resources/js/`: The frontend Vue 3 SPA built with Inertia.js.
  - `resources/js/components/`: Reusable Vue components (e.g., UI elements, navigation).
  - `resources/js/pages/`: Inertia page components acting as view layers for routes.
  - `resources/js/layouts/`: Vue layout components wrapping pages.
  - `resources/js/composables/`: Reusable Vue composables.
  - `resources/js/lib/`: Frontend utilities and helper functions.
  - `resources/js/ui/`: Radix-styled (Reka UI) primitive components.
- `routes/`: Routing definitions for the application.
  - `routes/web.php`: Primary frontend routes serving Inertia pages.
  - `routes/settings.php`: Routes specifically for user profile, security, and appearance settings.
- `tests/`: Automated tests utilizing Pest.

## Main Entry Points
1. **Backend Initialization**:
   - `bootstrap/app.php`: The starting point for the Laravel framework configuration and HTTP request lifecycle.
2. **Routing**:
   - `routes/web.php` and `routes/settings.php` map HTTP paths to Controllers or directly to Inertia pages.
3. **Frontend Application**:
   - `resources/js/app.ts`: The main entry file for initializing the Vue application and Inertia frontend router.
   - Vite is used via `vite.config.ts` to bundle and inject the frontend assets.
