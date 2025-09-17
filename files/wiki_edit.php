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

// Handle markdown file upload
if (isset($_POST['upload_markdown']) && isset($_FILES['markdown_file'])) {
    if ($_FILES['markdown_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['markdown_file'];
        
        // Check file type
        $allowedExtensions = ['md', 'txt', 'markdown'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = "Invalid file type. Please upload a markdown (.md), text (.txt), or markdown (.markdown) file.";
        } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            $errors[] = "File is too large. Maximum size is 5MB.";
        } else {
            $uploadedContent = file_get_contents($file['tmp_name']);
            
            if ($uploadedContent === false) {
                $errors[] = "Could not read the uploaded file.";
            } else {
                // Extract title from filename or first H1 header if title is empty
                $uploadedTitle = trim(Input::get('title'));
                if (empty($uploadedTitle)) {
                    $uploadedTitle = pathinfo($file['name'], PATHINFO_FILENAME);
                    
                    // Try to get title from first H1 in content
                    if (preg_match('/^#\s+(.+)$/m', $uploadedContent, $matches)) {
                        $uploadedTitle = trim($matches[1]);
                    }
                }
                
                // Set the uploaded content and title for the form
                $_POST['title'] = $uploadedTitle;
                $_POST['content'] = $uploadedContent;
                $success = "Markdown file uploaded successfully! You can now edit the content below.";
            }
        }
    } else {
        $errors[] = "Error uploading file. Please try again.";
    }
}

// Handle form submission
if (!empty($_POST) && !isset($_POST['upload_markdown'])) {
    $title = trim(Input::get('title'));
    $content = trim(Input::get('content'));
    $editorMode = Input::get('editor_mode') ?: 'markdown'; // Default to markdown if not specified
    
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
                    <!-- File Upload Section -->
                    <div class="card mb-4" id="upload-section">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-upload me-2"></i>Quick Import</h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleUploadSection()">
                                <span id="upload-toggle-text">Show</span> <i class="fas fa-chevron-down" id="upload-toggle-icon"></i>
                            </button>
                        </div>
                        <div class="card-body" id="upload-content" style="display: none;">
                            <form method="POST" enctype="multipart/form-data" id="upload-form">
                                <div class="row">
                                    <div class="col-md-8">
                                        <label for="markdown_file" class="form-label">Upload Markdown File</label>
                                        <input type="file" 
                                               class="form-control" 
                                               id="markdown_file" 
                                               name="markdown_file" 
                                               accept=".md,.txt,.markdown">
                                        <div class="form-text">Supported formats: .md, .txt, .markdown (Max: 5MB)</div>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" name="upload_markdown" class="btn btn-outline-primary">
                                            <i class="fas fa-upload me-1"></i> Import File
                                        </button>
                                    </div>
                                </div>
                                <div class="form-text mt-2">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Importing will populate the title and content fields below. You can then edit as needed.
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Main Editor Form -->
                    <form method="POST" id="editor-form">
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
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label for="content" class="form-label mb-0">Content</label>
                                <div class="btn-group btn-group-sm" role="group" aria-label="Editor mode">
                                    <input type="radio" class="btn-check" name="editor-mode" id="markdown-mode" value="markdown" checked>
                                    <label class="btn btn-outline-primary" for="markdown-mode">
                                        <i class="fas fa-code me-1"></i> Markdown
                                    </label>
                                    <input type="radio" class="btn-check" name="editor-mode" id="wysiwyg-mode" value="wysiwyg">
                                    <label class="btn btn-outline-primary" for="wysiwyg-mode">
                                        <i class="fas fa-eye me-1"></i> WYSIWYG
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Markdown Editor -->
                            <div id="markdown-editor">
                                <textarea class="form-control" 
                                          id="content" 
                                          name="content" 
                                          rows="15" 
                                          required><?php echo htmlspecialchars($entry['content'] ?? $_POST['content'] ?? ''); ?></textarea>
                                <div class="form-text">
                                    You can use Markdown syntax: **bold**, *italic*, # headers, - lists
                                </div>
                            </div>
                            
                            <!-- WYSIWYG Editor -->
                            <div id="wysiwyg-editor" style="display: none;">
                                <div id="summernote"></div>
                                <div class="form-text">
                                    WYSIWYG editor - content will be converted to Markdown when saved
                                </div>
                            </div>
                            
                            <!-- Hidden field for editor mode -->
                            <input type="hidden" id="editor-mode-field" name="editor_mode" value="markdown">
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
                            <h5><i class="fas fa-info-circle me-2"></i>Editor Help</h5>
                        </div>
                        <div class="card-body">
                            <h6>Quick Import</h6>
                            <p class="small mb-2">Use the "Quick Import" section above to upload existing markdown files and populate the editor.</p>
                            
                            <h6>Editor Modes</h6>
                            <p class="small mb-2">Switch between Markdown and WYSIWYG editing modes. Your preference is saved automatically.</p>
                            
                            <h6>Markdown Syntax</h6>
                            <code># H1<br>## H2<br>### H3</code>
                            <br><br>
                            <code>**bold**<br>*italic*</code>
                            <br><br>
                            <code>- Item 1<br>- Item 2</code>
                            <br><br>
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

#wysiwyg-editor .note-editor {
    border-radius: 0.375rem;
}

