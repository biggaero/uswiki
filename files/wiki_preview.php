<?php
require_once 'init.php';
require_once '../usersc/plugins/uswiki/assets/includes/WikiEntry.php';
require_once '../usersc/plugins/uswiki/functions.php';

// Check if user is logged in
if (!$user->isLoggedIn()) {
    Redirect::to($us_url_root . 'users/login.php');
}

$wikiEntry = new WikiEntry();
$content = Input::get('content') ?: '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Markdown Preview - Wiki</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
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
        
        .wiki-content pre {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.375rem;
            border: 1px solid #dee2e6;
        }
        
        .wiki-content blockquote {
            border-left: 4px solid #0d6efd;
            padding-left: 1rem;
            margin-left: 0;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-eye me-2"></i>Markdown Preview</h1>
            <button onclick="window.close()" class="btn btn-secondary">
                <i class="fas fa-times me-1"></i> Close
            </button>
        </div>
        
        <?php if (empty($content)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No content to preview. Enter some markdown content in the editor and click preview.
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body wiki-content">
                    <?php echo $wikiEntry->markdownToHtml($content); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>