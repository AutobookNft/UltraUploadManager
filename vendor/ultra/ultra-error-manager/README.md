# UltraErrorManager (UEM) – Oracoded Error Management for Laravel

[![Latest Version](https://img.shields.io/badge/version-v1.0.0-blue.svg?style=flat-square)](...) <!-- Placeholder -->
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg?style=flat-square)](...) <!-- Placeholder -->
[![Coverage Status](https://img.shields.io/badge/coverage-Partial%20(MVP)-yellowgreen.svg?style=flat-square)](...) <!-- Placeholder - Updated -->
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=flat-square)](https://opensource.org/licenses/MIT)

**UltraErrorManager (UEM)** is an enterprise-level, configuration-driven, and Oracode-compliant error management system designed for robust Laravel applications, specifically within the Ultra ecosystem (e.g., FlorenceEGI). It provides a centralized hub for catching, processing, logging, notifying, and potentially recovering from errors occurring throughout your application.

Built with **Dependency Injection** at its core, UEM is highly **testable**, **extensible** via custom handlers, and designed with **GDPR awareness** in mind.

---

## 📜 Core Philosophy: Oracode Doctrine

UEM adheres strictly to the **Oracode Doctrine**, ensuring code is not just functional but also **interrogable**, **explicitly intentional**, and **semantically coherent**. This means:

*   **Ultra Documentation Philosophy (UDP):** Every significant component explains *why* it exists. Code aims to be self-documenting through clear naming, structure, and targeted comments/annotations.
*   **AChaos Principle:** Embraces the flow from chaotic exploration (development, debugging) to structured, transmittable form (stable, documented code). The DI-first approach facilitates this by promoting decoupled components.
*   **Oracode Annotations:** Uses specific annotations (`@/#[🎯]`, `@[🧱]`, `@[📡]`, `@[🧪]`, `@[🛡️]`, `@[📥]`, `@[📤]`, etc.) within PHP docblocks and attributes to convey semantic meaning about purpose, structure, communication, testability, and GDPR implications.

*(For more details, refer to the main Oracode Doctrine documentation.)*

---

## ✨ Features

*   **Centralized Error Handling:** Single point (`ErrorManagerInterface`) for processing all application errors.
*   **Configuration-Driven:** Define error codes, types (critical, error, warning, notice), blocking levels (blocking, semi-blocking, not), messages (dev/user), HTTP status codes, display modes, and notification needs in `config/error-manager.php`.
*   **Extensible Handler Pattern:** Process errors through a pipeline of handlers. Comes with defaults for:
    *   Logging (via injected `UltraLogManager`)
    *   Database Persistence (`ErrorLog` model)
    *   Email Notifications (via injected `MailerContract`)
    *   Slack Notifications (via injected `HttpClientFactory`)
    *   User Interface Preparation (flashing data to session via injected `Session`)
    *   Automated Recovery Actions (placeholder logic, extensible)
    *   Error Simulation Logging (dev environments only)
*   **Dependency Injection First:** Core services (`ErrorManager`, Handlers) rely on DI, making them facade-free internally and highly testable.
*   **Oracode Compliant:** Internal codebase uses Oracode annotations and principles.
*   **GDPR Aware:** Includes annotations and promotes practices like context sanitization (see GDPR section below).
*   **Error Simulation:** Built-in service (`TestingConditionsManager`) and API/Dashboard UI to activate/deactivate specific error codes for testing application responses.
*   **Web Dashboard:** Provides a UI (`/error-manager/dashboard`) to view, filter, resolve, and analyze logged errors (requires database logging handler).
*   **Ultra Ecosystem Integration:** Designed to work seamlessly with `UltraLogManager` (for logging) and `UltraTranslationManager` (via `TranslatorContract` for messages).

---

## 🚧 Current Status & Test Coverage (v1.0.0 MVP)

This version (v1.0.0) represents a **functionally complete Minimum Viable Product (MVP)** of the UltraErrorManager. The core error handling pipeline, configuration system, default handlers (logging, database, email, slack, UI prep, recovery placeholders, simulation logging), and dashboard interface are implemented and operational.

However, in adherence to Oracode principles of transparency and continuous improvement, it's important to note that **full test coverage has not yet been achieved**. During the initial development and unit testing phase, certain tests were deliberately deferred to prioritize delivering the core MVP functionality. These decisions were often based on:

*   **Complexity:** Mocking certain dependencies (like static Eloquent methods, global helpers like `__()`, or complex external API interactions) proved difficult or unreliable in a purely unit-testing context.
*   **Suitability:** Some components, particularly controllers interacting heavily with database queries and view rendering (like `ErrorDashboardController`), are better suited for **Feature Tests** that verify the end-to-end flow rather than isolated unit tests.
*   **Risk Assessment:** Deferring tests for areas with secure defaults (like conditional data inclusion in notifications) or those implicitly covered by other tests was deemed acceptable for the MVP stage.

The following areas represent the current **technical debt** regarding test coverage, intended to be addressed in future development iterations:

### Deferred Unit Tests (To be added or completed post-MVP)

*   **`DatabaseLogHandler`:** Test for handling persistence failures (deferred due to static `ErrorLog::create` mocking challenges). Requires refactoring the handler to inject a persister dependency.
*   **`EmailNotificationHandler` / `SlackNotificationHandler`:** Granular tests verifying *every* conditional `include_*` flag and detailed sanitization/truncation logic for payload/email body (deferred due to combinatorial complexity; core sending logic is tested). Recommended coverage via dedicated Integration/Feature Tests.
*   **`UserInterfaceHandler`:** Test for the `getGenericErrorMessage` helper method (deferred due to difficulty mocking the global `__()` helper reliably in unit tests). Core session flashing is tested.
*   **`RecoveryActionHandler`:** Tests verifying internal dispatch logic (`switch` statement) and the internal `catch` block (deferred due to the class being `final` and reliance on placeholder methods). Requires refactoring (e.g., extracting a dispatcher) or Integration/Feature tests with real recovery actions.
*   **`TestingConditionsManager`:** Tests for the static proxy methods (`set`, `clear`, `reset`) (deferred due to complexity of mocking `app()` helper; considered low risk and sufficiently covered by Feature tests using the Facade).
*   **`ErrorLog` Model:** Tests for the `user()` relationship and complex static aggregate query methods (`getSimilarErrors`, `getErrorFrequency`, `getTopErrorCodes`), including handling date scopes (deferred due to test data setup complexity and potential DB driver inconsistencies in testing).
*   **Helpers (`helpers.php`):** Tests for global helper functions like `ultra_error()` (deferred due to complexity of mocking `app()`; considered low risk as they are thin wrappers around tested services, sufficiently covered by Feature tests).

### Planned Feature Tests (Considered Primary Coverage for Certain Areas)

*   **`ErrorDashboardController`:** The entire test suite for this controller is planned as Feature Tests, verifying routes, data retrieval from the DB via `ErrorLog`, view rendering, and interactions (filtering, resolving, deleting, purging, simulations).
*   **End-to-End Error Handling:** Tests simulating various application errors (via direct calls or exceptions) and verifying the complete UEM flow, including database logging, notifications (using Mail/HTTP fakes), and UI feedback (session assertions).
*   **Recovery Action Execution:** Feature tests triggering errors configured with *real* recovery actions (once implemented) and verifying their success/failure logging and effects.

We are committed to expanding test coverage and addressing this technical debt in subsequent versions of UEM to fully align with the Oracode doctrine's emphasis on interrogable and resilient code.

---

## 💾 Installation

1.  **Require the Package:**
    ```bash
    composer require ultra/ultra-error-manager:^1.0
    ```
    *(Adjust version constraint as needed.)*

2.  **Configure Repositories (if using VCS):** Ensure your application's `composer.json` includes the necessary VCS repositories if you are pulling dependencies like ULM or UTM directly from Git:
    ```json
    "repositories": [
        { "type": "vcs", "url": "https://github.com/AutobookNft/UltraLogManager" },
        { "type": "vcs", "url": "https://github.com/AutobookNft/UltraTranslationManager.git" },
        { "type": "vcs", "url": "https://github.com/AutobookNft/UltraErrorManager.git" }
        // Add other Ultra package repositories if necessary
    ],
    "minimum-stability": "dev",
    "prefer-stable": true
    ```
    Then run `composer update`.

3.  **Publish Configuration:**
    ```bash
    php artisan vendor:publish --tag=error-manager-config
    ```
    Review and customize `config/error-manager.php` according to your needs (especially notification recipients, error definitions).

4.  **Publish & Run Migrations:**
    ```bash
    php artisan vendor:publish --tag=error-manager-migrations
    php artisan migrate
    ```
    This creates the `error_logs` table required by the `DatabaseLogHandler`.

5.  **Publish Translations (Optional):**
    ```bash
    php artisan vendor:publish --tag=error-manager-language
    ```
    Customize messages in `resources/lang/vendor/error-manager/{locale}/errors.php`.

6.  **Publish Views (Optional):**
    ```bash
    php artisan vendor:publish --tag=error-manager-views
    ```
    Customize the dashboard views in `resources/views/vendor/error-manager/`.

7.  **Register Middleware:** Add the `ErrorHandlingMiddleware` to your global HTTP middleware stack (usually in `app/Http/Kernel.php`'s `$middleware` group) to automatically catch and handle exceptions. Ensure it's placed appropriately, often near the top. Add `EnvironmentMiddleware` to specific routes (like the simulation API routes in `routes/api.php`) or route groups that need environment protection.
    ```php
    // app/Http/Kernel.php
    protected $middleware = [
        // ... other global middleware
        \Ultra\ErrorManager\Http\Middleware\ErrorHandlingMiddleware::class,
        // ... other global middleware
    ];

    protected $middlewareAliases = [ // Renamed from $routeMiddleware for clarity in modern Laravel
        // ... other aliases
        'environment' => \Ultra\ErrorManager\Http\Middleware\EnvironmentMiddleware::class,
        // ...
    ];
    ```

---

## ⚙️ Configuration

The main configuration file is `config/error-manager.php`. Key sections include:

*   **`default_handlers`**: Array of `ErrorHandlerInterface` class strings to register by default. Order can matter.
*   **`email_notification` / `slack_notification`**: Settings for enabling/disabling and configuring recipients, webhooks, etc. Includes GDPR-related flags for data inclusion. **Remember to set actual recipient/webhook URLs in your `.env` file.**
*   **`database_logging`**: Enable/disable DB logging, include traces, set max trace length, define sensitive keys for context redaction.
*   **`ui`**: Default display mode, whether to show error codes to users, generic message key.
*   **`error_types`**: Define severity levels (critical, error, warning, notice) and their default behavior (log level, notify team, HTTP status).
*   **`blocking_levels`**: Define application flow impact (blocking, semi-blocking, not).
*   **`fallback_error` / `errors`**: The core dictionary mapping your application's symbolic error codes (e.g., `USER_NOT_FOUND`, `UPLOAD_FAILED`) to their specific configuration (type, blocking, messages, http status, notifications, recovery action). **This is where you define your application's error vocabulary.**

---

## 🚀 Basic Usage

There are three primary ways to trigger error handling:

**1. Dependency Injection (Recommended)**

Inject `Ultra\ErrorManager\Interfaces\ErrorManagerInterface` into your controllers, services, or other classes:

```php
use Ultra\ErrorManager\Interfaces\ErrorManagerInterface;
use App\Exceptions\CustomDomainException; // Your specific exception

class MyService
{
    protected readonly ErrorManagerInterface $errorManager;

    public function __construct(ErrorManagerInterface $errorManager) { /* ... */ }

    public function processUserData(int $userId, array $data)
    {
        try {
            // ... logic ...
            if (empty($data['email'])) {
                // Returns Response or null
                return $this->errorManager->handle('USER_DATA_INVALID', /* context */);
            }
            // ... logic ...
        } catch (CustomDomainException $e) {
            return $this->errorManager->handle('DOMAIN_RULE_VIOLATED', /* context */, $e);
        }
    }
}
```

**2. Facade (`UltraError`)**

Use the `UltraError` facade for quick access:

```php
use Ultra\ErrorManager\Facades\UltraError;

// Simple handling
UltraError::handle('CACHE_FLUSH_FAILED');

// Handling with context and exception
try { /* ... */ } catch (\Exception $e) {
    UltraError::handle('EXTERNAL_API_UNREACHABLE', ['api_name' => 'Stripe'], $e);
}
```

**3. Helper Function (`ultra_error`)**

Use the global `ultra_error()` helper function:

```php
// Handle an error
ultra_error('QUEUE_JOB_FAILED', ['job_id' => $job->id]);

// Handle and force throwing an exception
try { /* ... */ } catch (\Exception $e) {
    ultra_error('CRITICAL_PROCESS_FAILURE', [], $e, true); // $throw = true
}
```

---

## 💡 Advanced Concepts

### Custom Error Handlers

Extend UEM by creating classes implementing `Ultra\ErrorManager\Interfaces\ErrorHandlerInterface` (`shouldHandle`, `handle`) and register them via the service provider or `config/error-manager.php`. Use constructor injection for dependencies.

### Error Simulation (Testing)

Use the `TestingConditions` facade (`TestingConditions::set('MY_ERROR')`), `simulate_error('MY_ERROR')` helper, API (`/api/errors/simulate/...`), or the Web Dashboard (`/error-manager/dashboard/simulations`) to activate/deactivate specific error codes in non-production environments for testing application responses.

### GDPR & Oracode Annotations

UEM uses annotations like `@[🛡️]`, `@[📥]`, `@[📤]`, `@[🪵]`, `@[🧼]` to mark privacy-relevant code. Always sanitize `$context` data before sending it externally (Email, Slack) or persisting it if it might contain PII. Default handlers include basic sanitization based on config.

### Dashboard

Access the web dashboard at `/error-manager/dashboard` (protect with middleware) to view, filter, resolve, purge logs, and manage simulations.

---

## 🧪 Testing

While comprehensive unit tests cover core logic and handlers (excluding deferred items listed above), **Feature Tests are strongly recommended** to verify end-to-end error scenarios, especially for controllers like `ErrorDashboardController` and complex handler interactions (notifications, recovery).

---

## 🤝 Contributing

Contributions are welcome! Please adhere to Oracode principles and ensure tests cover any new functionality or changes. (Add more specific contribution guidelines if needed).

---

## 📄 License

The Ultra Error Manager is open-sourced software licensed under the [MIT license](LICENSE).
