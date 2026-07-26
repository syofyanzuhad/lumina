# Codebase Concerns

This document highlights technical debt, known bugs, security issues, performance bottlenecks, and code smells within the project.

## Security Issues
*   **NPM Vulnerabilities:** Running `npm audit` flags 9 high-severity vulnerabilities originating from outdated versions of `brace-expansion` and `minimatch`. These affect multiple linting and Vue ecosystem packages (like `@eslint/config-array` and `@vue/language-core`). It is recommended to run `npm audit fix --force` or update the dependencies to secure versions.
*   **Default Application Setup:** The current `.env.example` configurations use generic defaults (e.g., `APP_DEBUG=true`). While acceptable in development, care must be taken when deploying to production environments.

## Performance & Build Bottlenecks
*   **Developer Experience (PHPStan):** The `phpstan` analysis task crashes under the default PHP memory limit (128M). Analyzing the codebase currently requires specifying a higher memory limit explicitly (e.g., `vendor/bin/phpstan analyse --memory-limit=2G`). This should be addressed by adjusting the PHP configuration or specifying the memory limit directly in `phpstan.neon`.
*   **Vite Font Fallbacks:** The Vite build output warns that optimized font fallbacks require the optional `fontaine` package. To resolve this warning, either install the package or set `optimizedFallbacks: false` in the font configuration.

## Technical Debt & Code Smells
*   **Formatting Inconsistencies:** The Laravel Pint linter (`vendor/bin/pint --test`) highlights numerous minor code style violations—specifically, missing blank lines at the end of several test classes (e.g., `tests/Unit/ExampleTest.php`, `tests/Feature/Settings/SecurityTest.php`). Running Pint to fix these will enforce consistency.
*   **Testing Coverage Gaps:** While overall test coverage is extremely high (93.5%), there are minor gaps in provider files (`Providers/AppServiceProvider` and `Providers/FortifyServiceProvider`). Adding targeted test cases to these providers could push coverage closer to 100%.

## Architecture / Dependency Notes
*   **Database Default:** The project uses SQLite (`DB_CONNECTION=sqlite`) by default. This is excellent for a starter kit or small application but will require re-evaluating locking and concurrency limits if deployed for a highly active multi-user environment.
