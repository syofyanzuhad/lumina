# System Architecture

## High-Level Architecture
Lumina uses a hybrid monolithic architecture, often termed a **"Modern Monolith"**. It combines a robust Laravel backend with a reactive Vue 3 frontend, bridged seamlessly by **Inertia.js**. This removes the need for building and maintaining a separate REST API to communicate between the server and client.

### Core Stack
- **Backend**: Laravel framework (PHP) responsible for database access, business logic, routing, authentication, and validation.
- **Frontend**: Vue 3 (Composition API) built with Vite, acting as the view layer.
- **Bridge**: Inertia.js acts as the glue, allowing the frontend to operate as an SPA (Single Page Application) while keeping routing and controllers server-side.
- **Authentication**: Laravel Fortify provides the backend implementation for authentication features (login, registration, passkeys, 2FA), operating headlessly.

## Design Patterns & Paradigms
1. **MVC Pattern (Server-Side)**
   - The backend strictly follows the Model-View-Controller pattern but with a twist: the "View" is an Inertia response containing the data needed to render a Vue component, rather than a Blade template.
2. **Action Classes**
   - Encapsulation of specific business logic into single-responsibility Action classes (e.g., `app/Actions/Fortify/CreateNewUser.php`). This keeps controllers slim and makes core logic highly testable.
3. **Component-Based UI (Client-Side)**
   - The UI is composed of reusable Vue components (`resources/js/components` and `resources/js/ui`). It heavily utilizes Tailwind CSS for styling and Reka UI for accessible, unstyled component primitives.
4. **Middleware-Driven Lifecycle**
   - Critical lifecycle tasks (e.g., authentication checks, Inertia shared data injection, and appearance handling) are handled by HTTP middleware before hitting controllers.
5. **Form Requests & Validation**
   - Laravel handles incoming data validation before hitting controller logic, ensuring security and integrity. Inertia natively catches these errors and passes them back to the Vue frontend context.
