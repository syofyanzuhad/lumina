# Coding Conventions

This project enforces strict coding standards and static analysis across both the backend (PHP/Laravel) and frontend (TypeScript/Vue) codebases.

## Backend (PHP/Laravel)

### Code Style
- **Formatter**: [Laravel Pint](https://laravel.com/docs/pint) is used for code formatting.
- **Preset**: The `laravel` preset is strictly enforced (configured in `pint.json`).
- **Naming Conventions**: Standard Laravel conventions apply (e.g., PascalCase for classes, camelCase for methods/variables, snake_case for database columns).

### Static Analysis
- **Tool**: [PHPStan](https://phpstan.org/) with [Larastan](https://github.com/larastan/larastan).
- **Strictness Level**: Level 7 (configured in `phpstan.neon`).
- **Scope**: Analysis is run against `app/`, `bootstrap/app.php`, `config/`, `database/`, and `routes/`.

## Frontend (TypeScript/Vue)

### Code Style & Linting
- **Linter**: ESLint v9 (Flat Config via `eslint.config.js`).
- **Formatter**: Prettier (with `prettier-plugin-tailwindcss`).
- **Type Checking**: `vue-tsc` (TypeScript compiler for Vue).
- **Core Rules**:
  - **Imports**: Must be ordered alphabetically and grouped (`builtin`, `external`, `internal`, `parent`, `sibling`, `index`). Top-level consistent type imports are required.
  - **Control Statements**: Strict padding is enforced around control statements (`if`, `return`, `for`, `while`, `switch`, etc.) requiring blank lines before and after.
  - **Braces**: The `1tbs` (One True Brace Style) is enforced with single-line blocks explicitly disallowed (`curly: ['error', 'all']`).
  - **Vue Components**: Multi-word component names rule is disabled.

## Enforcing Standards

The project utilizes Composer and npm scripts to check and enforce standards locally and in CI:

### Automatic Fixing
- **Backend (PHP)**: Run `composer lint` to automatically fix Pint issues.
- **Frontend (JS/TS/Vue)**: Run `npm run lint` (ESLint auto-fix) and `npm run format` (Prettier auto-fix).

### CI / Checks
Before committing or merging, ensure the following checks pass:
- **Backend**: 
  - `composer lint:check` (Checks formatting without fixing)
  - `composer types:check` (Runs PHPStan)
- **Frontend**:
  - `npm run lint:check`
  - `npm run format:check`
  - `npm run types:check` (Runs `vue-tsc --noEmit`)

All of the above can be executed at once using the comprehensive CI script:
```bash
composer ci:check
```
