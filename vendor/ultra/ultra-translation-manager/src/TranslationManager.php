<?php

declare(strict_types=1);

namespace Ultra\TranslationManager;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\Translator as LaravelTranslator;
// Usa le interfacce specifiche di UTM
use Ultra\TranslationManager\Interfaces\ErrorReporter as UtmErrorReporterInterface;
use Ultra\TranslationManager\Interfaces\LoggerInterface as UtmLoggerInterface;
use Throwable;

/**
 * 🎯 Ultra Translation Manager (UTM) – Oracoded Core (Standalone Version)
 *
 * Manages translations for the Ultra ecosystem, implementing Laravel's Translator
 * contract. Handles package-specific loading, optional caching, and delegates
 * functionality to injected dependencies (Laravel Translator, Cache, Filesystem,
 * standard PSR-3 Logger via UTM interfaces).
 * This version is Facade-free and **independent of UltraLogManager (ULM)**.
 *
 * 🧱 Structure:
 * - Implements TranslatorContract.
 * - Injects Application, CacheFactory, Filesystem, UtmLoggerInterface, UtmErrorReporterInterface, config array.
 * - Stores loaded translations in memory (`$translations`).
 * - Uses setter injection for the core LaravelTranslator instance.
 * - Parses keys (`package::group.item`) for package/group identification.
 * - Fetches translations from memory, cache (if enabled), or LaravelTranslator fallback.
 * - Uses injected logger/error reporter (now based on standard PSR-3 logger).
 *
 * 📡 Communicates:
 * - With Laravel Application container (via injected $app) for locale management.
 * - With Cache system (via injected $cache) for storing/retrieving translations.
 * - With Filesystem (via injected $files) for reading language directories/files.
 * - With injected UtmLoggerInterface (DefaultLogger -> PSR-3) for logging activities.
 * - With injected UtmErrorReporterInterface (DefaultErrorReporter -> PSR-3) for reporting issues.
 * - With injected LaravelTranslator for fallback translation retrieval and `choice` method.
 *
 * 🧪 Testable:
 * - All external dependencies are injected via constructor or setter.
 * - No static dependencies.
 * - Independent of ULM, relies on standard PSR-3 logger contract.
 *
 * @package Ultra\TranslationManager
 * @version 1.2 (Oracode Standalone Refactored)
 */
final class TranslationManager implements TranslatorContract
{
    // --- Nessuna modifica necessaria alle proprietà ---
    /** @var array<string, array<string, array<string, string|array>>> */
    protected array $translations = [];
    /** @var LaravelTranslator|null */
    protected ?LaravelTranslator $laravelTranslator = null;
    protected readonly Application $app;
    protected readonly CacheFactory $cache;
    protected readonly Filesystem $files;
    protected readonly UtmLoggerInterface $logger; // Questa ora punta a DefaultLogger(PSR-3)
    protected readonly UtmErrorReporterInterface $errorReporter; // Questa ora punta a DefaultErrorReporter(PSR-3)
    protected readonly array $config;

    /**
     * 🎯 Constructor: Injects all required dependencies and configuration.
     * Dependencies now include standard Laravel/PSR components via UTM interfaces.
     *
     * @param Application $app Laravel application instance.
     * @param CacheFactory $cache Cache factory for accessing cache stores.
     * @param Filesystem $files Filesystem instance for file operations.
     * @param UtmLoggerInterface $logger Logger instance (DefaultLogger wrapping PSR-3).
     * @param UtmErrorReporterInterface $errorReporter Error reporter instance (DefaultErrorReporter wrapping PSR-3).
     * @param array $config Configuration array loaded from translation-manager.php.
     */
    public function __construct(
        Application $app,
        CacheFactory $cache,
        Filesystem $files,
        UtmLoggerInterface $logger, // Iniezione non cambia, ma l'implementazione sottostante sì
        UtmErrorReporterInterface $errorReporter, // Iniezione non cambia
        array $config
    ) {
        $this->app = $app;
        $this->cache = $cache;
        $this->files = $files;
        $this->logger = $logger;
        $this->errorReporter = $errorReporter;
        $this->config = $config;

        // Log iniziale non cambia
        $this->logger->debug(
            "[UTM] TranslationManager instantiated (Standalone Mode). Cache enabled: " .
            ($this->config['cache_enabled'] ? 'YES' : 'NO')
        );
    }

