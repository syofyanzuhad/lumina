# Integrations

## Third-Party Services & APIs
- **AWS S3**: Environment variables (`AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, etc.) are pre-configured in `.env.example` for cloud storage integration via Laravel's filesystem abstraction.

## External Libraries & Core Packages
- **Inertia.js**: Replaces standard blade views with a modern Vue 3 SPA frontend without building a separate API.
- **Laravel Fortify**: Headless authentication backend for Laravel, providing routes and controllers for login, registration, password reset, etc.
- **Laravel Passkeys**: (`@laravel/passkeys`) Integration for WebAuthn/Passkey support on the frontend.
- **Reka UI**: Accessible, headless Vue UI components used as the foundation for the application's design system.
- **VueUse**: Collection of essential Vue Composition Utilities.
- **Vue Sonner**: Opinionated toast component for Vue.

## Database & Infrastructure
- **SQLite**: The primary database connection used out of the box for simplicity and local development.
- **Redis / Memcached**: Configuration present for caching and queue management, though not enabled by default.
- **Mail**: Configured to use the `log` driver locally, but structured to support standard SMTP, Mailgun, Postmark, or AWS SES via Laravel's mailer.
