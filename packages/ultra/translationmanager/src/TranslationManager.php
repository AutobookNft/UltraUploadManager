<?php

namespace Ultra\TranslationManager;

use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Translation\Translator as LaravelTranslator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Ultra Translation Manager (UTM).
 *
 * Manages translations for the Ultra ecosystem, implementing Laravel's Translator
 * contract with package-specific loading and optional caching.
 *
 * @package Ultra\TranslationManager
 * @author [Fabio Cherici]
 * @version 1.0
 */
class TranslationManager implements TranslatorContract
{
    /**
     * Loaded translations from registered packages.
     * Structure: ['packageName']['locale']['key'] => 'translation'
     *
     * @var array
     */
    protected array $translations = [];

    /**
     * Configuration loaded from config/translation-manager.php.
     *
     * @var array
     */
    protected array $config;

    /**
     * Instance of Laravel's standard translator for delegation/fallback.
     *
     * @var LaravelTranslator|null
     */
    protected ?LaravelTranslator $laravelTranslator = null;

    /**
     * Constructor: Initializes configuration only.
     */
    public function __construct()
    {
        $this->config = config('translation-manager') ?? [
            'default_locale' => env('APP_LOCALE', 'en'),
            'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
            'cache_enabled' => env('TRANSLATION_CACHE_ENABLED', false),
            'cache_prefix' => 'utm_translations',
        ];
        Log::debug("[UTM] TranslationManager instantiated. Cache enabled: " . ($this->config['cache_enabled'] ? 'YES' : 'NO'));
    }

    /**
     * Adds a new namespace to the loader, delegating to the injected Laravel translator.
     *
     * @param string $namespace The namespace to add
     * @param string $hint The path hint for the namespace
     * @return void
     */
    public function addNamespace(string $namespace, string $hint): void
    {
        if ($this->laravelTranslator && method_exists($this->laravelTranslator, 'addNamespace')) {
            Log::debug("[UTM] addNamespace called for namespace '{$namespace}' with hint '{$hint}'. Delegating to LaravelTranslator.");
            $this->laravelTranslator->addNamespace($namespace, $hint);
        } else {
            Log::error("[UTM] addNamespace called but LaravelTranslator is unavailable or lacks the method!");
        }
    }

    /**
     * Sets the instance of Laravel's standard translator.
     *
     * @param LaravelTranslator $translator The translator instance to inject
     * @return void
     */
    public function setLaravelTranslator(LaravelTranslator $translator): void
    {
        $this->laravelTranslator = $translator;
        Log::debug("[UTM] LaravelTranslator injected into TranslationManager.");
    }

    //--------------------------------------------------------------------------
    // Required methods from TranslatorContract
    //--------------------------------------------------------------------------

    /**
     * Gets the translation for a given key.
     *
     * @param string $key The translation key
     * @param array $replace Array of replacements
     * @param string|null $locale The locale to use, or null for default
     * @return mixed
     */
    public function get($key, array $replace = [], $locale = null)
    {
        $fallback = true;
        Log::debug("[UTM GET ENTRY] Request for key: '{$key}', locale: '{$locale}'");
        list($package, $group, $item) = $this->parseKey($key);
        $locale = $locale ?? $this->getLocale();

        $cacheKey = ($this->config['cache_prefix'] ?? 'utm') . ".{$locale}." . ($package ?? 'APP') . "." . ($group ?? '') . "." . $item;

        if ($this->config['cache_enabled'] ?? true) {
            $translation = Cache::rememberForever($cacheKey, function () use ($group, $item, $package, $locale, $replace, $fallback) {
                return $this->fetchTranslation($group, $item, $package, $locale, $replace, $fallback);
            });
            $originalKey = $key;
            if ($translation === $this->getMissingKeyMarker($package, $group, $item)) {
                Log::debug("[UTM] 'Missing' value found in cache for '{$cacheKey}'.");
                return $originalKey;
            }
        } else {
            $translation = $this->fetchTranslation($group, $item, $package, $locale, $replace, $fallback);
            $originalKey = $key;
            if ($translation === $this->getMissingKeyMarker($package, $group, $item)) {
                return $originalKey;
            }
        }
        Log::debug("[UTM GET RETURN] Returning: '{$translation}'");
        return (string) $translation;
    }

