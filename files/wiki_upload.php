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
$errors = [];
$success = '';

// Handle file upload
if (!empty($_POST)) {
    if (isset($_FILES['markdown_file']) && $_FILES['markdown_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['markdown_file'];
        
        // Check file type
        $allowedExtensions = ['md', 'txt', 'markdown'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = "Invalid file type. Please upload a markdown (.md) or text (.txt) file.";
        } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            $errors[] = "File is too large. Maximum size is 5MB.";
        } else {
            $content = file_get_contents($file['tmp_name']);
            
            if ($content === false) {
                $errors[] = "Could not read the uploaded file.";
            } else {
                // Extract title from filename or first line
                $title = trim(Input::get('title'));
                if (empty($title)) {
                    $title = pathinfo($file['name'], PATHINFO_FILENAME);
                    
                    // Try to get title from first H1 in content
                    if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
                        $title = trim($matches[1]);
                    }
                }
                
                if (empty($title)) {
                    $errors[] = "Title is required. Please provide a title or ensure your markdown file has an H1 header.";
                }
                
                if (empty($errors)) {
                    // Generate slug from title
                    $slug = strtolower(preg_replace('/[^a-z0-9-]/', '-', $title));
                    $slug = preg_replace('/-+/', '-', trim($slug, '-'));
                    
                    // Create the entry
                    try {
                        $result = $db->query("INSERT INTO plg_wiki_entries (title, slug, content, created_by, created_at) VALUES (?, ?, ?, ?, NOW())", 
                            [$title, $slug, $content, $user->data()->id]);
                        if ($result) {
                            $newId = $db->lastId();
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
        }
    } else {
        $errors[] = "Please select a file to upload.";
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
                <h1><i class="fas fa-upload me-2"></i>Upload Markdown File</h1>
                <a href="<?= $us_url_root ?>users/wiki.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Wiki
                </a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check me-2"></i><?php echo $success; ?>
                    <?php if (isset($entry)): ?>
                        <br>
                        <a href="<?= $us_url_root ?>users/wiki.php?entry=<?php echo $entry['slug']; ?>" class="btn btn-sm btn-outline-success mt-2">
                            <i class="fas fa-eye me-1"></i> View Entry
                        </a>
                        <a href="<?= $us_url_root ?>users/wiki_edit.php?id=<?php echo $entry['id']; ?>" class="btn btn-sm btn-outline-primary mt-2">
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
                                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                                    <div class="form-text">Leave empty to use filename or extract from first H1 heading</div>
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

                            <h6 class="mt-3">Markdown Support</h6>
                            <p class="small">Your uploaded files will be processed with basic markdown formatting including headers, bold, italic, and lists.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
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