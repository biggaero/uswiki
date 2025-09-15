<?php
require_once("init.php");

// For security purposes, it is MANDATORY that this page be wrapped in the following
// if statement. This prevents remote execution of this code.
if (in_array($user->data()->id, $master_account)) {
    include "plugin_info.php";

    // All actions should be performed here.
    $pluginCheck = $db->query("SELECT * FROM us_plugins WHERE plugin = ?", array($plugin_name))->count();
    if ($pluginCheck > 0) {
        err($plugin_name . ' has already been installed!');
    } else {
        // Create the wiki entries table
        $sql = "
        CREATE TABLE IF NOT EXISTS `plg_wiki_entries` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            `slug` varchar(255) NOT NULL,
            `content` longtext,
            `parent_id` int(11) DEFAULT NULL,
            `sort_order` int(11) DEFAULT 0,
            `is_published` tinyint(1) DEFAULT 1,
            `created_by` int(11) DEFAULT NULL,
            `updated_by` int(11) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `slug` (`slug`),
            KEY `idx_parent` (`parent_id`),
            KEY `idx_slug` (`slug`),
            KEY `idx_published` (`is_published`),
            KEY `idx_created_by` (`created_by`),
            CONSTRAINT `fk_wiki_parent` FOREIGN KEY (`parent_id`) REFERENCES `plg_wiki_entries` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_wiki_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_wiki_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        try {
            $db->query($sql);
            
            // Insert sample data
            $sampleData = [
                [
                    'title' => 'Getting Started',
                    'slug' => 'getting-started',
                    'content' => "# Welcome to the Knowledge Base\n\nThis is your main wiki entry. You can edit this content and create sub-topics.\n\n## Features\n\n- Hierarchical organization\n- Markdown support\n- Full-text search\n- User permissions\n- Integration with UserSpice",
                    'parent_id' => null,
                    'sort_order' => 1,
                    'created_by' => $user->data()->id
                ],
                [
                    'title' => 'Installation',
                    'slug' => 'installation',
                    'content' => "# Installation Guide\n\nStep-by-step installation instructions for the Wiki plugin.\n\n## Requirements\n\n- UserSpice 5.7+ \n- PHP 7.4+\n- MySQL 5.7+",
                    'parent_id' => null,
                    'sort_order' => 2,
                    'created_by' => $user->data()->id
                ]
            ];
            
            foreach ($sampleData as $data) {
                $db->insert('plg_wiki_entries', $data);
            }
            
            // Copy user-facing files to users directory
            $filesToCopy = [
                'files/wiki.php' => 'users/wiki.php',
                'files/wiki_edit.php' => 'users/wiki_edit.php', 
                'files/wiki_delete.php' => 'users/wiki_delete.php',
                'files/wiki_preview.php' => 'users/wiki_preview.php',
                'files/wiki_upload.php' => 'users/wiki_upload.php',
                'files/SimpleWikiEntry.php' => 'users/SimpleWikiEntry.php'
            ];
            
            foreach ($filesToCopy as $source => $destination) {
                $sourcePath = $abs_us_root . $us_url_root . 'usersc/plugins/' . $plugin_name . '/' . $source;
                $destPath = $abs_us_root . $us_url_root . $destination;
                
                if (file_exists($sourcePath)) {
                    if (!copy($sourcePath, $destPath)) {
                        err('Failed to copy ' . $source . ' to ' . $destination);
                        exit;
                    }
                } else {
                    err('Source file not found: ' . $sourcePath);
                    exit;
                }
            }
            
        } catch (Exception $e) {
            err('Error creating wiki tables: ' . $e->getMessage());
            exit;
        }

        // Register the plugin
        $fields = array(
            'plugin' => $plugin_name,
            'status' => 'installed',
        );
        $db->insert('us_plugins', $fields);
        
        if (!$db->error()) {
            err($plugin_name . ' installed');
            logger($user->data()->id, "USPlugins", $plugin_name . " installed");
        } else {
            err($plugin_name . ' was not installed');
            logger($user->data()->id, "USPlugins", "Failed to install plugin, Error: " . $db->errorString());
        }
    }

    // Plugin hooks configuration
    $hooks = [];

    // Example hook to add wiki link to navigation if needed
    // $hooks['navigation.php']['body'] = 'hooks/navigation_wiki_link.php';

    registerHooks($hooks, $plugin_name);

} // do not perform actions outside of this statement
?>