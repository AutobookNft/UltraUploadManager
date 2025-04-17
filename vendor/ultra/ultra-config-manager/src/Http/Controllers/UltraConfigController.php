<?php

/**
 * 📜 Oracode Controller: UltraConfigController
 *
 * @package         Ultra\UltraConfigManager\Http\Controllers
 * @version         1.2.0 // Versione incrementata per uso DTO e nuovi metodi Manager
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator; // Per type hint
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Psr\Log\LoggerInterface;
use Throwable;
use Ultra\ErrorManager\Facades\UltraError;
use Ultra\UltraConfigManager\Dao\ConfigDaoInterface;
use Ultra\UltraConfigManager\Enums\CategoryEnum;
use Ultra\UltraConfigManager\UltraConfigManager;
use Ultra\UltraConfigManager\Exceptions\ConfigNotFoundException;
use Ultra\UltraConfigManager\Exceptions\DuplicateKeyException;
use Ultra\UltraConfigManager\Exceptions\PersistenceException;
// Import DTOs
use Ultra\UltraConfigManager\DataTransferObjects\ConfigAuditData;
use Ultra\UltraConfigManager\DataTransferObjects\ConfigDisplayData;
use Ultra\UltraConfigManager\DataTransferObjects\ConfigEditData;


/**
 * 🎯 Purpose: Handles HTTP requests for the UltraConfigManager's web interface.
 *    Provides CRUD operations and audit log viewing. Acts as the bridge between HTTP
 *    requests/responses and the core `UltraConfigManager` service.
 *
 * 🧱 Structure: Standard Laravel controller. Injects `UltraConfigManager` and `LoggerInterface`.
 *    Uses RESTful methods. Interacts SOLELY with `UltraConfigManager`. Uses `UltraErrorManager`.
 *
 * 🧩 Context: Activated by routes within 'web' middleware. Requires authenticated users.
 *
 * 🛠️ Usage: Accessed via web browser through defined routes.
 *
 * 💾 State: Stateless.
 *
 * 🗝️ Key Methods: `index`, `create`, `store`, `edit`, `update`, `destroy`, `audit`.
 *
 * 🚦 Signals: Returns Views or RedirectResponses. Handles exceptions via `UltraError`.
 *
 * 🛡️ Privacy (GDPR): Collects config data, reads `Auth::id()`, displays config data. Middleware access control is critical.
 *
 * 🤝 Dependencies: `UltraConfigManager`, `LoggerInterface`, Request, Auth, `UltraErrorManager`, `CategoryEnum`, Views, Validation, Routing, Custom Exceptions, **DTOs**.
 *
 * 🧪 Testing: Feature tests simulating HTTP requests, mocking `UltraConfigManager`, asserting responses and redirects.
 *
 * 💡 Logic: Receive request, validate, call `UltraConfigManager`, handle exceptions via `UltraError`, return response. Retrieves `Auth::id()` for write ops.
 *
 * @package Ultra\UltraConfigManager\Http\Controllers
 */
class UltraConfigController extends Controller
{
    // Dependencies sono readonly per coerenza con PHP 8.1+ best practices
    public function __construct(
        protected readonly UltraConfigManager $uconfig,
        protected readonly LoggerInterface $logger
    ) {
        $this->logger->debug('UltraConfigController initialized.');
    }

    /**
     * 🌍 Displays the list of all configuration entries.
     * Retrieves paginated display data via UltraConfigManager.
     *
     * @param Request $request Allows for future filtering/pagination query params.
     * @return View|RedirectResponse View on success, RedirectResponse on error.
     * @readOperation Retrieves configurations for display.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $this->logger->info('UCM Controller: Accessing configuration index.');
        try {
            // Usa il nuovo metodo del Manager che restituisce DTOs paginati
            // Possiamo aggiungere logica per passare filtri/paginazione dalla request se necessario
            $perPage = $request->integer('perPage', 15); // Esempio: prende perPage da query string, default 15
            $configs = $this->uconfig->getAllEntriesForDisplay(filters: [], perPage: $perPage); // Chiamata corretta

            return view('uconfig::index', compact('configs'));

        } catch (PersistenceException $e) {
            $this->logger->error('UCM Controller: Failed to retrieve configurations for index.', ['error' => $e->getMessage()]);
            return UltraError::handleFromException($e)
                             ->redirectBackWithError(__('uconfig::uconfig.error.generic_load_error') ?? 'Error loading configurations.');
        } catch (Throwable $e) {
             $this->logger->error('UCM Controller: Unexpected error on index.', ['error' => $e->getMessage()]);
            return UltraError::handleFromException($e)
                             ->redirectBackWithError(__('uconfig::uconfig.error.unexpected_error') ?? 'An unexpected error occurred.');
        }
    }

    /**
     * 🆕 Shows the form for creating a new configuration entry.
     *
     * @return View View for creating a configuration.
     */
    public function create(): View
    {
        $this->logger->info('UCM Controller: Accessing create configuration form.');
        $categories = CategoryEnum::translatedOptions();
        $categoryValues = CategoryEnum::validValues();
        return view('uconfig::create', compact('categories', 'categoryValues'));
    }

