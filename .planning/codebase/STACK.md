# Tech Stack

## Backend
- **Language**: PHP 8.3+
- **Framework**: Laravel 13.x
- **Architecture**: Monolith using Inertia.js to bridge backend and frontend.

## Frontend
- **Framework**: Vue 3 (Composition API)
- **Language**: TypeScript
- **Integration**: Inertia.js (Vue 3 Adapter)
- **Build Tool**: Vite
- **Styling**: Tailwind CSS v4
- **UI Components**: 
  - Reka UI (Headless components)
  - Class Variance Authority (CVA), clsx, tailwind-merge (for composable component styling)
- **Icons**: Lucide Vue

## Quality & Tooling
- **Testing**: Pest (PHP), PHPUnit
- **Static Analysis**: PHPStan (via Larastan)
- **Linting & Formatting**: 
  - Backend: Laravel Pint
  - Frontend: ESLint (with Vue and TypeScript plugins), Prettier
- **Local Environment**: Laravel Sail (Docker), optional local PHP environment (Herd/Valet)

## Database
- **Default Database**: SQLite (configured in `.env.example`)
- **Session/Cache/Queue**: Database driver used by default.