    /**
     * 🎯 Inject the Laravel Translator instance.
     * 
     * @param LaravelTranslator $translator The translator instance to inject.
     * @return void
     */
    public function setLaravelTranslator(LaravelTranslator $translator): void
    {
        $this->laravelTranslator = $translator;
        $this->logger->debug("[UTM] LaravelTranslator injected into TranslationManager.");
    }

    /**
     * {@inheritdoc}
     * Adheres strictly to the basic Translator interface definition provided.
     */
    public function get($key, array $replace = [], $locale = null) // Firma come da interfaccia base
    {
        $fallback = true; // Comportamento di default interno

        $this->logger->debug("[UTM GET ENTRY] Request for key: '{$key}', locale: '{$locale}'");
        $key = (string) $key;
        $locale = $locale === null ? null : (string) $locale;

        [$package, $group, $item] = $this->parseKey($key);
        $currentLocale = $locale ?? $this->getLocale();

        $cacheKey = $this->buildCacheKey($package, $group, $item, $currentLocale);

        if ($this->config['cache_enabled'] ?? false) {
            $translation = $this->cache->store()->rememberForever($cacheKey, function () use ($group, $item, $package, $currentLocale, $replace, $fallback) {
                return $this->fetchTranslation($group, $item, $package, $currentLocale, $replace, $fallback);
            });

            $missingMarker = $this->getMissingKeyMarker($package, $group, $item);
            if ($translation === $missingMarker) {
                 $this->logger->debug("[UTM] 'Missing' marker found in cache for '{$cacheKey}'. Returning original key via LaravelTranslator.");
                 return $this->laravelTranslator ? $this->laravelTranslator->get($key, $replace, $currentLocale, true) : $key;
            }
             $this->logger->debug("[UTM GET CACHE HIT] Returning cached: '{$cacheKey}'");

        } else {
            $translation = $this->fetchTranslation($group, $item, $package, $currentLocale, $replace, $fallback);
            $missingMarker = $this->getMissingKeyMarker($package, $group, $item);
             if ($translation === $missingMarker) {
                 $this->logger->debug("[UTM] 'Missing' marker found during fetch for key '{$key}'. Returning original key via LaravelTranslator.");
                 return $this->laravelTranslator ? $this->laravelTranslator->get($key, $replace, $currentLocale, true) : $key;
            }
             $this->logger->debug("[UTM GET NO CACHE] Returning fetched value for key '{$key}'");
        }

        return is_string($translation) ? $this->replacePlaceholders($translation, $replace) : $translation;
    }

    /**
     * {@inheritdoc}
     * Adheres strictly to the basic Translator interface definition provided.
     */
    public function setLocale($locale): void // Firma come da interfaccia base
    {
        $locale = (string) $locale;
        if ($this->app->getLocale() !== $locale) {
            $this->app->setLocale($locale);
            $this->logger->info("[UTM] Application locale set to: {$locale}");
        }
    }

   /**
     * 🎯 Gets the default locale being used from the injected Application instance.
     *
     * @return string The current application locale.
     */
    public function getLocale(): string // <- Abbiamo il return type hint 'string'
    {
        // Use injected application instance
        return $this->app->getLocale();
    }

    /**
     * {@inheritdoc}
     * Delegates directly to the injected LaravelTranslator.
     */
    public function choice($key, $number, array $replace = [], $locale = null): string
    {
        if (!$this->laravelTranslator) {
            $this->logger->error("[UTM] choice() called but LaravelTranslator is unavailable!");
            return (string) $key; // Cast a string per sicurezza
        }
        $this->logger->debug("[UTM] choice() called for: key='{$key}'. Delegating to LaravelTranslator.");
        return $this->laravelTranslator->choice($key, $number, $replace, $locale);
    }

     /**
     * {@inheritdoc}
     * Delegates to the injected Laravel translator if available.
     */
    public function addNamespace(string $namespace, string $hint): void
    {
        if ($this->laravelTranslator && method_exists($this->laravelTranslator, 'addNamespace')) {
            $this->logger->debug("[UTM] addNamespace called for namespace '{$namespace}' with hint '{$hint}'. Delegating to LaravelTranslator.");
            $this->laravelTranslator->addNamespace($namespace, $hint);
        } else {
            $this->logger->error("[UTM] addNamespace called but LaravelTranslator is unavailable or lacks the method!");
        }
    }