    /**
     * 💾 Stores a new configuration entry based on form submission.
     * Validates input and delegates persistence to UltraConfigManager.
     *
     * @param Request $request The incoming HTTP request.
     * @return RedirectResponse Redirects on success or failure.
     * @writeOperation Creates a new configuration entry.
     * @validation Validates request data.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->logger->info('UCM Controller: Attempting to store new configuration.');

        $validatedData = $request->validate([
            'key' => ['required', 'string', 'regex:/^[a-zA-Z0-9_.-]+$/', 'unique:uconfig,key'],
            'value' => ['required'],
            'category' => ['nullable', 'string', \Illuminate\Validation\Rule::in(CategoryEnum::validValues())],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $userId = Auth::id();
        $this->logger->debug('UCM Controller: Store validation passed.', ['key' => $validatedData['key'], 'userId' => $userId]);

        try {
            $this->uconfig->set(
                key: $validatedData['key'],
                value: $validatedData['value'],
                category: $validatedData['category'],
                userId: $userId, // Passa null se Auth::id() è null (ospite?) o usa GlobalConstants::NO_USER
                version: true,
                audit: true
            );

            $this->logger->info('UCM Controller: New configuration stored successfully.', ['key' => $validatedData['key']]);
            return redirect()->route('uconfig.index')
                             ->with('success', __('uconfig::uconfig.success.created') ?? 'Configuration created successfully.');

        } catch (DuplicateKeyException $e) {
             $this->logger->warning('UCM Controller: Failed to store configuration due to duplicate key.', ['key' => $validatedData['key'], 'error' => $e->getMessage()]);
             return UltraError::handleFromException($e, ['key' => $validatedData['key']])
                              ->withInput()
                              ->redirectBackWithError(__('uconfig::uconfig.error.duplicate_key', ['key' => $validatedData['key']]) ?? $e->getMessage());
        } catch (PersistenceException $e) {
             $this->logger->error('UCM Controller: Failed to store configuration due to persistence error.', ['key' => $validatedData['key'], 'error' => $e->getMessage()]);
             return UltraError::handleFromException($e, ['key' => $validatedData['key']])
                              ->withInput()
                              ->redirectBackWithError(__('uconfig::uconfig.error.generic_save_error') ?? 'Error saving configuration.');
        } catch (Throwable $e) {
             $this->logger->error('UCM Controller: Unexpected error storing configuration.', ['key' => $validatedData['key'], 'error' => $e->getMessage()]);
             return UltraError::handleFromException($e, ['key' => $validatedData['key']])
                              ->withInput()
                              ->redirectBackWithError(__('uconfig::uconfig.error.unexpected_error') ?? 'An unexpected error occurred.');
        }
    }

    /**
     * ✍️ Shows the form for editing an existing configuration entry.
     * Retrieves data aggregated in a DTO via UltraConfigManager.
     *
     * @param int $id The ID of the configuration to edit.
     * @return View|RedirectResponse View on success, RedirectResponse on error.
     * @readOperation Retrieves configuration, audit, and version data.
     */
    public function edit(int $id): View|RedirectResponse
    {
        $this->logger->info('UCM Controller: Accessing edit configuration form.', ['id' => $id]);

        try {
             // Usa il nuovo metodo del Manager che restituisce il DTO ConfigEditData
             $editData = $this->uconfig->findEntryForEdit($id); // Chiamata corretta

             // Prepara dati aggiuntivi per il form
             $categories = CategoryEnum::translatedOptions();
             $categoryValues = CategoryEnum::validValues();

             // Passa le proprietà del DTO alla vista (o l'intero DTO)
             return view('uconfig::edit', [
                 'config' => $editData->config,
                 'audits' => $editData->audits,
                 'versions' => $editData->versions,
                 'categories' => $categories,
                 'categoryValues' => $categoryValues,
             ]);

        } catch (ConfigNotFoundException $e) {
             $this->logger->warning('UCM Controller: Configuration not found for editing.', ['id' => $id]);
             return UltraError::handleFromException($e, ['id' => $id])
                              ->redirectBackWithError(__('uconfig::uconfig.error.not_found') ?? 'Configuration not found.');
        } catch (PersistenceException $e) {
            $this->logger->error('UCM Controller: Failed to retrieve data for edit form.', ['id' => $id, 'error' => $e->getMessage()]);
            return UltraError::handleFromException($e, ['id' => $id])
                             ->redirectBackWithError(__('uconfig::uconfig.error.generic_load_error') ?? 'Error loading configuration data.');
        } catch (Throwable $e) {
             $this->logger->error('UCM Controller: Unexpected error on edit.', ['id' => $id, 'error' => $e->getMessage()]);
             return UltraError::handleFromException($e, ['id' => $id])
                             ->redirectBackWithError(__('uconfig::uconfig.error.unexpected_error') ?? 'An unexpected error occurred.');
        }
    }

