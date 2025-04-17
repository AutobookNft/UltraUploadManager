<?php

namespace Ultra\TranslationManager\Facades;

use Illuminate\Support\Facades\Facade;
use Ultra\TranslationManager\TranslationManager;

/**
 * Facade for the Ultra Translation Manager (UTM).
 *
 * Exposes key functionalities of the UTM system with static method typing
 * to assist IDEs, static analysis, and avoid facade runtime pitfalls.
 *
 * @method static string get(string $key, array $replace = [], ?string $locale = null)
 * @method static string choice(string $key, \Countable|int|float|array $number, array $replace = [], ?string $locale = null)
 * @method static string getLocale()
 * @method static void setLocale(string $locale)
 * @method static void addNamespace(string $namespace, string $hint)
 * @method static void setLaravelTranslator(\Illuminate\Translation\Translator $translator)
 * @method static void registerPackageTranslations(string $package, string $baseLangPath)
 * @method static void setLogger(\Ultra\TranslationManager\Interfaces\LoggerInterface $logger)
 * @method static void setErrorReporter(\Ultra\TranslationManager\Interfaces\ErrorReporter $reporter)
 *
 * @see \Ultra\TranslationManager\TranslationManager
 */
class UltraTrans extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'ultra.translation';
    }

    /**
     * Retrieve a translation string by key.
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    public static function get(string $key, array $replace = [], ?string $locale = null): string
    {
        return static::getFacadeRoot()->get($key, $replace, $locale);
    }

    /**
     * Retrieve a translation string based on a number value (pluralization).
     *
     * @param string $key
     * @param \Countable|int|float|array $number
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    public static function choice(string $key, $number, array $replace = [], ?string $locale = null): string
    {
        return static::getFacadeRoot()->choice($key, $number, $replace, $locale);
    }

    /**
     * Get the current application locale.
     *
     * @return string
     */
    public static function getLocale(): string
    {
        return static::getFacadeRoot()->getLocale();
    }

    /**
     * Set the current application locale.
     *
     * @param string $locale
     * @return void
     */
    public static function setLocale(string $locale): void
    {
        static::getFacadeRoot()->setLocale($locale);
    }

    /**
     * Add a namespace to the Laravel Translator.
     *
     * @param string $namespace
     * @param string $hint
     * @return void
     */
    public static function addNamespace(string $namespace, string $hint): void
    {
        static::getFacadeRoot()->addNamespace($namespace, $hint);
    }

    /**
     * Inject Laravel's default Translator.
     *
     * @param \Illuminate\Translation\Translator $translator
     * @return void
     */
    public static function setLaravelTranslator(\Illuminate\Translation\Translator $translator): void
    {
        static::getFacadeRoot()->setLaravelTranslator($translator);
    }

    /**
     * Register translation files for a package.
     *
     * @param string $package
     * @param string $baseLangPath
     * @return void
     */
    public static function registerPackageTranslations(string $package, string $baseLangPath): void
    {
        static::getFacadeRoot()->registerPackageTranslations($package, $baseLangPath);
    }

    /**
     * Set a custom logger for the translation manager.
     *
     * @param \Ultra\TranslationManager\Interfaces\LoggerInterface $logger
     * @return void
     */
    public static function setLogger(\Ultra\TranslationManager\Interfaces\LoggerInterface $logger): void
    {
        static::getFacadeRoot()->setLogger($logger);
    }

    /**
     * Set a custom error reporter.
     *
     * @param \Ultra\TranslationManager\Interfaces\ErrorReporter $reporter
     * @return void
     */
    public static function setErrorReporter(\Ultra\TranslationManager\Interfaces\ErrorReporter $reporter): void
    {
        static::getFacadeRoot()->setErrorReporter($reporter);
    }
}
