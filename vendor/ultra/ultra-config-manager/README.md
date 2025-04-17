# UltraConfigManager (UCM)

## 📖 What is it?

**UltraConfigManager (UCM)** is a **versioned, auditable, secure, and Oracode-compliant** configuration management system designed for Laravel applications. It elevates configuration from static files or simple key-value stores to **live, critical data** that demands validation, robust tracking, security, and clear semantic intent.

Built for high-responsibility systems where configuration errors can have significant consequences, UCM provides developers with confidence and traceability.

[![Latest Version](https://img.shields.io/packagist/v/ultra/ultra-config-manager.svg?style=flat-square)](https://packagist.org/packages/ultra/ultra-config-manager)
[![Total Downloads](https://img.shields.io/packagist/dt/ultra/ultra-config-manager.svg?style=flat-square)](https://packagist.org/packages/ultra/ultra-config-manager)
[![License](https://img.shields.io/packagist/l/ultra/ultra-config-manager.svg?style=flat-square)](https://packagist.org/packages/ultra/ultra-config-manager)

---

## ✨ Core Principles (Oracode Aligned)

-   **Intentional & Interrogable:** Configuration changes are tracked with clear purpose (audit logs, versions). The system's state is queryable.
-   **Secure:** Sensitive values are automatically encrypted at rest (`EncryptedCast`). Access is controlled via middleware.
-   **Versioned & Auditable:** Every change creates a new version and an audit record, linked to the user responsible, allowing full traceability and potential rollbacks.
-   **Robust & Testable:** Designed with Dependency Injection, free from internal Facade coupling, promoting high testability and reliability. Errors are handled via specific exceptions.
-   **Semantically Coherent:** Uses Enums (`CategoryEnum`) and constants (`GlobalConstants`) for clarity and consistency.

---

## 🎯 Objectives

-   **Protect** critical configuration data with encryption and access control.
-   **Track** every change with comprehensive versioning and user-linked audit trails.
-   **Validate** configuration data (keys, types, categories).
-   **Centralize** configuration management through a consistent API and optional UI.
-   **Enable** flexible authorization using Spatie/laravel-permission or a simple role fallback.
-   **Improve** developer awareness, system observability, and long-term maintainability.

---

## 🧠 Architecture

UCM follows a clean, layered architecture promoting separation of concerns:

-   **`UltraConfigManager` (Service):** The central orchestrator, providing the public API. Manages in-memory state, caching, and coordinates interactions.
-   **`ConfigDaoInterface` (Contract):** Defines the contract for data persistence.
-   **`EloquentConfigDao` (Implementation):** The default DAO implementation using Eloquent ORM. Handles atomic database operations (config + version + audit) via transactions.
-   **Models:**
    -   `UltraConfigModel`: Represents the main configuration entry (`uconfig` table), includes `EncryptedCast` and `SoftDeletes`.
    -   `UltraConfigVersion`: Represents a historical version (`uconfig_versions` table).
    -   `UltraConfigAudit`: Records a change event (`uconfig_audit` table), linking to `User`.
-   **Services:**
    -   `VersionManager`: Calculates sequential version numbers.
-   **Casts:**
    -   `EncryptedCast`: Automatically encrypts/decrypts sensitive values.
-   **Enums & Constants:**
    -   `CategoryEnum`: Defines valid configuration categories.
    -   `GlobalConstants`: Provides shared constant values (e.g., `NO_USER`).
-   **HTTP Layer:**
    -   `UltraConfigController`: Handles web requests for the optional UI. Interacts *only* with `UltraConfigManager`.
    -   `CheckConfigManagerRole` (Middleware): Authorizes access based on permissions/roles.
-   **Console:**
    -   `UConfigInitializeCommand`: Post-installation setup command.
-   **Exceptions:** Custom exceptions (`PersistenceException`, `ConfigNotFoundException`, `DuplicateKeyException`) for semantic error handling.
-   **DTOs:** Data Transfer Objects (`ConfigDisplayData`, `ConfigEditData`, `ConfigAuditData`) for structured data exchange between layers (e.g., Manager to Controller).
-   **Facade:**
    -   `UConfig`: Optional static proxy for convenient access to the `UltraConfigManager` service.

---

## ⚙️ How It Works

1.  **Access:** Interact with configuration via the `UConfig` Facade or by injecting the `UltraConfigManager` service.
2.  **Retrieval (`get`, `has`, `all`):** Primarily reads from an in-memory cache for performance. Falls back to database (via DAO) and environment variables if cache is missed or disabled.
3.  **Mutation (`set`, `delete`):**
    -   Operations are delegated to the `UltraConfigManager`.
    -   The Manager calls the `ConfigDaoInterface` (`saveConfig` or `deleteConfigByKey`).
    -   The DAO performs the database operation (create/update/soft-delete) **atomically** within a transaction.
    -   If requested, the DAO also creates `UltraConfigVersion` and `UltraConfigAudit` records within the same transaction.
    -   On successful persistence, the Manager updates its in-memory state and refreshes the external cache.
4.  **Encryption:** The `EncryptedCast` automatically encrypts values before they are saved by the DAO/Model and decrypts them upon retrieval.
5.  **Error Handling:** Failures in persistence or other operations result in specific exceptions (`PersistenceException`, etc.) being thrown, allowing for robust error handling by the caller (e.g., the Controller using `UltraErrorManager`).

---

## 🔐 Permissions

UCM provides flexible authorization:

-   **Spatie Integration:** If `config('uconfig.use_spatie_permissions')` is `true` (and `spatie/laravel-permission` is installed), the `CheckConfigManagerRole` middleware uses `$user->hasPermissionTo('permission-name')`. Required permissions are typically:
    -   `view-config`
    -   `create-config`
    -   `update-config`
    -   `delete-config`
-   **Fallback Role:** If `use_spatie_permissions` is `false`, the middleware checks for a specific role property/attribute on the authenticated user (default assumes `$user->role`). The mapping is:
    -   `view-config`: Requires 'ConfigViewer' (or Editor/Manager)
    -   `create-config`, `update-config`: Requires 'ConfigEditor' (or Manager)
    -   `delete-config`: Requires 'ConfigManager'
-   **Middleware Alias:** `uconfig.check_role` (applied in `routes/uconfig.php`).

---

## 🚀 Installation

**Requirements:**

-   **PHP:** `^8.2` (Due to usage of modern PHP features like `readonly class`)
-   **Laravel:** `^11.0` (Based on dependencies, verify compatibility if needed for other versions)
-   **Database:** A configured Laravel database connection.

**Steps:**

1.  **Install via Composer:**
    ```bash
    composer require ultra/ultra-config-manager
    ```

2.  **Publish Resources:** This publishes migrations, config file, views, translations, and routes.
    ```bash
    php artisan vendor:publish --tag=uconfig-resources
    ```

3.  **Run Migrations:** Create the necessary database tables.
    ```bash
    php artisan migrate
    ```

4.  **Configure (Optional):** Edit the published `config/uconfig.php` file to adjust settings like Spatie integration, cache, or table names if necessary.

5.  **Add Facade Alias (Optional):** If Facade auto-discovery fails or you prefer explicit aliases, add the following to the `aliases` array in your `config/app.php`:
    ```php
    'UConfig' => \Ultra\UltraConfigManager\Facades\UConfig::class,
    ```

6.  **Initialize (Optional):** Run the initialization command. It checks table existence and displays guidance.
    ```bash
    php artisan uconfig:initialize
    ```

---

## ⚙️ Configuration (`config/uconfig.php`)

The main configuration file allows you to customize UCM's behavior:

-   `database.table` (default: `'uconfig'`): Name of the main configuration table.
-   `cache.enabled` (default: `true`): Enable/disable configuration caching.
-   `cache.ttl` (default: `3600`): Cache Time-To-Live in seconds.
-   `use_spatie_permissions` (default: `true`): Set to `true` to use `spatie/laravel-permission` for authorization, `false` for the simple role fallback.

*(Note: Ensure this section accurately reflects all keys present in the published `uconfig.php` file.)*

---

## 📦 Resource Publishing

Running `php artisan vendor:publish --tag=uconfig-resources` publishes:

-   **Migrations:** Creates `uconfig`, `uconfig_versions`, `uconfig_audit` tables. File names include timestamps for ordering.
-   **Configuration:** `config/uconfig.php`.
-   **Views:** To `resources/views/vendor/uconfig/` for UI customization.
-   **Translations:** To `resources/lang/vendor/uconfig/` for localization.
-   **Routes:** `routes/uconfig.php` defining the web UI endpoints.
-   **Seeder (Stub):** `database/seeders/PermissionSeeder.php` (stub for creating default Spatie permissions/roles). *Caution: May overwrite existing file.*

---

## 🔁 Route Loading

The published `routes/uconfig.php` file is loaded automatically by the `UConfigServiceProvider`, including the necessary `web` middleware group. You typically don't need to register it manually in `bootstrap/app.php`.

---

## 🧪 Testing

UCM is designed for testability:

-   **Dependency Injection:** Core classes (`UltraConfigManager`, `EloquentConfigDao`, etc.) receive dependencies via constructor, allowing easy mocking in tests.
-   **No Internal Facades:** Core logic is free from static Facade calls, simplifying unit testing.
-   **Custom Exceptions:** Specific exceptions (`PersistenceException`, etc.) allow predictable testing of error conditions.
-   **Testing Strategy:**
    -   **Unit Tests:** Mock dependencies (DAO, Cache, Logger) to test Manager logic in isolation. Test DAO methods by mocking DB interactions or using an in-memory database.
    -   **Feature Tests:** Use Laravel's HTTP testing helpers (`$this->get`, `$this->post`, etc.) to test the Controller, Middleware, and Routes. You can mock the `UltraConfigManager` at this level using `swap` or `partialMock`.
-   **UltraErrorManager:** While not used internally by UCM core, `UltraErrorManager` can be used in your application's Exception Handler or Controllers to catch exceptions thrown by UCM and provide standardized error responses/logging.

---

## 🌍 Translation

-   UI labels and messages are translatable via standard Laravel language files.
-   Translations are published to `resources/lang/vendor/uconfig`.
-   The `CategoryEnum` includes methods (`translatedName`, `translatedOptions`) to retrieve translated category labels.

---

## ⛳ Credits & Philosophy

This package embodies the **Oracode** philosophy: code should not just function, but endure, communicate its intent, protect its data, and be inherently testable and robust. Inspired by the critical need for reliable configuration in high-stakes environments.

Developed by Fabio Cherici.

---

*Generated/Updated: 2024-MM-DD* <!-- Update with current date -->