    /**
     * Gets a translation according to an integer value.
     *
     * @param string $key The translation key
     * @param \Countable|int|float|array $number The number determining plural form
     * @param array $replace Array of replacements
     * @param string|null $locale The locale to use, or null for default
     * @return string
     */
    public function choice($key, $number, array $replace = [], $locale = null)
    {
        if (!$this->laravelTranslator) {
            Log::error("[UTM] choice() called but LaravelTranslator is unavailable!");
            return $key;
        }
        Log::debug("[UTM] choice() called for: key='{$key}'. Delegating to LaravelTranslator.");
        return $this->laravelTranslator->choice($key, $number, $replace, $locale);
    }

    /**
     * Gets the default locale being used.
     *
     * @return string
     */
    public function getLocale(): string
    {
        return App::getLocale() ?? $this->config['default_locale'] ?? 'en';
    }

    /**
     * Sets the default locale.
     *
     * @param string $locale The locale to set
     * @return void
     */
    public function setLocale($locale): void
    {
        if (App::getLocale() !== $locale) {
            App::setLocale($locale);
            Log::info("[UTM] Application locale set to: {$locale}");
        }
    }

    //--------------------------------------------------------------------------
    // UTM-specific public methods
    //--------------------------------------------------------------------------

    /**
     * Registers translation files for a package.
     *
     * @param string $package The package name
     * @param string $baseLangPath The base language directory path
     * @return void
     */
    public function registerPackageTranslations(string $package, string $baseLangPath): void
    {
        if (!File::isDirectory($baseLangPath)) {
            Log::warning("[UTM Register] Invalid base path for package '{$package}': {$baseLangPath}");
            return;
        }
        Log::info("[UTM Register] Registering translations for package: '{$package}', path: '{$baseLangPath}'");

        $locales = $this->getAvailableLocales($baseLangPath);
        if (empty($locales)) {
            Log::warning("[UTM Register] No language directories found in: {$baseLangPath}");
            return;
        }

        foreach ($locales as $locale) {
            $translationFilePath = $this->buildTranslationFilePath($baseLangPath, $locale, $package);
            Log::debug("[UTM Register] Checking file: {$translationFilePath}");

            if (File::exists($translationFilePath)) {
                try {
                    $translations = include $translationFilePath;
                    if (is_array($translations)) {
                        $this->loadTranslationsIntoMemory($package, $locale, $translations);
                        Log::debug("[UTM Register] Translations loaded successfully for '{$package}' [{$locale}].");
                    } else {
                        Log::warning("[UTM Register] File did not return an array: {$translationFilePath}");
                    }
                } catch (\Throwable $e) {
                    Log::error("[UTM Register] Error including file: {$translationFilePath} - Error: " . $e->getMessage());
                }
            } else {
                Log::debug("[UTM Register] File not found: {$translationFilePath}");
            }
        }
    }

    //--------------------------------------------------------------------------
    // Protected helper methods
    //--------------------------------------------------------------------------

    /**
     * Fetches a translation, first from internal array, then via Laravel fallback.
     *
     * @param string|null $group The translation group (file name)
     * @param string $item The translation item (key)
     * @param string|null $package The package name
     * @param string $locale The locale to use
     * @param array $replace Array of replacements
     * @param bool $fallback Whether to use fallback
     * @return string|array
     */
    protected function fetchTranslation(?string $group, string $item, ?string $package, string $locale, array $replace, bool $fallback): string|array
    {
        Log::debug("[UTM Fetch] Starting fetch for item='{$item}', group='{$group}', package='{$package}', locale='{$locale}'");

        if ($package && isset($this->translations[$package][$locale])) {
            Log::debug("[UTM Fetch] Checking internal array for '{$package}' [{$locale}]...");
            Log::debug("[UTM Fetch] Array content: " . print_r($this->translations[$package][$locale], true));

            if (\Illuminate\Support\Arr::has($this->translations[$package][$locale], $item)) {
                $translation = \Illuminate\Support\Arr::get($this->translations[$package][$locale], $item);
                Log::debug("[UTM Fetch] Item '{$item}' FOUND internally (package: '{$package}').");
                return is_string($translation) ? $this->replacePlaceholders($translation, $replace) : $translation;
            } else {
                Log::debug("[UTM Fetch] Item '{$item}' NOT found internally in [{$package}][{$locale}]. Falling back...");
            }
        } else {
            Log::debug("[UTM Fetch] Internal array for '{$package}' [{$locale}] not found. Falling back...");
        }

        $originalKey = $package ? "{$package}::" : '';
        $originalKey .= $group ? "{$group}.{$item}" : $item;

        Log::debug("[UTM Fetch] Attempting fallback with Laravel key: '{$originalKey}'");
        if (!$this->laravelTranslator) { /* ... */ }
        $translation = $this->laravelTranslator->get($originalKey, $replace, $locale);

        $isMissing = ($translation === $originalKey);
        if ($isMissing) {
            Log::warning("[UTM Fetch] Fallback failed to find key: '{$originalKey}'.");
            return "[Missing: {$originalKey}]";
        } else {
            Log::debug("[UTM Fetch] Key '{$originalKey}' FOUND via fallback.");
            return $translation;
        }
    }

