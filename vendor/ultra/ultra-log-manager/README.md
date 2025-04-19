# Ultra Log Manager (ULM) – Living Logging for the Ultra Ecosystem

> *“Code must speak even when its author is gone.”* – Oracode Pillar 6 *(Interrogable)*

ULM is a thin, **PSR‑3–compatible** wrapper around Monolog that auto‑adds caller
(class & method) to every log entry and embraces the Oracode doctrine.

* **Framework‑agnostic core** – works in any PHP ≥ 8.1 project.
* **First‑class Laravel integration** via `UltraLogManagerServiceProvider`.
* **Sanitizer contract** (`ContextSanitizerInterface`) to keep logs GDPR‑safe.
* **Fail‑fast philosophy** – mis‑configuration surfaces immediately.

---

##  🚀 Installation

```bash
composer require ultra/ultra-log-manager
```

Laravel 11+ auto‑discovers the service provider; for other frameworks see
*Manual bootstrap* below.

---

##  🔧 Configuration

```bash
php artisan vendor:publish --tag=ultra-log-config
```
creates `config/ultra_log_manager.php` with:

| Key | Meaning | Default |
|-----|---------|---------|
| `log_channel` | Default channel name | `ultra_log_manager` |
| `log_level`   | PSR‑3 string level (`debug`, `info`, …) | `debug` |
| `log_backtrace_depth` / `backtrace_limit` | Introspection depth for caller info | `3 / 7` |

### Choosing channels per class

```php
$this->app->when(App\Services\OrderService::class)
          ->needs(Ultra\UltraLogManager\UltraLogManager::class)
          ->give(function () {
              $m = new Monolog\Logger('order');
              $m->pushHandler(new StreamHandler(storage_path('logs/order.log')));
              return new UltraLogManager($m);
          });
```
No code change inside `OrderService`; it just requests `UltraLogManager`.

---

##  🛡️ Context Sanitizer

| Contract | Default binding | Swap example |
|-----------|-----------------|--------------|
| `ContextSanitizerInterface` | `NoOpSanitizer` (pass‑through) | In `AppServiceProvider`:<br>`$this->app->singleton(ContextSanitizerInterface::class, DefaultContextSanitizer::class);` |

Callers sanitize *before* logging:

```php
$context = $sanitizer->sanitize(['email' => $userEmail, 'ip' => $ip]);
$log->info('User login', $context);
```

---

##  💻 Usage

### Laravel / DI

```php
final class CheckoutController
{
    public function __construct(private UltraLogManager $log) {}

    public function store(Request $r): Response
    {
        $this->log->info('Order placed', ['order_id' => 42]);
        // …
    }
}
```

### Plain PHP

```php
$mono = new Monolog\Logger('cli');
$mono->pushHandler(new StreamHandler(__DIR__.'/cli.log'));
$log = new UltraLogManager($mono);
$log->warning('Cron started');
```

---

##  🧪 Oracular Tests (Example)

```php
#[Test]
public function it_enriches_context(): void
{
    $mono = new TestLogger(); // Monolog test handler
    $ul   = new UltraLogManager($mono);

    $ul->info('Ping');

    $record = $mono->records[0];
    $this->assertArrayHasKey('Class', $record['context']);
}
```

---

##  📡 Fail‑Fast Philosophy

ULM throws **InvalidArgumentException** (Monolog) or IO errors during boot if
log path is unwritable. Do **not** silence them – fix the config or directory
permissions. *(Pillar 5 – Variation‑Ready)*

---

##  📜 Changelog

See `CHANGELOG.md` for version history (semantic‑versioned, `‑oracode` suffix
indicates full compliance with Oracode v1.5).

---

##  🖋️ Credits

Fabio Cherici – Ultra Ecosystem