     /**
     * 🎯 Registers translation files for a specific package (Standalone Mode).
     * Loads translations from the package's language directory into memory.
     * Uses injected Filesystem, Logger, and ErrorReporter.
     *
     * @param string $package The package name (used as the translation namespace/group).
     * @param string $baseLangPath The base language directory path for this package.
     * @return void
     */
    public function registerPackageTranslations(string $package, string $baseLangPath): void
    {
    
         if (!$this->files->isDirectory($baseLangPath)) {
            $this->errorReporter->report('TRANSLATION_PATH_INVALID', ['package' => $package, 'path' => $baseLangPath]);
            $this->logger->warning("[UTM Register] Invalid base path for package '{$package}': {$baseLangPath}");
            return;
        }
    
         $locales = $this->getAvailableLocales($baseLangPath);
         if (empty($locales)) {
            $this->errorReporter->report('TRANSLATION_LOCALES_NOT_FOUND', ['package' => $package,'path' => $baseLangPath]);
            $this->logger->warning("[UTM Register] No language directories found in: {$baseLangPath}");
             return;
         }
         foreach ($locales as $locale) {
            $translationFilePath = $this->buildTranslationFilePath($baseLangPath, $locale, $package);
             $this->logger->debug("[UTM Register] Checking file: {$translationFilePath}");
             if ($this->files->exists($translationFilePath)) {
                 try {
                    $translations = $this->files->getRequire($translationFilePath);
                     if (!is_array($translations)) {
                        $this->errorReporter->report('TRANSLATION_FILE_INVALID', ['package' => $package,'locale' => $locale,'file' => $translationFilePath]);
                         $this->logger->warning("[UTM Register] File did not return an array: {$translationFilePath}");
                         continue;
                     }
                     $this->loadTranslationsIntoMemory($package, $locale, $translations);
                     $this->logger->debug("[UTM Register] Translations loaded successfully for '{$package}' [{$locale}].");
                 } catch (Throwable $e) {
                    $this->errorReporter->report('TRANSLATION_PROCESSING_ERROR', ['package' => $package,'locale' => $locale,'file' => $translationFilePath,'exception_message' => $e->getMessage()], $e);
                     $this->logger->error("[UTM Register] Error processing file: {$translationFilePath} - Error: " . $e->getMessage());
                 }
             } else {
                 $this->logger->debug("[UTM Register] File not found: {$translationFilePath}");
             }
         }
    }

    
    /**
     * 🧱 Fetches a translation (Standalone Mode). Checks memory, cache, then Laravel fallback.
     * Returns a unique marker string if the key is ultimately not found.
     * Uses injected Logger and LaravelTranslator.
    */
     protected function fetchTranslation(?string $group, string $item, ?string $package, string $locale, array $replace, bool $fallback): string|array
     {
         $this->logger->debug("[UTM Fetch] Starting fetch for item='{$item}', group='{$group}', package='{$package}', locale='{$locale}'");
         // 1. Check In-Memory Cache
         if ($package && isset($this->translations[$package][$locale])) {
             $keyInDotNotation = $group ? "{$group}.{$item}" : $item;
             if (\Illuminate\Support\Arr::has($this->translations[$package][$locale], $keyInDotNotation)) {
                $translation = \Illuminate\Support\Arr::get($this->translations[$package][$locale], $keyInDotNotation);
                 $this->logger->debug("[UTM Fetch] Item '{$keyInDotNotation}' FOUND in-memory (package: '{$package}').");
                 return $translation;
             } else {
                 $this->logger->debug("[UTM Fetch] Item '{$keyInDotNotation}' NOT found in-memory in [{$package}][{$locale}]. Proceeding to fallback...");
             }
         } else {
             $this->logger->debug("[UTM Fetch] In-memory array for '{$package}' [{$locale}] not found or package is null. Proceeding to fallback...");
         }
         // 2. Fallback to Laravel Translator
         if (!$this->laravelTranslator) {
             $this->logger->error("[UTM Fetch] Fallback requested but LaravelTranslator is unavailable!");
             return $this->getMissingKeyMarker($package, $group, $item);
         }
         $laravelKey = $package ? "{$package}::" : '';
         $laravelKey .= $group ? "{$group}.{$item}" : $item;
         $this->logger->debug("[UTM Fetch] Attempting fallback with Laravel key: '{$laravelKey}'");
         $translation = $this->laravelTranslator->get($laravelKey, $replace, $locale, false);
         if ($translation === $laravelKey) {
             if ($fallback) {
                 $this->logger->debug("[UTM Fetch] Key '{$laravelKey}' not found in '{$locale}', trying Laravel's fallback locale mechanism.");
                 $translation = $this->laravelTranslator->get($laravelKey, $replace, $locale, true);
                 if ($translation === $laravelKey) {
                     $this->logger->warning("[UTM Fetch] Fallback failed to find key: '{$laravelKey}' even with fallback locale.");
                     return $this->getMissingKeyMarker($package, $group, $item);
                 } else {
                     $this->logger->debug("[UTM Fetch] Key '{$laravelKey}' FOUND via Laravel fallback locale.");
                     return $translation;
                 }
             } else {
                 $this->logger->warning("[UTM Fetch] Fallback failed to find key: '{$laravelKey}' (fallback disabled).");
                 return $this->getMissingKeyMarker($package, $group, $item);
             }
         } else {
             $this->logger->debug("[UTM Fetch] Key '{$laravelKey}' FOUND via LaravelTranslator in locale '{$locale}'.");
             return $translation;
         }
     }

