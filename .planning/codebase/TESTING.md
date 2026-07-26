# Testing

The project's testing strategy primarily focuses on the backend using Pest PHP.

## Backend Testing (Pest PHP)

The backend utilizes [Pest PHP](https://pestphp.com/) (v4.7+) as the testing framework, integrated natively with Laravel via `pestphp/pest-plugin-laravel`.

### Test Structure
Tests are located in the `tests/` directory and are divided into two main categories:

- **`tests/Feature/`**: 
  - Used for integration testing, HTTP requests, database interactions, and end-to-end API/Controller testing.
  - These tests typically interact with the application using the testing database and ensure that the various parts of the application work together correctly.

- **`tests/Unit/`**: 
  - Used for testing isolated classes, methods, and logic without relying on the Laravel framework or database.

### Running Tests

You can run the test suite using Artisan or Composer:

```bash
# Run all tests using Artisan
php artisan test

# Run tests via Composer script (Recommended)
# Note: This also clears config, runs linting (pint), and static analysis (phpstan) before executing tests
composer test
```

### Base Configuration
- `Pest.php`: Contains the foundational Pest configuration, binding base test cases, and custom helpers/expectations.
- `TestCase.php`: The foundational test class that extends Laravel's native testing capabilities.

## Frontend Testing

Currently, the project does not have a frontend testing framework (like Vitest, Jest, or Cypress) configured in `package.json`. Validations for the frontend are currently handled via static analysis (`vue-tsc`), linting (`eslint`), and formatting (`prettier`).
