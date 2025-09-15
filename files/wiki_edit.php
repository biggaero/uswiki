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

$wikiEntry = new SimpleWikiEntry();
$entry = null;
$isEdit = false;
$errors = [];
$success = '';

// Get entry ID if editing
if (Input::get('id') && is_numeric(Input::get('id'))) {
    $entry = $wikiEntry->getById(Input::get('id'));
    if ($entry) {
        $isEdit = true;
    }
}

// Handle form submission
if (!empty($_POST)) {
    $title = trim(Input::get('title'));
    $content = trim(Input::get('content'));
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    if (empty($content)) {
        $errors[] = "Content is required";
    }
    
    if (empty($errors)) {
        // Generate slug from title
        $slug = strtolower(preg_replace('/[^a-z0-9-]/', '-', $title));
        $slug = preg_replace('/-+/', '-', trim($slug, '-'));
        
        try {
            if ($isEdit) {
                // Update existing entry
                $result = $db->query("UPDATE plg_wiki_entries SET title = ?, slug = ?, content = ?, updated_by = ?, updated_at = NOW() WHERE id = ?", 
                    [$title, $slug, $content, $user->data()->id, $entry['id']]);
                if ($result) {
                    $success = "Entry updated successfully!";
                    $entry = $wikiEntry->getById($entry['id']); // Refresh entry data
                } else {
                    $errors[] = "Failed to update entry";
                }
            } else {
                // Create new entry
                $result = $db->query("INSERT INTO plg_wiki_entries (title, slug, content, created_by, created_at) VALUES (?, ?, ?, ?, NOW())", 
                    [$title, $slug, $content, $user->data()->id]);
                if ($result) {
                    $newId = $db->lastId();
                    $success = "Entry created successfully!";
                    $entry = $wikiEntry->getById($newId);
                    $isEdit = true; // Switch to edit mode
                } else {
                    $errors[] = "Failed to create entry";
                }
            }
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

require_once $abs_us_root . $us_url_root . 'users/includes/template/prep.php';
$hooks = getMyHooks();
includeHook($hooks, 'pre');
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><?php echo $isEdit ? 'Edit Wiki Entry' : 'Create New Wiki Entry'; ?></h1>
                <div>
                    <a href="<?= $us_url_root ?>users/wiki.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Wiki
                    </a>
                    <?php if ($isEdit): ?>
                        <a href="<?= $us_url_root ?>users/wiki_delete.php?id=<?php echo $entry['id']; ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this entry?')">
                            <i class="fas fa-trash me-1"></i> Delete
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check me-2"></i><?php echo $success; ?>
                    <?php if ($entry): ?>
                        <br><a href="<?= $us_url_root ?>users/wiki.php?entry=<?php echo $entry['slug']; ?>" class="btn btn-sm btn-outline-success mt-2">
                            <i class="fas fa-eye me-1"></i> View Entry
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="title" 
                                   name="title" 
                                   value="<?php echo htmlspecialchars($entry['title'] ?? $_POST['title'] ?? ''); ?>" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Content (Markdown)</label>
                            <textarea class="form-control" 
                                      id="content" 
                                      name="content" 
                                      rows="15" 
                                      required><?php echo htmlspecialchars($entry['content'] ?? $_POST['content'] ?? ''); ?></textarea>
                            <div class="form-text">
                                You can use Markdown syntax: **bold**, *italic*, # headers, - lists
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                <?php echo $isEdit ? 'Update Entry' : 'Create Entry'; ?>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle me-2"></i>Markdown Help</h5>
                        </div>
                        <div class="card-body">
                            <h6>Headers</h6>
                            <code># H1<br>## H2<br>### H3</code>

                            <h6 class="mt-3">Formatting</h6>
                            <code>**bold**<br>*italic*</code>

                            <h6 class="mt-3">Lists</h6>
                            <code>- Item 1<br>- Item 2</code>

                            <h6 class="mt-3">Links</h6>
                            <code>[text](URL)</code>
                        </div>
                    </div>

                    <?php if ($isEdit): ?>
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5><i class="fas fa-info me-2"></i>Entry Info</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Created:</strong><br><?php echo date('F j, Y, g:i a', strtotime($entry['created_at'])); ?></p>
                                <p><strong>Last Updated:</strong><br><?php echo date('F j, Y, g:i a', strtotime($entry['updated_at'])); ?></p>
                                <p><strong>Slug:</strong><br><code><?php echo htmlspecialchars($entry['slug']); ?></code></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
code {
    color: #e83e8c;
    background-color: #f8f9fa;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
}

.card h6 {
    color: #495057;
    margin-bottom: 0.5rem;
    margin-top: 1rem;
}

.card h6:first-child {
    margin-top: 0;
}
</style>

<?php require_once $abs_us_root . $us_url_root . 'users/includes/html_footer.php'; ?>