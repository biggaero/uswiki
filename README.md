# UserSpice Wiki Plugin

A comprehensive wiki knowledge base plugin for UserSpice that provides hierarchical organization, markdown support, and full-text search capabilities.

## Features

- **Hierarchical Organization**: Create nested wiki entries with parent-child relationships
- **Markdown Support**: Full markdown syntax support with live preview
- **Search Functionality**: Full-text search across all entries
- **User Permissions**: Integration with UserSpice's permission system
- **File Upload**: Upload markdown files directly to create wiki entries
- **Bootstrap 5 UI**: Modern, responsive interface
- **SEO-Friendly URLs**: Clean slug-based URLs for entries

## Requirements

- UserSpice 5.7+
- PHP 7.4+
- MySQL 5.7+
- Parsedown library (included)

## Installation

1. **Download**: Copy the `uswiki` folder to your `usersc/plugins/` directory

2. **Install via Plugin Manager**: 
   - Go to Admin Dashboard → Plugin Manager
   - Find "UserSpice Wiki" in the list
   - Click "Activate" , this will create a menu item and install db table
   

3. **Access**: After installation:
   - Click "Wiki" in the Plugin Manager for admin access
   - Users can access via the new menu link that was automatically created
   - In the event that you do not want to use the ultramenu link, do not delete the menu item, disable it in Ultramenu

## File Structure

```
uswiki/
├── assets/
│   ├── css/                    # Stylesheets (if needed)
│   ├── js/                     # JavaScript files (if needed)
│   ├── images/                 # Image assets
│   ├── includes/
│   │   ├── WikiEntry.php       # Core wiki functionality
│   │   └── vendor/             # Parsedown library
│   └── parsers/                # AJAX handlers (if needed)
├── files/                      # Installation files
├── hooks/                      # UserSpice page hooks
├── menu_hooks/                 # Menu integration
├── configure.php               # Main wiki interface
├── edit.php                    # Create/edit entries
├── delete.php                  # Delete entries
├── upload.php                  # Upload markdown files
├── preview.php                 # Markdown preview
├── functions.php               # Helper functions
├── info.xml                    # Plugin metadata
├── install.php                 # Installation script
├── uninstall.php              # Uninstallation script
├── migrate.php                 # Database migrations
├── plugin_info.php            # Plugin name definition
└── README.md                   # This file
```

## Usage

### Creating Entries

1. Click "New Entry" in the sidebar
2. Enter a title and markdown content
3. Optionally select a parent entry to create hierarchy
4. Click "Create Entry"

### Editing Entries

1. Navigate to any entry
2. Click the "Edit" button (if you have permission)
3. Modify the content
4. Click "Update Entry"

### Uploading Files

1. Click "Upload Markdown" 
2. Select a `.md`, `.txt`, or `.markdown` file
3. Optionally specify title and parent
4. Click "Upload and Create Entry"

### Searching

Use the search box in the sidebar to find entries by title or content.

### Permissions

- **Admins**: Can edit all entries
- **Entry Creators**: Can edit their own entries
- **All Users**: Can view published entries

## Database Schema

The plugin creates one main table:

### `plg_wiki_entries`

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| title | VARCHAR(255) | Entry title |
| slug | VARCHAR(255) | URL-friendly slug |
| content | LONGTEXT | Markdown content |
| parent_id | INT | Parent entry ID (nullable) |
| sort_order | INT | Display order |
| is_published | TINYINT | Published status |
| created_by | INT | User ID who created entry |
| updated_by | INT | User ID who last updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

## Customization

### Styling

Add custom CSS to `assets/css/wiki.css` and modify the `configure.php` file to include it.

### Functionality

Modify `assets/includes/WikiEntry.php` to add custom functionality like:
- View counters
- Entry ratings
- Comment systems
- File attachments

### Hooks

Use UserSpice's hook system to integrate wiki functionality into other pages:

```php
// In install.php, add hooks
$hooks['dashboard.php']['body'] = 'hooks/dashboard_wiki_widget.php';
```

## Troubleshooting

### Common Issues

1. **"UserSpice not found" error**: Make sure the plugin is in the correct directory structure
2. **Database errors**: Check that your MySQL user has CREATE TABLE permissions
3. **Permission errors**: Ensure the web server can write to the uploads directory
4. **Markdown not rendering**: Verify the Parsedown library is in `assets/includes/vendor/`

### Debug Mode

Enable UserSpice debug mode to see detailed error messages.

## Development

### Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

### Adding New Features

When adding features, follow these guidelines:

1. **Database Changes**: Add migrations to `migrate.php`
2. **New Pages**: Follow UserSpice conventions for security and validation
3. **Functions**: Add to `functions.php` with proper existence checks
4. **Styling**: Keep consistent with Bootstrap 5 and UserSpice themes

## License

This plugin is open source and available under the MIT License.

## Support

For support, please:

1. Check this README
2. Review UserSpice documentation
3. Post in UserSpice forums
4. Submit GitHub issues

## Changelog

### v0.1.7 ✅ WORKING
- ✅ Full UserSpice integration
- ✅ Hierarchical wiki organization
- ✅ Markdown support with basic formatting
- ✅ Search functionality
- ✅ Complete CRUD operations (Create, Read, Update, Delete)
- ✅ Markdown file upload functionality
- ✅ User authentication and permissions
- ✅ Bootstrap 5 responsive UI
- ✅ Database table creation and management
- ✅ Automatic slug generation
- ✅ User tracking (created_by, updated_by)

### Installation Notes
- Plugin successfully tested and working
- Files automatically copied to users/ directory during installation
- Database table created with sample data
- All functionality verified working