    /**
     * Loads an array of translations into internal memory.
     *
     * @param string $package The package name
     * @param string $locale The locale
     * @param array $translations The translations to load
     * @return void
     */
    protected function loadTranslationsIntoMemory(string $package, string $locale, array $translations): void
    {
        if (!isset($this->translations[$package])) {
            $this->translations[$package] = [];
        }
        if (!isset($this->translations[$package][$locale])) {
            $this->translations[$package][$locale] = [];
        }
        $this->translations[$package][$locale] = array_merge(
            $this->translations[$package][$locale],
            $translations
        );
    }

    /**
     * Finds available locale directories in the base path.
     *
     * @param string $baseLangPath The base language directory path
     * @return array List of locale codes
     */
    protected function getAvailableLocales(string $baseLangPath): array
    {
        $locales = [];
        if (File::isDirectory($baseLangPath)) {
            $directories = File::directories($baseLangPath);
            foreach ($directories as $directory) {
                $localeCode = basename($directory);
                if (strlen($localeCode) === 2 || strlen($localeCode) === 5) {
                    $locales[] = $localeCode;
                }
            }
        }
        Log::debug("[UTM] Languages found in '{$baseLangPath}': " . implode(', ', $locales));
        return $locales;
    }

    /**
     * Builds the full translation file path.
     *
     * @param string $baseLangPath The base language directory path
     * @param string $locale The locale
     * @param string $package The package name
     * @return string The full file path
     */
    protected function buildTranslationFilePath(string $baseLangPath, string $locale, string $package): string
    {
        return rtrim($baseLangPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $package . '.php';
    }

    /**
     * Builds the cache key.
     *
     * @param string|null $package The package name
     * @param string $actualKey The actual key
     * @param string $locale The locale
     * @return string The cache key
     */
    protected function buildCacheKey(?string $package, string $actualKey, string $locale): string
    {
        return ($this->config['cache_prefix'] ?? 'utm') . ".{$locale}." . ($package ?? 'APP') . ".{$actualKey}";
    }

    /**
     * Returns a unique marker for a missing translation key.
     *
     * @param string|null $package The package name
     * @param string|null $group The translation group
     * @param string $item The translation item
     * @return string The missing key marker
     */
    protected function getMissingKeyMarker(?string $package, ?string $group, string $item): string
    {
        return "@@__MISSING_TRANSLATION__{$package}::{$group}.{$item}__@@";
    }

    /**
     * Parses a key into package, group, and item components.
     *
     * @param string $key The translation key
     * @return array [package, group, item]
     */
    protected function parseKey(string $key): array
    {
        $package = null;
        $groupAndItem = $key;

        if (str_contains($key, '::')) {
            list($package, $groupAndItem) = explode('::', $key, 2);
        }

        $group = null;
        $item = $groupAndItem;

        $dotPosition = strpos($groupAndItem, '.');
        if ($dotPosition !== false) {
            $group = substr($groupAndItem, 0, $dotPosition);
            $item = substr($groupAndItem, $dotPosition + 1);
        }

        Log::debug("[UTM ParseKey] Key:'{$key}' -> Package:'{$package}', Group:'{$group}', Item:'{$item}'");
        return [$package, $group, $item];
    }

    /**
     * Replaces placeholders (:param) in the translation string.
     *
     * @param string $translation The translation string
     * @param array $replace Array of replacements
     * @return string The translated string with placeholders replaced
     */
    protected function replacePlaceholders(string $translation, array $replace): string
    {
        if (empty($replace)) {
            return $translation;
        }
        foreach ($replace as $key => $value) {
            $translation = str_replace(':' . ltrim($key, ':'), $value, $translation);
        }
        return $translation;
    }
}