#wysiwyg-editor .note-toolbar {
    border-top-left-radius: 0.375rem;
    border-top-right-radius: 0.375rem;
}

#wysiwyg-editor .note-editing-area {
    border-bottom-left-radius: 0.375rem;
    border-bottom-right-radius: 0.375rem;
}

/* Upload section styling */
#upload-section .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

#upload-section .card-header h6 {
    color: #495057;
    font-weight: 600;
}

#upload-section .btn-outline-secondary {
    border-color: transparent;
    color: #6c757d;
}

#upload-section .btn-outline-secondary:hover {
    background-color: #e9ecef;
    border-color: #adb5bd;
}

#upload-toggle-icon {
    transition: transform 0.2s ease-in-out;
}

#upload-content {
    transition: all 0.3s ease-in-out;
}
</style>

<!-- Summernote CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs5.min.css" rel="stylesheet">

<!-- jQuery fallback -->
<script>
    if (typeof jQuery === 'undefined') {
        document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"><\/script>');
    }
</script>

<!-- Summernote JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs5.min.js"></script>

<!-- Turndown.js for HTML to Markdown conversion -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/turndown/7.1.2/turndown.min.js"></script>

<!-- Marked.js for Markdown to HTML conversion -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/marked/4.3.0/marked.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentMode = 'markdown';
    let summernoteInitialized = false;
    const markdownTextarea = document.getElementById('content');
    const summernoteDiv = document.getElementById('summernote');
    const markdownEditor = document.getElementById('markdown-editor');
    const wysiwygEditor = document.getElementById('wysiwyg-editor');
    const editorModeField = document.getElementById('editor-mode-field');
    const markdownModeBtn = document.getElementById('markdown-mode');
    const wysiwygModeBtn = document.getElementById('wysiwyg-mode');
    
    // Initialize Turndown for HTML to Markdown conversion
    const turndownService = new TurndownJS({
        headingStyle: 'atx',
        bulletListMarker: '-',
        codeBlockStyle: 'fenced'
    });
    
    // Configure marked for Markdown to HTML conversion
    marked.setOptions({
        breaks: true,
        sanitize: false
    });
    
    function initializeSummernote() {
        if (summernoteInitialized) return;
        
        $('#summernote').summernote({
            height: 400,
            minHeight: 200,
            placeholder: 'Start writing your content...',
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
                ['fontname', ['fontname']],
                ['color', ['color']],
                ['para', ['ol', 'ul', 'paragraph', 'height']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'hr']],
                ['view', ['fullscreen', 'codeview']],
                ['help', ['help']]
            ],
            popover: {
                image: [
                    ['image', ['resizeFull', 'resizeHalf', 'resizeQuarter', 'resizeNone']],
                    ['float', ['floatLeft', 'floatRight', 'floatNone']],
                    ['remove', ['removeMedia']]
                ],
                link: [
                    ['link', ['linkDialogShow', 'unlink']]
                ],
                table: [
                    ['add', ['addRowDown', 'addRowUp', 'addColLeft', 'addColRight']],
                    ['delete', ['deleteRow', 'deleteCol', 'deleteTable']]
                ]
            }
        });
        
        summernoteInitialized = true;
    }
    
    function convertMarkdownToHtml(markdown) {
        try {
            return marked.parse(markdown);
        } catch (e) {
            console.error('Markdown to HTML conversion error:', e);
            return markdown; // Return original if conversion fails
        }
    }
    
    function convertHtmlToMarkdown(html) {
        try {
            return turndownService.turndown(html);
        } catch (e) {
            console.error('HTML to Markdown conversion error:', e);
            return html; // Return original if conversion fails
        }
    }
    
    function switchToMarkdown() {
        if (currentMode === 'markdown') return;
        
        // Convert WYSIWYG content to Markdown
        if (summernoteInitialized) {
            const htmlContent = $('#summernote').summernote('code');
            const markdownContent = convertHtmlToMarkdown(htmlContent);
            markdownTextarea.value = markdownContent;
        }
        
        // Show/hide appropriate editors
        wysiwygEditor.style.display = 'none';
        markdownEditor.style.display = 'block';
        
        // Update mode tracking
        currentMode = 'markdown';
        editorModeField.value = 'markdown';
        
        // Focus on markdown textarea
        markdownTextarea.focus();
    }
    
    function switchToWysiwyg() {
        if (currentMode === 'wysiwyg') return;
        
        // Initialize Summernote if not already done
        if (!summernoteInitialized) {
            initializeSummernote();
        }
        
        // Convert Markdown content to HTML
        const markdownContent = markdownTextarea.value;
        const htmlContent = convertMarkdownToHtml(markdownContent);
        $('#summernote').summernote('code', htmlContent);
        
        // Show/hide appropriate editors
        markdownEditor.style.display = 'none';
        wysiwygEditor.style.display = 'block';
        
        // Update mode tracking
        currentMode = 'wysiwyg';
        editorModeField.value = 'wysiwyg';
        
        // Focus on Summernote editor
        $('#summernote').summernote('focus');
    }
    
    // Event listeners for mode toggle
    markdownModeBtn.addEventListener('change', function() {
        if (this.checked) {
            switchToMarkdown();
        }
    });
    
    wysiwygModeBtn.addEventListener('change', function() {
        if (this.checked) {
            switchToWysiwyg();
        }
    });
    
    // Handle form submission for main editor form
    document.querySelector('#editor-form').addEventListener('submit', function(e) {
        if (currentMode === 'wysiwyg' && summernoteInitialized) {
            // Convert WYSIWYG content to Markdown before submission
            const htmlContent = $('#summernote').summernote('code');
            const markdownContent = convertHtmlToMarkdown(htmlContent);
            markdownTextarea.value = markdownContent;
        }
    });
    
    // Load user preference from localStorage
    const savedMode = localStorage.getItem('wiki-editor-mode');
    if (savedMode === 'wysiwyg') {
        wysiwygModeBtn.checked = true;
        markdownModeBtn.checked = false;
        // Delay initialization to ensure DOM is ready
        setTimeout(() => switchToWysiwyg(), 100);
    }
    
    // Save user preference when mode changes
    markdownModeBtn.addEventListener('change', function() {
        if (this.checked) {
            localStorage.setItem('wiki-editor-mode', 'markdown');
        }
    });
    
    wysiwygModeBtn.addEventListener('change', function() {
        if (this.checked) {
            localStorage.setItem('wiki-editor-mode', 'wysiwyg');
        }
    });
});

