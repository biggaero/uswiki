USE biggaero_db;

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
    KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data
INSERT IGNORE INTO plg_wiki_entries (title, slug, content, parent_id, sort_order, created_by) VALUES
('Getting Started', 'getting-started', '# Welcome to the Knowledge Base\n\nThis is your main wiki entry. You can edit this content and create sub-topics.\n\n## Features\n\n- Hierarchical organization\n- Markdown support\n- Full-text search\n- User permissions\n- Integration with UserSpice', NULL, 1, 1),
('Installation', 'installation', '# Installation Guide\n\nStep-by-step installation instructions for the Wiki plugin.\n\n## Requirements\n\n- UserSpice 5.7+ \n- PHP 7.4+\n- MySQL 5.7+', NULL, 2, 1),
('Configuration', 'configuration', '# Configuration\n\nHow to configure your wiki system.\n\n## Basic Settings\n\nConfigure your wiki according to your needs.', 2, 1, 1);