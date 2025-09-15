<?php
require_once '../../users/init.php';
require_once 'assets/includes/WikiEntry.php';

// Check if user is logged in
if (!$user->isLoggedIn()) {
    Redirect::to($us_url_root . 'users/login.php');
}

$wikiEntry = new WikiEntry();
$entry = null;
$isEdit = false;
$errors = [];
$success = '';

// Get entry ID if editing
if (Input::get('id') && is_numeric(Input::get('id'))) {
    $entry = $wikiEntry->getById(Input::get('id'));
    if ($entry && $wikiEntry->canEdit($entry['id'])) {
        $isEdit = true;
    } else {
        $errors[] = "You don't have permission to edit this entry.";
    }
}

// Handle form submission
if (Input::exists()) {
    if (Token::check(Input::get('csrf'))) {
        $validation = new Validation();
        $validation->check($_POST, [
            'title' => [
                'required' => true,
                'min' => 1,
                'max' => 255
            ],
            'content' => [
                'required' => true
            ]
        ]);

        if ($validation->passed()) {
            $title = Input::get('title');
            $content = Input::get('content');
            $parentId = Input::get('parent_id') ?: null;
            $sortOrder = Input::get('sort_order') ?: 0;

            try {
                if ($isEdit) {
                    // Update existing entry
                    if ($wikiEntry->update($entry['id'], $title, $content, $parentId, $sortOrder)) {
                        $success = "Entry updated successfully!";
                        $entry = $wikiEntry->getById($entry['id']); // Refresh entry data
                    } else {
                        $errors[] = "Failed to update entry. Please try again.";
                    }
                } else {
                    // Create new entry
                    $newId = $wikiEntry->create($title, $content, $parentId, $sortOrder);
                    if ($newId) {
                        $success = "Entry created successfully!";
                        $entry = $wikiEntry->getById($newId);
                        $isEdit = true; // Switch to edit mode
                    } else {
                        $errors[] = "Failed to create entry. Please try again.";
                    }
                }
            } catch (Exception $e) {
                $errors[] = "Error: " . $e->getMessage();
            }
        } else {
            $errors = $validation->errors();
        }
    } else {
        $errors[] = "Invalid CSRF token. Please try again.";
    }
}

// Get all entries for parent dropdown
$allEntries = $wikiEntry->getAllForDropdown($isEdit ? $entry['id'] : null);

// Build hierarchical list for dropdown
function buildHierarchicalList($entries, $parentId = null, $level = 0) {
    $result = [];
    foreach ($entries as $entry) {
        if ($entry['parent_id'] == $parentId) {
            $entry['level'] = $level;
            $result[] = $entry;
            $children = buildHierarchicalList($entries, $entry['id'], $level + 1);
            $result = array_merge($result, $children);
        }
    }
    return $result;
}

$hierarchicalEntries = buildHierarchicalList($allEntries);
?>

<?php require_once '../../users/includes/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><?php echo $isEdit ? 'Edit Wiki Entry' : 'Create New Wiki Entry'; ?></h1>
                <div>
                    <a href="<?= $us_url_root ?>users/admin.php?view=plugins&plugin=uswiki" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Wiki
                    </a>
                    <?php if ($isEdit): ?>
                        <a href="<?= $us_url_root ?>usersc/plugins/uswiki/delete.php?id=<?php echo $entry['id']; ?>" 
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
                        <?php echo Token::generate(); ?>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="title" 
                                   name="title" 
                                   value="<?php echo htmlspecialchars($entry['title'] ?? Input::get('title') ?? ''); ?>" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Parent Entry (optional)</label>
                            <select class="form-select" id="parent_id" name="parent_id">
                                <option value="">-- None (Top Level) --</option>
                                <?php foreach ($hierarchicalEntries as $option): ?>
                                    <option value="<?php echo $option['id']; ?>" 
                                            <?php echo ($entry['parent_id'] ?? Input::get('parent_id') ?? '') == $option['id'] ? 'selected' : ''; ?>>
                                        <?php echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $option['level']) . htmlspecialchars($option['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Choose a parent entry to create a hierarchy</div>
                        </div>

                        <div class="mb-3">
                            <label for="sort_order" class="form-label">Sort Order</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="sort_order" 
                                   name="sort_order" 
                                   value="<?php echo htmlspecialchars($entry['sort_order'] ?? Input::get('sort_order') ?? '0'); ?>">
                            <div class="form-text">Lower numbers appear first</div>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Content (Markdown)</label>
                            <textarea class="form-control" 
                                      id="content" 
                                      name="content" 
                                      rows="15" 
                                      required><?php echo htmlspecialchars($entry['content'] ?? Input::get('content') ?? ''); ?></textarea>
                            <div class="form-text">
                                You can use Markdown syntax. 
                                <a href="https://www.markdownguide.org/basic-syntax/" target="_blank">Learn Markdown</a>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                <?php echo $isEdit ? 'Update Entry' : 'Create Entry'; ?>
                            </button>
                            <a href="<?= $us_url_root ?>usersc/plugins/uswiki/preview.php" 
                               class="btn btn-outline-secondary"
                               target="_blank"
                               onclick="this.href += '?content=' + encodeURIComponent(document.getElementById('content').value)">
                                <i class="fas fa-eye me-1"></i> Preview
                            </a>
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
                            <code>**bold**<br>*italic*<br>`code`</code>

                            <h6 class="mt-3">Lists</h6>
                            <code>- Item 1<br>- Item 2<br><br>1. First<br>2. Second</code>

                            <h6 class="mt-3">Links</h6>
                            <code>[text](URL)</code>

                            <h6 class="mt-3">Code Blocks</h6>
                            <code>```<br>code here<br>```</code>

                            <h6 class="mt-3">Blockquotes</h6>
                            <code>&gt; Quote text</code>
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
                                <a href="<?= $us_url_root ?>users/admin.php?view=plugins&plugin=uswiki&entry=<?php echo $entry['slug']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-external-link-alt me-1"></i> View Entry
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.form-text {
    font-size: 0.875rem;
}

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

<?php require_once '../../users/includes/footer.php'; ?>