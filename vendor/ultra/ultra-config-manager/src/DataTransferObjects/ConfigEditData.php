<?php

/**
 * 📜 Oracode DTO: ConfigEditData
 *
 * @package         Ultra\UltraConfigManager\DataTransferObjects
 * @version         1.0.0
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\DataTransferObjects;

use Illuminate\Support\Collection; // Per le collection di audit/versioni
use Ultra\UltraConfigManager\Models\UltraConfigModel; // Modello base
use Ultra\UltraConfigManager\Models\UltraConfigAudit; // Modello Audit
use Ultra\UltraConfigManager\Models\UltraConfigVersion; // Modello Version

/**
 * 🎯 Purpose: Data Transfer Object containing all data required to render the
 *    configuration edit form, including the configuration itself, its audit trail,
 *    and its version history.
 *
 * 🧱 Structure: Readonly class aggregating the main Model and collections of related history.
 *
 * 🛡️ Privacy: Contains the full `UltraConfigModel` (including potentially sensitive
 *    decrypted `value`). Also contains Audit/Version history which might include user IDs.
 *
 * @package Ultra\UltraConfigManager\DataTransferObjects
 */
final readonly class ConfigEditData
{
    /**
     * @param UltraConfigModel $config The configuration model instance (value is decrypted).
     * @param Collection<int, UltraConfigAudit> $audits Collection of audit records.
     * @param Collection<int, UltraConfigVersion> $versions Collection of version records.
     */
    public function __construct(
        public UltraConfigModel $config,
        public Collection $audits,
        public Collection $versions
    ) {}
}