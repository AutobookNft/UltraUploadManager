# 🌍 Ultra Translation Manager (UTM)

**Version:** 1.2 – _Oracoded Standalone Refactored_  
**Author:** Fabio Cherici  
**License:** MIT  
**Laravel Compatibility:** ^11.0  
**PHP Compatibility:** ^8.1 | ^8.2 | ^8.3

---

## 🎯 Purpose

Ultra Translation Manager (UTM) is a fully standalone, facade-free translation system designed for the **Ultra Ecosystem**.  
It replaces Laravel’s internal translation logic with a more modular, cacheable and semantically documented layer—aligned with the Oracode philosophy.

---

## 🧱 Structure

- **Main Components:**
  - `TranslationManager`: Core orchestrator.
  - `DefaultLogger`: PSR-3 logger wrapper.
  - `DefaultErrorReporter`: PSR-3 error reporter.
  - `UltraTranslationServiceProvider`: Laravel integration point.
  - `UltraTrans`: Optional Facade (for compatibility).
- **Interfaces:**
  - `LoggerInterface`
  - `ErrorReporter`

- **Config File:** `config/translation-manager.php`
- **Translations Path:** `resources/lang/{locale}/core.php`

---

## 🔌 Installation

```bash
composer require ultra/ultra-translation-manager
php artisan vendor:publish --tag=utm-config
```

> UTM does **not** depend on any other Ultra packages.  
> It uses Laravel’s native PSR-3 logger via dependency injection.

---

## ⚙️ Configuration

```php
return [
    'default_locale' => 'en',
    'available_locales' => ['en', 'it', 'fr', 'es', 'de', 'pt'],
    'fallback_locale' => 'en',
    'cache_enabled' => env('TRANSLATION_CACHE_ENABLED', false),
    'cache_prefix' => 'ultra_translations',
];
```

---

## 📡 Usage

UTM can be used either via dependency injection or via the optional `UltraTrans` facade.

```php
use Ultra\TranslationManager\TranslationManager;

// Dependency injection (recommended)
$utm = app(TranslationManager::class);
echo $utm->get('core.upload.success');
```

---

## 🧪 Testing & Observability

UTM is:
- Fully PSR-compliant (Logger + Error handling).
- Audit-friendly (Oracoded structured logging).
- Independently testable (no static dependencies).

---

## 🧠 Oracode Compliance

This package adheres to the [📜 Oracode Doctrine](https://link-to-oracode-docs), respecting all 8 pillars:
- Explicit Intention
- Semantic Coherence
- Contextual Autonomy
- Interpretability
- Variational Readiness
- Interrogability
- Tolerance to Imperfect Transmission
- Linguistic Universality (English only)

> It protects both developer memory and user privacy. GDPR-aware by design.

---

## 🔧 Artisan Command

```bash
php artisan ultra:translations:publish
```

Publishes config file to `config/translation-manager.php`.

---

## 📁 Folder Structure

```
src/
├── Commands/
├── ErrorReporters/
├── Facades/
├── Interfaces/
├── Loggers/
├── Providers/
├── TranslationManager.php
config/
└── translation-manager.php
resources/
└── lang/
    └── en/
        └── core.php
```

---

## 🛡️ Error Handling

All errors are routed through `DefaultErrorReporter`, logged via PSR-3 logger, and contextualized semantically.  
No Laravel exceptions are thrown directly.

---

## 📜 License

This library is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).