// Toggle upload section visibility
function toggleUploadSection() {
    const uploadContent = document.getElementById('upload-content');
    const toggleText = document.getElementById('upload-toggle-text');
    const toggleIcon = document.getElementById('upload-toggle-icon');
    
    if (uploadContent.style.display === 'none' || uploadContent.style.display === '') {
        uploadContent.style.display = 'block';
        toggleText.textContent = 'Hide';
        toggleIcon.className = 'fas fa-chevron-up';
        localStorage.setItem('wiki-upload-section-visible', 'true');
    } else {
        uploadContent.style.display = 'none';
        toggleText.textContent = 'Show';
        toggleIcon.className = 'fas fa-chevron-down';
        localStorage.setItem('wiki-upload-section-visible', 'false');
    }
}

// Initialize upload section visibility based on localStorage
document.addEventListener('DOMContentLoaded', function() {
    const uploadSectionVisible = localStorage.getItem('wiki-upload-section-visible');
    if (uploadSectionVisible === 'true') {
        toggleUploadSection();
    }
    
    // Add confirmation dialog for file upload if content exists
    const uploadForm = document.getElementById('upload-form');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            const titleField = document.getElementById('title');
            const contentField = document.getElementById('content');
            
            // Check if there's existing content
            const hasTitle = titleField && titleField.value.trim() !== '';
            const hasContent = contentField && contentField.value.trim() !== '';
            
            if (hasTitle || hasContent) {
                const confirmMessage = 'You have existing content in the editor. Uploading a file will replace the current title and content. Do you want to continue?';
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
});
</script>

<?php require_once $abs_us_root . $us_url_root . 'users/includes/html_footer.php'; ?>
