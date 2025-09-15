<?php
require_once 'init.php';
if (!securePage($_SERVER['PHP_SELF'])) {
    die();
}

// Check if user is logged in
if (!$user->isLoggedIn()) {
    Redirect::to($us_url_root . 'users/login.php');
}

// Include the WikiEntry class
require_once 'SimpleWikiEntry.php';

require_once $abs_us_root . $us_url_root . 'users/includes/template/prep.php';
$hooks = getMyHooks();
includeHook($hooks, 'pre');

try {
    $wikiEntry = new SimpleWikiEntry();
    
    // Get the slug from URL parameter
    $slug = Input::get('entry') ?? 'getting-started';
    $currentEntry = $wikiEntry->getBySlug($slug);
    $allEntries = $wikiEntry->getAllEntries();
    
    // Handle search
    $searchResults = [];
    if (Input::exists('get') && Input::get('search') && !empty(Input::get('search'))) {
        $searchResults = $wikiEntry->search(Input::get('search'));
    }
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    $currentEntry = false;
    $allEntries = [];
    $searchResults = [];
}

function renderTreeView($entries, $currentSlug = '', $us_url_root = '') {
    $html = '<ul class="list-unstyled">';
    foreach ($entries as $entry) {
        $isActive = ($entry['slug'] === $currentSlug) ? 'active' : '';
        $hasChildren = !empty($entry['children']);
        
        $html .= '<li class="mb-1">';
        $html .= '<div class="d-flex align-items-center">';
        
        if ($hasChildren) {
            $html .= '<button class="btn btn-sm btn-link p-0 me-1 tree-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-' . $entry['id'] . '">';
            $html .= '<i class="fas fa-chevron-right"></i>';
            $html .= '</button>';
        } else {
            $html .= '<span class="me-3"></span>';
        }
        
        $html .= '<a href="' . $us_url_root . 'users/wiki.php?entry=' . $entry['slug'] . '" class="tree-link ' . $isActive . '">';
        $html .= '<i class="fas fa-' . ($hasChildren ? 'folder' : 'file-alt') . ' me-2"></i>';
        $html .= htmlspecialchars($entry['title']);
        $html .= '</a>';
        $html .= '</div>';
        
        if ($hasChildren) {
            $html .= '<div class="collapse ms-3" id="collapse-' . $entry['id'] . '">';
            $html .= renderTreeView($entry['children'], $currentSlug, $us_url_root);
            $html .= '</div>';
        }
        
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}
?>

<style>
.tree-link {
    color: #495057;
    text-decoration: none;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    display: inline-block;
    transition: all 0.15s ease-in-out;
}

.tree-link:hover {
    background-color: #e9ecef;
    color: #212529;
    text-decoration: none;
}

.tree-link.active {
    background-color: #0d6efd;
    color: white;
}

.tree-toggle {
    border: none !important;
    color: #6c757d;
    transition: transform 0.15s ease-in-out;
}

.tree-toggle:not(.collapsed) {
    transform: rotate(90deg);
}

.wiki-content {
    line-height: 1.6;
}

.wiki-content h1, .wiki-content h2, .wiki-content h3, 
.wiki-content h4, .wiki-content h5, .wiki-content h6 {
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.wiki-content h1:first-child {
    margin-top: 0;
}

.sidebar {
    background-color: #f8f9fa;
    border-right: 1px solid #dee2e6;
}
</style>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 sidebar p-3">
            <h5 class="mb-3">
                <i class="fas fa-book me-2"></i>
                Wiki Knowledge Base
            </h5>
            
            <!-- Search -->
            <form class="mb-3" method="GET" action="">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search..." 
                           value="<?php echo htmlspecialchars(Input::get('search') ?? ''); ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
            
            <!-- Success Messages -->
            <?php if (Input::get('deleted')): ?>
                <div class="alert alert-info alert-sm mb-3">
                    <i class="fas fa-trash me-1"></i> Entry deleted successfully!
                </div>
            <?php endif; ?>
            
            <!-- Add New Entry Button -->
            <div class="mb-3">
                <a href="<?= $us_url_root ?>users/wiki_edit.php" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-plus me-1"></i> New Entry
                </a>
            </div>
            
            <!-- Search Results -->
            <?php if (!empty($searchResults)): ?>
                <div class="search-results mb-3">
                    <h6><i class="fas fa-search me-2"></i>Search Results</h6>
                    <ul class="list-unstyled">
                        <?php foreach ($searchResults as $result): ?>
                            <li class="mb-1">
                                <a href="<?= $us_url_root ?>users/wiki.php?entry=<?php echo $result['slug']; ?>" class="tree-link">
                                    <i class="fas fa-file-alt me-2"></i>
                                    <?php echo htmlspecialchars($result['title']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <hr>
                </div>
            <?php endif; ?>
            
            <!-- Tree View -->
            <div class="tree-view">
                <h6><i class="fas fa-sitemap me-2"></i>Topics</h6>
                <?php if (!empty($allEntries)): ?>
                    <?php echo renderTreeView($allEntries, $slug, $us_url_root); ?>
                <?php else: ?>
                    <p class="text-muted small">No entries available</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9 p-4">
            <?php if (isset($error)): ?>
                <div class="alert alert-warning">
                    <h4><i class="fas fa-exclamation-triangle me-2"></i>Setup Required</h4>
                    <p><?php echo $error; ?></p>
                    <p><strong>To fix this:</strong></p>
                    <ol>
                        <li>Run the database setup script</li>
                        <li>Make sure the plugin table exists</li>
                        <li>Reload this page</li>
                    </ol>
                </div>
            <?php elseif ($currentEntry): ?>
                <!-- Breadcrumbs -->
                <?php 
                $breadcrumbs = $wikiEntry->getBreadcrumbs($currentEntry['id']);
                if (count($breadcrumbs) > 1):
                ?>
                    <nav aria-label="breadcrumb" class="mb-3">
                        <ol class="breadcrumb">
                            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                                <?php if ($index === count($breadcrumbs) - 1): ?>
                                    <li class="breadcrumb-item active" aria-current="page">
                                        <?php echo htmlspecialchars($crumb['title']); ?>
                                    </li>
                                <?php else: ?>
                                    <li class="breadcrumb-item">
                                        <a href="<?= $us_url_root ?>users/wiki.php?entry=<?php echo $crumb['slug']; ?>">
                                            <?php echo htmlspecialchars($crumb['title']); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                <?php endif; ?>
                
                <!-- Entry Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><?php echo htmlspecialchars($currentEntry['title']); ?></h1>
                    <div>
                        <a href="<?= $us_url_root ?>users/wiki_edit.php?id=<?php echo $currentEntry['id']; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                        <a href="<?= $us_url_root ?>users/wiki_upload.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-upload me-1"></i> Upload Markdown
                        </a>
                    </div>
                </div>
                
                <!-- Entry Content -->
                <div class="wiki-content">
                    <?php echo $wikiEntry->markdownToHtml($currentEntry['content']); ?>
                </div>
                
                <!-- Entry Meta -->
                <div class="mt-4 pt-4 border-top">
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i>
                        Last updated: <?php echo date('F j, Y, g:i a', strtotime($currentEntry['updated_at'])); ?>
                    </small>
                </div>
                
            <?php else: ?>
                <!-- Entry Not Found -->
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-circle fa-3x text-muted mb-3"></i>
                    <h3>Entry Not Found</h3>
                    <p class="text-muted">The requested wiki entry could not be found.</p>
                    <a href="<?= $us_url_root ?>users/wiki.php?entry=getting-started" class="btn btn-primary">
                        <i class="fas fa-home me-1"></i> Go to Getting Started
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Auto-expand tree for current entry
document.addEventListener('DOMContentLoaded', function() {
    // Find active link and expand its parents
    const activeLink = document.querySelector('.tree-link.active');
    if (activeLink) {
        let parent = activeLink.closest('.collapse');
        while (parent) {
            parent.classList.add('show');
            const toggle = document.querySelector(`[data-bs-target="#${parent.id}"]`);
            if (toggle) {
                toggle.classList.remove('collapsed');
            }
            parent = parent.parentElement.closest('.collapse');
        }
    }
});

// Handle tree toggle rotation
document.querySelectorAll('.tree-toggle').forEach(function(toggle) {
    toggle.addEventListener('click', function() {
        setTimeout(() => {
            const icon = this.querySelector('i');
            if (this.classList.contains('collapsed')) {
                icon.style.transform = 'rotate(0deg)';
            } else {
                icon.style.transform = 'rotate(90deg)';
            }
        }, 10);
    });
});
</script>

<?php require_once $abs_us_root . $us_url_root . 'users/includes/html_footer.php'; ?>