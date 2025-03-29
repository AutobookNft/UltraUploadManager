# Ultra Translation Manager (UTM)

Ultra Translation Manager (UTM) è un modulo per la gestione avanzata delle traduzioni in applicazioni Laravel. Offre un sistema di caching per migliorare le prestazioni e supporta la sostituzione dinamica dei parametri nelle traduzioni.

## Caratteristiche principali
- **Registrazione dinamica**: Registra le traduzioni da file di lingua in cache per un accesso rapido.
- **Recupero traduzioni**: Recupera traduzioni con supporto per namespace e sostituzione di parametri.
- **Logging integrato**: Usa `UltraLogManager` per tracciare errori e informazioni durante la gestione delle traduzioni.
- **Integrazione con Laravel**: Si integra facilmente con il sistema di localizzazione di Laravel.

## Requisiti
- PHP >= 8.0
- Laravel >= 8.0
- `UltraLogManager` (per il logging)

## Installazione
1. **Installa il pacchetto**:
   - Copia la classe `UltraTrans.php` in `app/Ultra/TranslationManager/UltraTrans.php` (o usa Composer se lo pubblichi come pacchetto).
   - Assicurati che `UltraLogManager` sia configurato nel tuo progetto.

2. **Configura i file di lingua**:
   - Crea i file di lingua in `resources/lang/<lang>/` (es. `resources/lang/en/core.php`).
   - Esempio di file di lingua:
     ```php
     return [
         'welcome' => 'Welcome to Ultra Translation Manager!',
         'upload' => [
             'success' => 'File uploaded successfully!',
             'error' => 'An error occurred while uploading the file.',
         ],
         'test' => [
             'message' => 'This is a test message with a parameter: :param',
         ],
     ];
     ```

3. **Registra le traduzioni**:
   - Chiama `UltraTrans::register()` per registrare le traduzioni in cache. Ad esempio, in un service provider:
     ```php
     use Ultra\TranslationManager\UltraTrans;

     public function boot()
     {
         UltraTrans::register('en', 'core');
     }
     ```

## Utilizzo
### Recupero di una traduzione
Usa `UltraTrans::get()` per recuperare una traduzione:

```php
use Ultra\TranslationManager\UltraTrans;

// Recupera una traduzione semplice
echo UltraTrans::get('core.welcome'); // Output: Welcome to Ultra Translation Manager!

// Recupera una traduzione con namespace
echo UltraTrans::get('core.upload.success'); // Output: File uploaded successfully!

// Recupera una traduzione con parametri
echo UltraTrans::get('core.test.message', ['param' => 'Fabio']); // Output: This is a test message with a parameter: Fabio
