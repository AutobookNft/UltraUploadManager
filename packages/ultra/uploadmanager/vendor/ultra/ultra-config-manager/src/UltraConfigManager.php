<?php

namespace Ultra\UltraConfigManager;

use Illuminate\Support\Facades\Log;

class UltraConfigManager
{
    // Method to retrieve and validate configuration values
    public static function getConfig($key, $default = null)
    {
        $value = config("ultra_fc_config_manager.{$key}", $default);

        // Validate based on the key
        switch ($key) {
            case 'devteam_email':
                return self::validateDevTeamEmail($value);
            case 'email_notifications':
                return self::validateEmailNotification($value);
            case 'log_channel':
                return self::validateRouteChannel($value);
            default:
                return $value;
        }
    }

    // Validation methods
    protected static function validateDevTeamEmail($email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        } else {
            Log::warning("Invalid dev team email provided in configuration: {$email}. Defaulting to null.");
            return null;
        }
    }

    protected static function validateEmailNotification($value)
    {
        if (is_bool($value)) {
            return $value;
        } else {
            Log::warning("Invalid value for email_notifications configuration: {$value}. Defaulting to false.");
            return false;
        }
    }

    protected static function validateRouteChannel($channel)
    {
        $validChannels = ['stack', 'single', 'daily', 'slack', 'syslog', 'errorlog', 'monolog', 'custom'];
        if (in_array($channel, $validChannels)) {
            return $channel;
        } else {
            Log::warning("Invalid log channel provided: {$channel}. Defaulting to 'upload'.");
            return 'upload';
        }
    }
}