    /**
     * 🔄 Updates an existing configuration entry based on form submission.
     * Validates input and delegates persistence to UltraConfigManager.
     *
     * @param Request $request The incoming HTTP request.
     * @param int $id The ID of the configuration to update.
     * @return RedirectResponse Redirects on success or failure.
     * @writeOperation Updates a configuration entry.
     * @validation Validates request data.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $this->logger->info('UCM Controller: Attempting to update configuration.', ['id' => $id]);

        // --- Recupera la chiave esistente (necessaria per Manager->set) ---
        // ORCD: Nota Padmin: Recupero temporaneo via DAO per ottenere la chiave.
        // Idealmente, `findEntryForEdit` potrebbe essere chiamato prima della validazione
        // per avere il DTO, oppure `set` potrebbe accettare ID.
        $key = null;
        try {
            $dao = app(ConfigDaoInterface::class); // Risoluzione temporanea DAO
            $existingConfig = $dao->getConfigById($id);
            if (!$existingConfig) {
                 throw new ConfigNotFoundException("Config ID {$id} not found for update.");
            }
            $key = $existingConfig->key;
        } catch (ConfigNotFoundException $e) {
             $this->logger->warning('UCM Controller: Configuration not found for update.', ['id' => $id]);
             return UltraError::handleFromException($e, ['id' => $id])
                              ->redirectBackWithError(__('uconfig::uconfig.error.not_found') ?? 'Configuration not found.');
        } catch (PersistenceException $e) {
             $this->logger->error('UCM Controller: Failed to retrieve key for update.', ['id' => $id, 'error' => $e->getMessage()]);
            return UltraError::handleFromException($e, ['id' => $id])
                             ->redirectBackWithError(__('uconfig::uconfig.error.generic_load_error') ?? 'Error loading configuration data.');
        }
         // --- Fine recupero chiave ---

        // --- Validation ---
        $validatedData = $request->validate([
            'value' => ['required'],
            'category' => ['nullable', 'string', \Illuminate\Validation\Rule::in(CategoryEnum::validValues())],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $userId = Auth::id();
        $this->logger->debug('UCM Controller: Update validation passed.', ['key' => $key, 'userId' => $userId]);

        // --- Delegate to Manager ---
        try {
            $this->uconfig->set(
                key: $key,
                value: $validatedData['value'],
                category: $validatedData['category'],
                userId: $userId,
                version: true,
                audit: true
            );

            $this->logger->info('UCM Controller: Configuration updated successfully.', ['key' => $key]);
            return redirect()->route('uconfig.index')
                             ->with('success', __('uconfig::uconfig.success.updated') ?? 'Configuration updated successfully.');

        // Catch specifiche eccezioni che 'set' potrebbe lanciare
        } catch (ConfigNotFoundException $e) { // Se set internamente non trovasse la chiave (improbabile dato il check iniziale)
             $this->logger->warning('UCM Controller: Configuration key mismatch or not found during update.', ['key' => $key, 'error' => $e->getMessage()]);
             return UltraError::handleFromException($e, ['key' => $key])
                              ->redirectBackWithError(__('uconfig::uconfig.error.not_found') ?? 'Configuration could not be updated.');
        } catch (PersistenceException $e) {
             $this->logger->error('UCM Controller: Failed to update configuration due to persistence error.', ['key' => $key, 'error' => $e->getMessage()]);
             return UltraError::handleFromException($e, ['key' => $key])
                              ->withInput()
                              ->redirectBackWithError(__('uconfig::uconfig.error.generic_save_error') ?? 'Error updating configuration.');
        } catch (Throwable $e) {
             $this->logger->error('UCM Controller: Unexpected error updating configuration.', ['key' => $key, 'error' => $e->getMessage()]);
             return UltraError::handleFromException($e, ['key' => $key])
                              ->withInput()
                              ->redirectBackWithError(__('uconfig::uconfig.error.unexpected_error') ?? 'An unexpected error occurred.');
        }
    }

    /**
     * 🔥 Deletes a configuration entry.
     * Delegates the deletion process to UltraConfigManager.
     *
     * @param int $id The ID of the configuration to delete.
     * @return RedirectResponse Redirects on success or failure.
     * @writeOperation Deletes a configuration entry.
     */
    public function destroy(int $id): RedirectResponse
    {
        $this->logger->info('UCM Controller: Attempting to delete configuration.', ['id' => $id]);

        // --- Recupera la chiave (necessaria per Manager->delete) ---
        // ORCD: Nota Padmin: Recupero temporaneo via DAO.
        $key = null;
        try {
             $dao = app(ConfigDaoInterface::class);
             $configToDelete = $dao->getConfigById($id);
             if (!$configToDelete) {
                 throw new ConfigNotFoundException("Config ID {$id} not found for deletion.");
             }
             $key = $configToDelete->key;
        } catch (ConfigNotFoundException $e) {
             $this->logger->warning('UCM Controller: Configuration not found for deletion.', ['id' => $id]);
             // Consider deletion idempotent? Redirect success? For now, error.
             return UltraError::handleFromException($e, ['id' => $id])
                              ->redirectBackWithError(__('uconfig::uconfig.error.not_found') ?? 'Configuration not found.');
        } catch (PersistenceException $e) {
             $this->logger->error('UCM Controller: Failed to retrieve key for deletion.', ['id' => $id, 'error' => $e->getMessage()]);
             return UltraError::handleFromException($e, ['id' => $id])
                              ->redirectBackWithError(__('uconfig::uconfig.error.generic_load_error') ?? 'Error loading configuration data.');
        }
        // --- Fine recupero chiave ---

        $userId = Auth::id();
        $this->logger->debug('UCM Controller: Deletion requested.', ['key' => $key, 'userId' => $userId]);

        // --- Delegate to Manager ---
        try {
            $this->uconfig->delete(
                key: $key,
                userId: $userId
            );

            $this->logger->info('UCM Controller: Configuration deleted successfully.', ['key' => $key]);
            return redirect()->route('uconfig.index')
                             ->with('success', __('uconfig::uconfig.success.deleted') ?? 'Configuration deleted successfully.');

        } catch (ConfigNotFoundException $e) { // Se delete non trova la chiave
             $this->logger->warning('UCM Controller: Configuration key not found during delete operation.', ['key' => $key, 'error' => $e->getMessage()]);
             // Consider deletion idempotent? For now, error.
             return UltraError::handleFromException($e, ['key' => $key])
                              ->redirectBackWithError(__('uconfig::uconfig.error.not_found') ?? 'Configuration not found during deletion.');
        } catch (PersistenceException $e) {
             $this->logger->error('UCM Controller: Failed to delete configuration due to persistence error.', ['key' => $key, 'error' => $e->getMessage()]);
             return UltraError::handleFromException($e, ['key' => $key])
                              ->redirectBackWithError(__('uconfig::uconfig.error.generic_delete_error') ?? 'Error deleting configuration.');
        } catch (Throwable $e) {
             $this->logger->error('UCM Controller: Unexpected error deleting configuration.', ['key' => $key, 'error' => $e->getMessage()]);
             return UltraError::handleFromException($e, ['key' => $key])
                              ->redirectBackWithError(__('uconfig::uconfig.error.unexpected_error') ?? 'An unexpected error occurred.');
        }
    }

