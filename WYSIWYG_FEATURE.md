# WYSIWYG Editor Feature

## Overview

The UserSpice Wiki plugin now includes a **WYSIWYG (What You See Is What You Get)** editor option using Summernote. This allows users to create and edit wiki entries using a rich text editor instead of markdown syntax.

## Features

### 🔄 **Dual Editor Mode**
- **Markdown Mode**: Traditional markdown text editor for users who prefer writing in markdown
- **WYSIWYG Mode**: Rich text editor with visual formatting tools
- Seamless switching between modes with content preservation

### ✨ **Rich Text Editing**
- Visual formatting toolbar with common formatting options
- Support for:
  - Text styling (bold, italic, underline, strikethrough)
  - Headers and paragraphs
  - Lists (ordered and unordered)
  - Links and images
  - Tables
  - Code blocks
  - Colors and fonts

### 🔁 **Automatic Conversion**
- **Markdown → HTML**: When switching from markdown to WYSIWYG mode, content is automatically converted to HTML
- **HTML → Markdown**: When switching from WYSIWYG to markdown mode or saving, HTML content is converted back to markdown
- All content is stored as markdown in the database for consistency

### 💾 **User Preferences**
- Editor mode preference is saved in browser localStorage
- Users' preferred editor mode is automatically selected when creating new entries
- Upload section visibility preference is also saved
- Preferences persist across browser sessions

### 📁 **File Upload Integration**
- **Quick Import**: Upload markdown files directly in the editor page
- **Smart Title Extraction**: Automatically extracts title from filename or H1 headers
- **Content Population**: Uploaded content populates the editor for further editing
- **Overwrite Protection**: Confirmation dialog if existing content would be replaced
- **File Validation**: Supports .md, .txt, .markdown files up to 5MB

## How It Works

### Editor Toggle
1. At the top right of the content field, users can toggle between "Markdown" and "WYSIWYG" modes
2. Content is automatically converted when switching modes
3. The selected mode is remembered for future use

### Content Storage
- All content is stored as **Markdown** in the database
- WYSIWYG content is converted to markdown before saving
- This ensures backward compatibility and consistent data format

### Libraries Used
- **Summernote**: Rich text editor (v0.8.20)
- **Marked.js**: Markdown to HTML conversion (v4.3.0)  
- **Turndown.js**: HTML to Markdown conversion (v7.1.2)
- **jQuery**: Required dependency (with CDN fallback)

## Technical Implementation

### Files Modified
- `files/wiki_edit.php` - Main editor page with dual-mode functionality

### JavaScript Features
- Mode switching logic
- Content conversion between HTML and Markdown
- LocalStorage integration for user preferences
- Form submission handling
- Error handling for conversion failures

### CSS Styling
- Custom styles for editor mode toggle buttons
- Summernote editor styling to match UserSpice theme
- Responsive design considerations

## Usage Instructions

### For End Users
1. **Creating New Entry**: Navigate to the wiki editor and choose your preferred editing mode
2. **Quick Import**: Use the "Quick Import" section to upload existing markdown files
3. **Switching Modes**: Click the "Markdown" or "WYSIWYG" buttons to switch editor modes
4. **WYSIWYG Editing**: Use the rich text toolbar to format content visually
5. **Markdown Editing**: Write content using standard markdown syntax
6. **File Upload**: Click "Show" on Quick Import to upload .md, .txt, or .markdown files
7. **Saving**: Content is automatically converted to markdown format when saved

### For Administrators
- No additional configuration required
- All existing markdown content works seamlessly with the new editor
- WYSIWYG mode provides a more user-friendly experience for non-technical users

## Browser Compatibility

The WYSIWYG editor works on all modern browsers that support:
- ES6 JavaScript features
- CSS Grid and Flexbox
- LocalStorage API

Tested on:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Benefits

### For Users
- **Easier Content Creation**: Visual editor reduces learning curve for markdown
- **Faster Editing**: WYSIWYG tools speed up content formatting
- **Flexible Choice**: Users can choose their preferred editing method
- **Consistent Experience**: Content looks the same regardless of editor used

### For Administrators
- **Backward Compatible**: All existing content continues to work
- **No Data Migration**: Markdown storage format maintained
- **User-Friendly**: Reduces support requests about markdown syntax
- **Professional Appearance**: Rich text editing creates more polished content

## Future Enhancements

Potential future improvements could include:
- Custom toolbar configuration
- Plugin extensions for Summernote
- Enhanced image upload integration
- Real-time collaborative editing
- Custom styling themes