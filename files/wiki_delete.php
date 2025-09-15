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

// Get entry ID
if (!Input::get('id') || !is_numeric(Input::get('id'))) {
    $errors[] = "Invalid entry ID.";
} else {
    $entry = $wikiEntry->getById(Input::get('id'));
    if (!$entry) {
        $errors[] = "Entry not found.";
    } else {
        // Perform deletion
        try {
            $result = $db->query("DELETE FROM plg_wiki_entries WHERE id = ?", [$entry['id']]);
            if ($result) {
                // Redirect with success message
                Redirect::to($us_url_root . 'users/wiki.php?deleted=1');
            } else {
                $errors[] = "Failed to delete entry. Please try again.";
            }
        } catch (Exception $e) {
            $errors[] = "Error deleting entry: " . $e->getMessage();
        }
    }
}

// If we get here, there was an error
require_once $abs_us_root . $us_url_root . 'users/includes/template/prep.php';
$hooks = getMyHooks();
includeHook($hooks, 'pre');
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-exclamation-triangle me-2"></i>Error</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <a href="<?= $us_url_root ?>users/wiki.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Wiki
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $abs_us_root . $us_url_root . 'users/includes/html_footer.php'; ?>