    /**
     * 📜 Displays the audit log for a specific configuration.
     * Retrieves data aggregated in a DTO via UltraConfigManager.
     *
     * @param int $id The ID of the configuration whose audit log is to be viewed.
     * @return View|RedirectResponse View on success, RedirectResponse on error.
     * @readOperation Retrieves configuration and audit data.
     */
    public function audit(int $id): View|RedirectResponse
    {
        $this->logger->info('UCM Controller: Accessing audit log.', ['id' => $id]);

        try {
            // Usa il nuovo metodo del Manager che restituisce il DTO ConfigAuditData
            $auditData = $this->uconfig->findEntryForAudit($id); // Chiamata corretta

            // Passa le proprietà del DTO alla vista
            return view('uconfig::audit', [
                'config' => $auditData->config,
                'audits' => $auditData->audits,
            ]);

        } catch (ConfigNotFoundException $e) {
             $this->logger->warning('UCM Controller: Configuration not found for audit.', ['id' => $id]);
             return UltraError::handleFromException($e, ['id' => $id])
                              ->redirectBackWithError(__('uconfig::uconfig.error.not_found') ?? 'Configuration not found.');
        } catch (PersistenceException $e) {
            $this->logger->error('UCM Controller: Failed to retrieve data for audit log.', ['id' => $id, 'error' => $e->getMessage()]);
            return UltraError::handleFromException($e, ['id' => $id])
                             ->redirectBackWithError(__('uconfig::uconfig.error.generic_load_error') ?? 'Error loading audit data.');
        } catch (Throwable $e) {
            $this->logger->error('UCM Controller: Unexpected error on audit.', ['id' => $id, 'error' => $e->getMessage()]);
            return UltraError::handleFromException($e, ['id' => $id])
                             ->redirectBackWithError(__('uconfig::uconfig.error.unexpected_error') ?? 'An unexpected error occurred.');
        }
    }
}