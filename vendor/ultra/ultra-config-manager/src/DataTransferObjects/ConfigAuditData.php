<?php

/**
 * 📜 Oracode DTO: ConfigAuditData
 *
 * @package         Ultra\UltraConfigManager\DataTransferObjects
 * @version         1.0.0
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\DataTransferObjects;

use Illuminate\Support\Collection; // Per la collection di audit
use Ultra\UltraConfigManager\Models\UltraConfigModel; // Modello base
use Ultra\UltraConfigManager\Models\UltraConfigAudit; // Modello Audit

/**
 * 🎯 Purpose: Data Transfer Object containing the data required to render the
 *    configuration audit trail view, including the configuration itself (even if deleted)
 *    and its complete audit history.
 *
 * 🧱 Structure: Readonly class aggregating the main Model and the collection of audit records.
 *
 * 🛡️ Privacy: Contains the `UltraConfigModel` (key, category, etc.) and the audit history
 *    which includes encrypted old/new values and potentially user IDs.
 *
 * @package Ultra\UltraConfigManager\DataTransferObjects
 */
final readonly class ConfigAuditData
{
    /**
     * @param UltraConfigModel $config The configuration model instance (can be soft-deleted).
     * @param Collection<int, UltraConfigAudit> $audits Collection of audit records.
     */
    public function __construct(
        public UltraConfigModel $config,
        public Collection $audits
    ) {}
}