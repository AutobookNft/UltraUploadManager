# 📘 UltraLogManager

**UltraLogManager** is the semantic logging core of the Ultra ecosystem.  
It provides enriched, structured, and future-proof logging for Laravel 11+ applications,  
fully aligned with the principles of [Oracode – The Doctrine of Living Code](https://github.com/ultra/oracode).

---

## 🎯 Purpose

UltraLogManager is built to:
- Centralize logging across all Ultra libraries
- Enrich log messages with caller context (class + method)
- Comply with PSR-3 (`LoggerInterface`)
- Remain fully testable, injectable, and facade-optional
- Support multilingual error feedback
- Include semantic metadata for debugging, analytics, and audits

---

## 🧱 Architecture

```
┌────────────────────────────┐
│ UltraLogManager (Core)    │◄───── Injected PSR-3 Logger (e.g. Monolog)
├────────────────────────────┤
│ Enriches Context           │
│ Detects Caller Class/Func  │
│ Supports All Log Levels    │
└────────────┬───────────────┘
             │
      CustomException → logs upon construction
             │
  UltraLog (Facade, test-safe) ← Optional
```

---

## ⚙️ Installation

```bash
composer require ultra/ultra-log-manager
```

### Laravel auto-discovery is enabled.

If needed manually:
```php
// config/app.php
'providers' => [
    Ultra\UltraLogManager\Providers\UltraLogManagerServiceProvider::class,
],
'aliases' => [
    'UltraLog' => Ultra\UltraLogManager\Facades\UltraLog::class,
],
```

---

## 🛠️ Configuration

After installation, publish the config:

```bash
php artisan vendor:publish --tag=ultra-log-config
```

### Key settings in `config/ultra_log_manager.php`:

| Key                     | Description                                           |
|------------------------|-------------------------------------------------------|
| `log_channel`          | Laravel log channel name (`error_manager` default)   |
| `log_level`            | Minimum log level (`debug`, `info`, etc.)            |
| `log_backtrace_depth`  | Where to start scanning the call stack (default: 3)  |
| `backtrace_limit`      | Maximum call stack depth (default: 7)                |
| `supported_languages`  | Languages for error messages (`it,en,fr,es,pt,de`)   |
| `devteam_email`        | Email address for critical error alerts              |
| `email_notifications`  | Enable/disable DevTeam notifications (default: false)|

---

## 📡 Usage Examples

### Basic Logging via Core Class

```php
use Ultra\UltraLogManager\UltraLogManager;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('custom');
$logger->pushHandler(new StreamHandler(storage_path('logs/custom.log')));

$ultraLogger = new UltraLogManager($logger);
$ultraLogger->info("UserLogin", "User has logged in", ['user_id' => 42]);
```

### Laravel-style Facade Logging

```php
UltraLog::info("UserModule", "Login successful", ['user_id' => 42]);
UltraLog::error("UploadModule", "File scan failed", ['filename' => 'malicious.pdf']);
```

---

## 📦 Custom Exceptions

```php
use Ultra\UltraLogManager\Exceptions\CustomException;
use Illuminate\Support\Facades\Log;

throw new CustomException("UPLOAD_FAILED", Log::channel('error_manager'));
```

Logs automatically:
```json
{
  "Class": "Ultra\\UltraLogManager\\Exceptions\\CustomException",
  "Method": "__construct",
  "StringCode": "UPLOAD_FAILED"
}
```

---

## 🌐 Language Support

Available translations:
- 🇮🇹 Italian
- 🇺🇸 English
- 🇫🇷 French
- 🇪🇸 Spanish
- 🇩🇪 German
- 🇵🇹 Portuguese

File structure:
```
resources/lang/{lang}/errors.php
```

---

## 🧪 Testing & Safety

- Fully injectable (constructor-based)
- No static state or global dependencies
- `UltraLog` facade safely falls back to `NullLogger` in tests
- Configurable log level and context

---

## 🔐 Oracode Compliance

ULM adheres to the 8 pillars of [Oracode](https://github.com/ultra/oracode), including:

- Explicitly Intentional documentation
- Semantic Enrichment via context metadata
- Backtrace-safe caller resolution
- GDPR-conscious logging
- Interpretable by future humans and AIs

Each method is annotated semantically for AI and human parsing.  
This package is more than a tool. It's a message to the future.

---

## 📜 License

MIT – Created by Fabio Cherici (fabiocherici@gmail.com)  
For the Ultra Ecosystem – built from trauma, designed for dignity.

---

## 💬 Questions?

**🧠 Designed for semantic clarity. If you're confused, it's a bug.**  
Open an issue or contact [Fabio](mailto:fabiocherici@gmail.com).
