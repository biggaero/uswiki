<?php
require_once("init.php");

// For security purposes, it is MANDATORY that this page be wrapped in the following
// if statement. This prevents remote execution of this code.
if (in_array($user->data()->id, $master_account)) {
    include "plugin_info.php";

    // Remove the plugin from the database
    $db->query("DELETE FROM us_plugins WHERE plugin = ?", array($plugin_name));
    
    // Remove user-facing files from users directory
    $filesToRemove = [
        'users/wiki.php',
        'users/wiki_edit.php', 
        'users/wiki_delete.php',
        'users/wiki_preview.php',
        'users/wiki_upload.php',
        'users/SimpleWikiEntry.php'
    ];
    
    foreach ($filesToRemove as $file) {
        $filePath = $abs_us_root . $us_url_root . $file;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    // Drop the wiki tables
    try {
        // Disable foreign key checks temporarily
        $db->query("SET FOREIGN_KEY_CHECKS = 0");
        
        // Drop the table
        $db->query("DROP TABLE IF EXISTS plg_wiki_entries");
        
        // Re-enable foreign key checks
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
        
        err($plugin_name . ' uninstalled successfully');
        logger($user->data()->id, "USPlugins", $plugin_name . " uninstalled");
    } catch (Exception $e) {
        err('Error uninstalling ' . $plugin_name . ': ' . $e->getMessage());
        logger($user->data()->id, "USPlugins", "Failed to uninstall " . $plugin_name . ", Error: " . $e->getMessage());
    }
}
?>