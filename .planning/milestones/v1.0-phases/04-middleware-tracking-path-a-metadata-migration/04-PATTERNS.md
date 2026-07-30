# Phase 4 Patterns: Middleware Tracking (Path A) & Metadata Migration

## Overview of Files to Modify/Create

1. **`packages/lumina-core/src/Middleware/TrackPageview.php`** (New)
2. **`packages/lumina-core/src/LuminaCoreServiceProvider.php`** (Modify)
3. **`packages/lumina-core/src/Enums/DeviceType.php`** (Modify)
4. **`packages/lumina-core/src/Jobs/InsertEvent.php`** (New)
5. **`packages/lumina-core/database/migrations/..._add_metadata_to_events_table.php`** (New)
6. **`packages/lumina-core/src/Models/Event.php`** (Modify)
7. **`routes/web.php`** (Modify)
8. **`tests/Feature/TrackPageviewMiddlewareTest.php`** (New)

---

## Pattern Analysis

### 1. `TrackPageview` Middleware
- **Role:** Intercept incoming requests, derive tracking variables (IP hash, UA, country), handle rate limiting gracefully (silent swallow), and dispatch asynchronous tracking job (`InsertEvent`).
- **Data Flow:** Incoming HTTP Request → Variable Extraction/Hash → Rate Limit Check → `InsertEvent::dispatch(...)` → Passes onto next middleware via `return $next($request);`.
- **Closest Analog:** `app/Http/Middleware/HandleAppearance.php`
- **Concrete Code Excerpt (`HandleAppearance.php`):**
  ```php
  <?php

  namespace App\Http\Middleware;

  use Closure;
  use Illuminate\Http\Request;
  use Symfony\Component\HttpFoundation\Response;

  class HandleAppearance
  {
      public function handle(Request $request, Closure $next): Response
      {
          // ... middleware logic ...
          return $next($request);
      }
  }
  ```

### 2. `LuminaCoreServiceProvider`
- **Role:** Register middleware aliases (`lumina.track`) and configure named rate limiters (`lumina_ip`, `lumina_site`) during package booting.
- **Data Flow:** Package Boot → `boot()` method → Register limits & alias.
- **Closest Analog:** `app/Providers/FortifyServiceProvider.php` (for rate limiting setup).
- **Concrete Code Excerpt (`FortifyServiceProvider.php`):**
  ```php
  private function configureRateLimiting(): void
  {
      RateLimiter::for('login', function (Request $request) {
          $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

          return Limit::perMinute(5)->by($throttleKey);
      });
  }
  ```

### 3. `DeviceType` Enum
- **Role:** Map a given User-Agent string to a standardized enum case (`Mobile`, `Tablet`, `Desktop`, `Unknown`).
- **Data Flow:** Receives raw User-Agent string → Evaluates via regex match → Returns enum.
- **Closest Analog:** Self (Adding a new static method).
- **Concrete Code Excerpt (`04-RESEARCH.md` Pattern):**
  ```php
  public static function fromUserAgent(string $userAgent): self
  {
      // ... regex matching logic
      return self::Desktop;
  }
  ```

### 4. `InsertEvent` Job
- **Role:** Handle inserting tracking events in the background to prevent blocking the web request cycle. 
- **Data Flow:** Dispatched synchronously or asynchronously (via Queue) receiving primitive types → Runs `Event::create(...)`.
- **Closest Analog:** Standard Laravel Jobs pattern (`ShouldQueue`).
- **Concrete Code Excerpt (`04-RESEARCH.md`):**
  ```php
  class InsertEvent implements ShouldQueue
  {
      use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
      // ...
  }
  ```

### 5. `..._add_metadata_to_events_table` Migration
- **Role:** Add a nullable JSON column for event metadata.
- **Data Flow:** Database schema modification (Additive).
- **Closest Analog:** Standard additive migration.
- **Concrete Code Excerpt:**
  ```php
  Schema::table('events', function (Blueprint $table) {
      $table->json('metadata')->nullable()->after('country');
  });
  ```

### 6. `Event` Model
- **Role:** Eloquent model representing analytics events.
- **Data Flow:** Casts database payload types for application usage.
- **Closest Analog:** Self (Already exists).
- **Concrete Code Excerpt:**
  ```php
  protected function casts(): array
  {
      return [
          'device_type' => DeviceType::class,
          'metadata'    => 'array',
      ];
  }
  ```

### 7. `routes/web.php`
- **Role:** Registering the tracking middleware on the host application routes.
- **Data Flow:** Apply `lumina.track` to the necessary route/group wrappers.
- **Closest Analog:** Current route file auth middleware wrapping.
- **Concrete Code Excerpt:**
  ```php
  Route::middleware(['auth', 'verified', 'lumina.track'])->group(function () {
      // ... routes
  });
  ```

### 8. `TrackPageviewMiddlewareTest`
- **Role:** Validate middleware execution, job dispatch correctness, hashing, and rate limiting logic.
- **Data Flow:** Performs HTTP GET → Asserts against `Queue::fake()` or DB insertions (via `sync` queue connection). Uses Pest `test()` closures with `expect()` chaining.
- **Closest Analog:** `tests/Feature/SiteTest.php`
- **Concrete Code Excerpt (`SiteTest.php`):**
  ```php
  test('it deletes events when site is deleted', function () {
      $site = Site::factory()->has(Event::factory()->count(3))->create();
      
      $site->delete();
      
      $this->assertDatabaseEmpty('events');
  });
  ```
