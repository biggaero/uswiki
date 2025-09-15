<?php
// Plugin functions for UserSpice Wiki

// Check if function exists before defining to avoid conflicts
if (!function_exists('wikiGetUserName')) {
    /**
     * Get user name for wiki entries
     * @param int $userId
     * @return string
     */
    function wikiGetUserName($userId) {
        global $db;
        if (!$userId) return 'Unknown';
        
        $user = $db->query("SELECT fname, lname, username FROM users WHERE id = ?", [$userId])->first();
        if ($user) {
            if ($user->fname || $user->lname) {
                return trim($user->fname . ' ' . $user->lname);
            }
            return $user->username;
        }
        return 'Unknown';
    }
}

if (!function_exists('wikiFormatDate')) {
    /**
     * Format date for wiki display
     * @param string $date
     * @return string
     */
    function wikiFormatDate($date) {
        return date('F j, Y, g:i a', strtotime($date));
    }
}

if (!function_exists('wikiTruncateText')) {
    /**
     * Truncate text for search results
     * @param string $text
     * @param int $length
     * @return string
     */
    function wikiTruncateText($text, $length = 150) {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        // Strip markdown and HTML
        $text = strip_tags($text);
        $text = preg_replace('/[#*_`\[\](){}]/', '', $text);
        
        return substr($text, 0, $length) . '...';
    }
}

if (!function_exists('wikiHighlightSearchTerm')) {
    /**
     * Highlight search terms in text
     * @param string $text
     * @param string $searchTerm
     * @return string
     */
    function wikiHighlightSearchTerm($text, $searchTerm) {
        if (empty($searchTerm)) return $text;
        
        return preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<mark>$1</mark>', $text);
    }
}
?>