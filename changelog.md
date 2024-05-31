### 1.3.2
- Fixed error when using page template in Gutenberg

### 1.3.1
- Multisite post clone option was buggy

### 1.3.0
- Code refactoring
- Improved method comments to remove warnings
- Added crop selector for images
- Added sticky option for CPT
- Fixed radio type for custom taxonomies
- Block/Post/Term ACF fields can now be added directly in the yml config file
- Added support for the Carbon Field plugin
- Added "Column" type for the menu in the admin area
- Fixed CSS in the admin area

### 1.2.10
- Added options in General settings to change the default email address and sender name

### 1.2.9
- Added "convert to jpg" option for png files in the media selector
- Removed "x-redirect-by" on ajax calls

### 1.2.8
- Improved post and term copy using the Multisite language switcher plugin

### 1.2.7
- Setting capability_type: true in post_type or taxonomy will now generate the correct capabilities

### 1.2.6
- Fixed possible SQL injection and unsecured CSV export in list-table.php
- Updated vendors

### 1.2.5
- Fixed editor roles when using map meta cap on custom taxonomies

### 1.2.4
- Fixed editor roles typo

### 1.2.3
- Fixed editor roles when using map meta cap on CPT

### 1.2.2
- Removed rewrite sanitization introduced in 1.2.0

### 1.2.1
- Fixed image ID on post/term copy

### 1.2.0
- Fixed CSS in the admin menu
- Removed rewrite warning
- Allowed translation for inline editor from the acf-extensions plugin
- Allowed privacy page edition for editor
- Improved PHP 8.1 compatibility
- Improved roles
- Cloning using the Multisite language switcher plugin now copies terms

#### Breaking change:
When using 'block_render_callback', call your function directly:
> add_filter('block_render_callback', [$this, 'renderBlock']);

### 1.1.7
- Fixed broken clone when using blocks and the Multisite language switcher plugin

### 1.1.6
- Removed unnecessary CSS added by WordPress 6.1
- Allowed non-breakable space in the editor
- WordPress link selector now displays term and post archive on search

### 1.1.5
- Improved post type and taxonomy registration/de-registration

### 1.1.4
- Bugfix using config getter

### 1.1.0
- Deepl integration
- Build hook management
- Improved taxonomy capabilities
- Plugins loading optimization
- Added post state to body class
- Transients cleaner in options
- Improved role management
- Bugfix

### 1.0.2
- Gutenberg block configuration

### 1.0.1
#### Bugfix
- Ajax action echo missing translation debug output
