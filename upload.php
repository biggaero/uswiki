<?php
require_once '../../users/init.php';
require_once 'assets/includes/WikiEntry.php';

// Check if user is logged in
if (!$user->isLoggedIn()) {
    Redirect::to($us_url_root . 'users/login.php');
}

$wikiEntry = new WikiEntry();
$errors = [];
$success = '';

// Handle file upload
if (Input::exists('post')) {
    if (Token::check(Input::get('csrf'))) {
        if (isset($_FILES['markdown_file']) && $_FILES['markdown_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['markdown_file'];
            
            // Check file type
            $allowedTypes = ['text/plain', 'text/markdown', 'application/octet-stream'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($mimeType, $allowedTypes) && !in_array($extension, ['md', 'txt', 'markdown'])) {
                $errors[] = "Invalid file type. Please upload a markdown (.md) or text (.txt) file.";
            } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
                $errors[] = "File is too large. Maximum size is 5MB.";
            } else {
                $content = file_get_contents($file['tmp_name']);
                
                if ($content === false) {
                    $errors[] = "Could not read the uploaded file.";
                } else {
                    // Extract title from filename or first line
                    $title = Input::get('title');
                    if (empty($title)) {
                        $title = pathinfo($file['name'], PATHINFO_FILENAME);
                        
                        // Try to get title from first H1 in content
                        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
                            $title = trim($matches[1]);
                        }
                    }
                    
                    $parentId = Input::get('parent_id') ?: null;
                    $sortOrder = Input::get('sort_order') ?: 0;
                    
                    // Create the entry
                    try {
                        $newId = $wikiEntry->create($title, $content, $parentId, $sortOrder);
                        if ($newId) {
                            $success = "File uploaded and wiki entry created successfully!";
                            $entry = $wikiEntry->getById($newId);
                        } else {
                            $errors[] = "Failed to create wiki entry.";
                        }
                    } catch (Exception $e) {
                        $errors[] = "Error creating entry: " . $e->getMessage();
                    }
                }
            }
        } else {
            $errors[] = "Please select a file to upload.";
        }
    } else {
        $errors[] = "Invalid CSRF token.";
    }
}

// Get all entries for parent dropdown
$allEntries = $wikiEntry->getAllForDropdown();

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
                <h1><i class="fas fa-upload me-2"></i>Upload Markdown File</h1>
                <a href="<?= $us_url_root ?>users/admin.php?view=plugins&plugin=uswiki" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Wiki
                </a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check me-2"></i><?php echo $success; ?>
                    <?php if (isset($entry)): ?>
                        <br>
                        <a href="<?= $us_url_root ?>users/admin.php?view=plugins&plugin=uswiki&entry=<?php echo $entry['slug']; ?>" class="btn btn-sm btn-outline-success mt-2">
                            <i class="fas fa-eye me-1"></i> View Entry
                        </a>
                        <a href="<?= $us_url_root ?>usersc/plugins/uswiki/edit.php?id=<?php echo $entry['id']; ?>" class="btn btn-sm btn-outline-primary mt-2">
                            <i class="fas fa-edit me-1"></i> Edit Entry
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
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <?php echo Token::generate(); ?>
                                
                                <div class="mb-3">
                                    <label for="markdown_file" class="form-label">Markdown File</label>
                                    <input type="file" 
                                           class="form-control" 
                                           id="markdown_file" 
                                           name="markdown_file" 
                                           accept=".md,.txt,.markdown"
                                           required>
                                    <div class="form-text">Accepted formats: .md, .txt, .markdown (Max size: 5MB)</div>
                                </div>

                                <div class="mb-3">
                                    <label for="title" class="form-label">Title (optional)</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="title" 
                                           name="title" 
                                           value="<?php echo htmlspecialchars(Input::get('title') ?? ''); ?>">
                                    <div class="form-text">Leave empty to use filename or extract from first H1 heading</div>
                                </div>

                                <div class="mb-3">
                                    <label for="parent_id" class="form-label">Parent Entry (optional)</label>
                                    <select class="form-select" id="parent_id" name="parent_id">
                                        <option value="">-- None (Top Level) --</option>
                                        <?php foreach ($hierarchicalEntries as $option): ?>
                                            <option value="<?php echo $option['id']; ?>" 
                                                    <?php echo (Input::get('parent_id') ?? '') == $option['id'] ? 'selected' : ''; ?>>
                                                <?php echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $option['level']) . htmlspecialchars($option['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="sort_order" 
                                           name="sort_order" 
                                           value="<?php echo htmlspecialchars(Input::get('sort_order') ?? '0'); ?>">
                                    <div class="form-text">Lower numbers appear first</div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload me-1"></i> Upload and Create Entry
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle me-2"></i>Upload Instructions</h5>
                        </div>
                        <div class="card-body">
                            <h6>Supported Formats</h6>
                            <ul class="small">
                                <li>Markdown files (.md)</li>
                                <li>Text files (.txt)</li>
                                <li>Markdown files (.markdown)</li>
                            </ul>

                            <h6 class="mt-3">Title Extraction</h6>
                            <p class="small">If you don't specify a title, the system will:</p>
                            <ul class="small">
                                <li>First try to use the first H1 heading (# Title) in your file</li>
                                <li>If no H1 found, use the filename</li>
                            </ul>

                            <h6 class="mt-3">File Size Limit</h6>
                            <p class="small">Maximum file size is 5MB.</p>

                            <h6 class="mt-3">Organization</h6>
                            <p class="small">You can organize your uploaded content by selecting a parent entry to create a hierarchy.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../users/includes/footer.php'; ?>