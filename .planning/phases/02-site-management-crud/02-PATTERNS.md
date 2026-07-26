# Phase 02 Patterns: Site Management (CRUD)

## Files to be Created / Modified

| File Path | Role | Data Flow | Closest Analog |
| --- | --- | --- | --- |
| `app/Models/User.php` | Model (Modify) | Provide `sites()` relationship | `App\Models\Site` |
| `app/Http/Requests/StoreSiteRequest.php` | Validation | Validates normalized domain | `App\Http\Requests\Settings\ProfileUpdateRequest` |
| `app/Policies/SitePolicy.php` | Authorization | Checks ownership (`owner_id`) | Laravel standard Policies |
| `app/Http/Controllers/SiteController.php` | Controller | CRUD and Onboarding presentation | `App\Http\Controllers\Settings\ProfileController` |
| `app/Http/Controllers/ActiveSiteController.php` | Controller | Updates `active_site_id` in session | N/A (Standard Invokable/Update) |
| `app/Http/Middleware/HandleInertiaRequests.php` | Middleware (Modify) | Shares active site and list of sites to UI | Existing `HandleInertiaRequests` |
| `routes/web.php` | Routing (Modify) | Registers Site & ActiveSite endpoints | `routes/web.php` |
| `resources/js/pages/Sites/Index.vue` | View | Displays grid/list of user's sites | `resources/js/pages/settings/Profile.vue` |
| `resources/js/pages/Sites/Create.vue` | View | Registration form for a new site | `resources/js/pages/settings/Profile.vue` (Form pattern) |
| `resources/js/pages/Sites/Show.vue` | View | Displays onboarding tracking snippet | N/A |
| `resources/js/components/SiteSwitcher.vue` | UI Component | Dropdown to switch active site | `resources/js/components/NavMain.vue` |
| `tests/Feature/SiteControllerTest.php` | Test | Asserts CRUD & normalization | `tests/Feature/Settings/ProfileUpdateTest.php` |
| `tests/Feature/SitePolicyTest.php` | Test | Asserts authorization boundaries | N/A |
| `tests/Feature/ActiveSiteControllerTest.php` | Test | Asserts session state logic | N/A |

## Code Excerpts & Patterns

### 1. Model Attributes Pattern (Laravel 13+)
Laravel now natively supports PHP Attributes instead of protected properties for fillable/hidden fields. Use this syntax:
```php
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable {
    // ...
}
```

### 2. Frontend Auto-Imports (Wayfinder)
The project utilizes `@laravel/vite-plugin-wayfinder` to automatically generate TypeScript actions and routes for Inertia components. Do not hardcode endpoint URLs. Bind your forms directly to the generated actions, or use the generated routes:

```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit } from '@/routes/profile';
</script>

<template>
    <!-- Use generated routes for links -->
    <Link :href="edit()">Edit</Link>
    
    <!-- Bind directly to form methods -->
    <Form
        v-bind="ProfileController.update.form()"
        v-slot="{ errors, processing }"
    >
        <!-- form fields... -->
    </Form>
</template>
```

### 3. Middleware Sharing via Inertia
When extending `HandleInertiaRequests.php` to provide the active site globally, append to the `share` method and keep `...parent::share($request)`:

```php
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            // Append sites logic here
        ];
    }
```

### 4. Vue & Tailwind Headless Components
All interfaces rely heavily on Reka UI primitives configured as Vue components in `resources/js/components/ui/`.
For instance, buttons and inputs are imported directly:
```vue
<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
</script>
```
