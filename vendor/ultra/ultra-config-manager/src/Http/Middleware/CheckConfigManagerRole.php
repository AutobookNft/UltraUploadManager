<?php

/**
 * 📜 Oracode Middleware: CheckConfigManagerRole
 *
 * @package         Ultra\UltraConfigManager\Http\Middleware
 * @version         1.1.0 // Versione incrementata per refactoring Oracode
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException; // Per lanciare errore 403 standard
use Illuminate\Contracts\Auth\Factory as AuthFactory; // Per gestire autenticazione
use Illuminate\Contracts\Config\Repository as ConfigRepository; // Per leggere config
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface; // Per logging
use Symfony\Component\HttpFoundation\Response; // Per type hint ritorno
use Throwable; // Per catturare eccezioni

/**
 * 🎯 Purpose: Authorizes incoming HTTP requests destined for UltraConfigManager routes.
 *    It checks if the currently authenticated user possesses the required permission
 *    to perform the requested action (e.g., 'view-config', 'create-config'). Supports
 *    both Spatie/laravel-permission integration and a simple role-based fallback mechanism,
 *    configurable via `config/uconfig.php`.
 *
 * 🧱 Structure: Implements the `handle` method required by Laravel middleware.
 *    - Injects `AuthFactory`, `ConfigRepository`, and `LoggerInterface`.
 *    - Checks authentication status.
 *    - Reads configuration to determine authorization strategy (Spatie vs. fallback).
 *    - Calls Spatie's `hasPermissionTo` or performs a role check.
 *    - Throws `AuthorizationException` on failure, which Laravel typically catches and converts to a 403 response.
 *
 * 🧩 Context: Applied to routes defined in `routes/uconfig.php` either directly in the
 *    route file or via the controller/service provider. Executes after authentication
 *    middleware.
 *
 * 🛠️ Usage: `Route::middleware('uconfig.check_role:view-config')->get(...)`
 *
 * 💾 State: Stateless middleware. Relies on injected services and request context.
 *
 * 🗝️ Key Logic:
 *    - Authentication check.
 *    - Configuration-driven strategy selection (Spatie/Fallback).
 *    - Permission/Role verification.
 *    - Exception throwing on authorization failure.
 *
 * 🚦 Signals:
 *    - Allows the request to proceed `$next($request)` if authorized.
 *    - Throws `AuthorizationException` if unauthorized.
 *    - May trigger a redirect to login if the user is not authenticated (handled by Laravel's default Auth middleware usually run before this).
 *
 * 🛡️ Privacy (GDPR):
 *    - Accesses the authenticated user object to check roles/permissions. Does not typically handle other PII directly.
 *    - `@privacy-internal`: Reads user roles/permissions.
 *    - `@privacy-safe`: Primarily concerned with authorization, not processing sensitive data itself.
 *
 * 🤝 Dependencies:
 *    - `Illuminate\Contracts\Auth\Factory`: To get the authenticated user.
 *    - `Illuminate\Contracts\Config\Repository`: To read `uconfig.use_spatie_permissions`.
 *    - `Psr\Log\LoggerInterface`: For logging authorization attempts/failures.
 *    - `Illuminate\Auth\Access\AuthorizationException`: Standard exception for authorization failures.
 *    - (Conditional) `spatie/laravel-permission` package and `HasRoles` trait on the User model if Spatie is used.
 *    - (Conditional) A `role` attribute/method on the User model if fallback is used.
 *
 * 🧪 Testing:
 *    - Unit Test: Mock dependencies (`AuthFactory`, `ConfigRepository`, `LoggerInterface`, `Request`, `User`).
 *      - Test authenticated user passes with correct permission/role (Spatie/Fallback).
 *      - Test authenticated user fails with incorrect permission/role (throws `AuthorizationException`).
 *      - Test behavior when `use_spatie_permissions` config is true vs. false.
 *      - Test unauthenticated user case (though usually handled before this middleware).
 *    - Feature Test: Apply middleware to test routes. Simulate requests with authenticated users having different roles/permissions. Assert 200 OK for authorized, 403 Forbidden for unauthorized.
 *
 * 💡 Logic:
 *    - Dependency Injection removes reliance on Facades/helpers.
 *    - Uses standard `AuthorizationException` for better integration with Laravel's exception handling.
 *    - Fallback logic mapping permission string to role name is maintained but isolated. Consider making this mapping configurable if needed.
 *
 * @package Ultra\UltraConfigManager\Http\Middleware
 */
class CheckConfigManagerRole
{
    public function __construct(
        protected readonly AuthFactory $auth,
        protected readonly ConfigRepository $config,
        protected readonly LoggerInterface $logger
    ) {}

