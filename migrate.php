<?php
// Wiki Plugin Migrations
// This file handles database migrations for plugin updates

require_once("init.php");
include "plugin_info.php";

if (in_array($user->data()->id, $master_account)) {
    $migrations = [];
    
    // Migration 1: Initial database setup (handled in install.php)
    $migrations['1.0.0'] = function($db) {
        // This migration is handled in install.php
        return true;
    };
    
    // Example future migration
    // $migrations['1.1.0'] = function($db) {
    //     // Add new column example
    //     $db->query("ALTER TABLE plg_wiki_entries ADD COLUMN view_count INT DEFAULT 0");
    //     return true;
    // };
    
    // Get current version from settings or default to 1.0.0
    $currentVersion = '1.0.0';
    $pluginSettings = $db->query("SELECT * FROM us_plugin_settings WHERE plugin = ?", [$plugin_name])->first();
    if ($pluginSettings && isset($pluginSettings->version)) {
        $currentVersion = $pluginSettings->version;
    }
    
    // Run migrations
    $newVersion = '1.0.0'; // Update this when you have new migrations
    
    foreach ($migrations as $version => $migration) {
        if (version_compare($currentVersion, $version, '<')) {
            try {
                if ($migration($db)) {
                    $newVersion = $version;
                    logger($user->data()->id, "USPlugins", "Wiki plugin migrated to version $version");
                }
            } catch (Exception $e) {
                logger($user->data()->id, "USPlugins", "Wiki plugin migration to $version failed: " . $e->getMessage());
                err("Migration to version $version failed: " . $e->getMessage());
                break;
            }
        }
    }
    
    // Update version in settings
    if ($pluginSettings) {
        $db->update('us_plugin_settings', $pluginSettings->id, ['version' => $newVersion]);
    } else {
        $db->insert('us_plugin_settings', [
            'plugin' => $plugin_name,
            'version' => $newVersion,
            'settings' => '{}'
        ]);
    }
    
    err("Wiki plugin migrations completed successfully");
}
?>