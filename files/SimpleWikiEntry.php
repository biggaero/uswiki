<?php
class SimpleWikiEntry {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    // Get all entries in hierarchical structure
    public function getAllEntries($parentId = null) {
        if ($parentId === null) {
            $entries = $this->db->query("SELECT * FROM plg_wiki_entries WHERE parent_id IS NULL AND is_published = 1 ORDER BY sort_order ASC, title ASC")->results();
        } else {
            $entries = $this->db->query("SELECT * FROM plg_wiki_entries WHERE parent_id = ? AND is_published = 1 ORDER BY sort_order ASC, title ASC", [$parentId])->results();
        }
        
        // Convert to array format and get children for each entry
        $entriesArray = [];
        foreach ($entries as $entry) {
            $entryArray = (array)$entry;
            $entryArray['children'] = $this->getAllEntries($entryArray['id']);
            $entriesArray[] = $entryArray;
        }
        
        return $entriesArray;
    }
    
    // Get single entry by slug
    public function getBySlug($slug) {
        $result = $this->db->query("SELECT * FROM plg_wiki_entries WHERE slug = ? AND is_published = 1", [$slug])->first();
        return $result ? (array)$result : false;
    }
    
    // Convert markdown to HTML (simple fallback)
    public function markdownToHtml($markdown) {
        if (empty($markdown)) return '';
        
        // Simple markdown parsing
        $html = htmlspecialchars($markdown);
        
        // Headers
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
        
        // Bold and italic
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
        
        // Lists
        $html = preg_replace('/^- (.+)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html);
        
        // Line breaks
        $html = nl2br($html);
        
        return $html;
    }
    
    // Search entries
    public function search($query) {
        $searchTerm = '%' . $query . '%';
        $entries = $this->db->query("SELECT * FROM plg_wiki_entries WHERE (title LIKE ? OR content LIKE ?) AND is_published = 1 ORDER BY title ASC", [$searchTerm, $searchTerm])->results();
        
        // Convert to array format
        $entriesArray = [];
        foreach ($entries as $entry) {
            $entriesArray[] = (array)$entry;
        }
        
        return $entriesArray;
    }
    
    // Get breadcrumb path
    public function getBreadcrumbs($entryId) {
        $breadcrumbs = [];
        $currentEntry = $this->getById($entryId);
        
        while ($currentEntry) {
            array_unshift($breadcrumbs, $currentEntry);
            $currentEntry = $currentEntry['parent_id'] ? $this->getById($currentEntry['parent_id']) : null;
        }
        
        return $breadcrumbs;
    }
    
    // Get entry by ID
    public function getById($id) {
        $result = $this->db->query("SELECT * FROM plg_wiki_entries WHERE id = ?", [$id])->first();
        return $result ? (array)$result : false;
    }
}
?>