    /**
     * 🛡️ Handles the incoming request, performing authorization checks.
     *
     * @param Request $request The incoming request object.
     * @param Closure $next Closure representing the next middleware or controller action.
     * @param string $permission The permission string required (e.g., 'view-config').
     * @return Response Allows request to proceed or throws AuthorizationException.
     *
     * @throws AuthorizationException If the user is not authorized.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Get the default auth guard
        $guard = $this->auth->guard(); // Use injected AuthFactory

        // 1. Check Authentication (redundant if Auth middleware runs first, but safe)
        if (!$guard->check()) {
            $this->logger->warning('UCM Middleware: Unauthorized access attempt (unauthenticated).', ['permission' => $permission, 'ip' => $request->ip()]);
            // Typically, Authentication middleware handles the redirect.
            // Throwing here ensures protection if run standalone.
            throw new AuthorizationException('Unauthenticated.', 401);
        }

        $user = $guard->user();
        $userId = $user?->getAuthIdentifier(); // Get user ID safely

        $this->logger->debug('UCM Middleware: Checking authorization.', ['userId' => $userId, 'permission' => $permission]);

        // 2. Determine Authorization Strategy
        $useSpatie = $this->config->get('uconfig.use_spatie_permissions', false); // Use injected ConfigRepository

        $isAuthorized = false;

        try {
            if ($useSpatie && $user && method_exists($user, 'hasPermissionTo')) {
                // --- Spatie Strategy ---
                $this->logger->debug('UCM Middleware: Using Spatie strategy.', ['userId' => $userId, 'permission' => $permission]);
                $isAuthorized = $user->hasPermissionTo($permission);
            } else {
                // --- Fallback Role Strategy ---
                $this->logger->debug('UCM Middleware: Using fallback role strategy.', ['userId' => $userId, 'permission' => $permission]);
                $requiredRole = $this->mapPermissionToFallbackRole($permission);
                $userRole = $user?->role ?? null; // Assumes a 'role' property/attribute exists

                $this->logger->debug('UCM Middleware: Role check.', ['userId' => $userId, 'requiredRole' => $requiredRole, 'userRole' => $userRole]);

                // Basic check (adjust if roles have hierarchy, e.g., Manager > Editor > Viewer)
                // Current logic requires exact match or ConfigManager for write ops
                 if ($requiredRole === 'ConfigViewer') {
                     $isAuthorized = in_array($userRole, ['ConfigViewer', 'ConfigEditor', 'ConfigManager']);
                 } elseif ($requiredRole === 'ConfigEditor') {
                     $isAuthorized = in_array($userRole, ['ConfigEditor', 'ConfigManager']);
                 } elseif ($requiredRole === 'ConfigManager') {
                     $isAuthorized = ($userRole === 'ConfigManager');
                 } else {
                     $isAuthorized = false; // Unknown required role
                 }
            }
        } catch (Throwable $e) {
             // Catch potential errors during permission/role check (e.g., DB error if roles are complex)
             $this->logger->error('UCM Middleware: Error during authorization check.', [
                 'userId' => $userId, 'permission' => $permission, 'exception' => $e::class, 'message' => $e->getMessage()
             ]);
             // Deny access on error for security
             throw new AuthorizationException('Authorization check failed due to an internal error.', 500, $e);
        }


        // 3. Enforce Authorization
        if (!$isAuthorized) {
            $this->logger->warning('UCM Middleware: Authorization denied.', ['userId' => $userId, 'permission' => $permission]);
            // Throw standard exception, Laravel handles the 403 response
            throw new AuthorizationException('You do not have permission to perform this action.');
        }

        $this->logger->info('UCM Middleware: Authorization granted.', ['userId' => $userId, 'permission' => $permission]);

        // 4. Proceed to next middleware/controller
        return $next($request);
    }

    /**
     * 🗺️ Maps a permission string to the required role name for the fallback strategy.
     * @internal
     * @param string $permission Permission string (e.g., 'view-config').
     * @return string Required role name (e.g., 'ConfigViewer', 'ConfigManager').
     */
    protected function mapPermissionToFallbackRole(string $permission): string
    {
        // This mapping could potentially be made configurable
        return match ($permission) {
            'view-config' => 'ConfigViewer',
            'create-config' => 'ConfigEditor', // Editor can also create? Or only Manager? Let's assume Editor can.
            'update-config' => 'ConfigEditor',
            'delete-config' => 'ConfigManager', // Only Manager can delete
            default => 'ConfigManager', // Default to highest privilege for safety on unknown permissions
        };
    }
}