    /**
     * 🧱 Loads translations into memory (Standalone Mode).
     * Uses injected Logger.
     * 
     */
     protected function loadTranslationsIntoMemory(string $package, string $locale, array $translations): void
     {
         if (!isset($this->translations[$package])) {
             $this->translations[$package] = [];
         }
         $this->translations[$package][$locale] = array_replace_recursive(
             $this->translations[$package][$locale] ?? [],
             $translations
         );
         $this->logger->debug("[UTM Memory Load] Merged translations for '{$package}' [{$locale}].");
     }


    /**
     * 🧱 Finds available locale directories (Standalone Mode).
     * Uses injected Filesystem and Logger.
     * 
     */
     protected function getAvailableLocales(string $baseLangPath): array
     {
        $locales = [];
         if ($this->files->isDirectory($baseLangPath)) {
            $directories = $this->files->directories($baseLangPath);
             foreach ($directories as $directory) {
                $localeCode = basename($directory);
                 if (preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $localeCode)) {
                    $locales[] = $localeCode;
                 } else {
                    $this->logger->debug("[UTM] Ignoring directory '{$localeCode}' in '{$baseLangPath}' as it doesn't match locale format.");
                 }
             }
         }
         $this->logger->debug("[UTM] Locales found in '{$baseLangPath}': " . implode(', ', $locales));
         return $locales;
     }

    /**
     * 🧱 Builds the translation file path (Standalone Mode).
     * 
     */
     protected function buildTranslationFilePath(string $baseLangPath, string $locale, string $package): string
     {
         return rtrim($baseLangPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
                $locale . DIRECTORY_SEPARATOR . $package . '.php';
     }

    /**
     * 🧱 Builds the cache key (Standalone Mode).
     * 
     */
     protected function buildCacheKey(?string $package, ?string $group, string $item, string $locale): string
     {
        $prefix = $this->config['cache_prefix'] ?? 'utm';
         $packageSegment = $package ?? 'APP';
         $keySegment = $group ? "{$group}.{$item}" : $item;
         return "{$prefix}.{$locale}.{$packageSegment}.{$keySegment}";
     }


    /**
     * 🧱 Returns the missing key marker (Standalone Mode).
     * 
     */
     protected function getMissingKeyMarker(?string $package, ?string $group, string $item): string
     {
         // Aggiungo $locale al marker per renderlo ancora più univoco se necessario
         $locale = $this->getLocale();
         return "@@__UTM_MISSING_{$locale}_{$package}::{$group}.{$item}__@@";
     }

    /**
     * 🧱 Parses a key into package, group, item (Standalone Mode).
     * Uses injected Logger.
     * 
     */
     protected function parseKey(string $key): array
     {
         $package = null;
         $groupAndItem = $key;
         if (str_contains($key, '::')) {
             [$package, $groupAndItem] = explode('::', $key, 2);
         }
         $group = null;
         $item = $groupAndItem;
         $dotPosition = strpos($groupAndItem, '.');
         if ($dotPosition !== false) {
             $potentialGroup = substr($groupAndItem, 0, $dotPosition);
             $potentialItem = substr($groupAndItem, $dotPosition + 1);
             $group = $potentialGroup;
             $item = $potentialItem;
         }
         $this->logger->debug("[UTM ParseKey] Key:'{$key}' -> Package:'{$package}', Group:'{$group}', Item:'{$item}'");
         return [$package, $group, $item];
     }


    /**
     * 🧱 Replaces placeholders (Standalone Mode).
     * 
     */
     protected function replacePlaceholders(string $translation, array $replace): string
     {
         if (empty($replace) || !str_contains($translation, ':')) {
             return $translation;
         }
         foreach ($replace as $key => $value) {
             $placeholder = ':' . ltrim((string)$key, ':');
             $translation = str_replace($placeholder, (string)$value, $translation);
         }
         return $translation;
     }
}