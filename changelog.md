### 1.5.14
- Taxonomy archive now use post type posts_per_page configuration as fallback
- Added WP_ prefix to constants*
- Removed sidebar resize

### 1.5.13
- Fix canonical on archive page

### 1.5.12
- Replace fil_exists with is_file for better performance on Azure

### 1.5.11
- Show Taxonomy in the main menu, not a child of a Post Type
- Add archive management for Taxonomy
- Add Gutenberg preview size parameter
- Remove wp_speculation_rules_configuration
- Added get_taxonomy_archive_link()
- CSS fix for ACF fields in menus
- CSS fix for 'Wicked folder' plugin
- Better innerBlock management
- Added 'small' button for tinyMCE

### 1.5.10
- Better wp-json api cleaning
- Minor css tweaks
- Fixed a bug for cpt options page when has_archive was not set

### 1.5.9
- After cloning a site, you can now copy ids for msls

### 1.5.8
- posts from ACF relashionship field are now filtered to remove invalid post

### 1.5.7
- removed version from block_editor_settings_theme_css

### 1.5.6
- set page publicly_queryable=true

### 1.5.5
- disable wptexturize globally, caused issues with block rendering 

### 1.5.4
- fixed remove_plugin_block option in yml

### 1.5.3
- add aria label input for menu
- menu admin css bugfix
- prevent admin redirect from unknown url

### 1.5.2
- split css for ACF and Carbon fields
- add fix for zip archive in Simply Static plugin
- added a message in admin footer if a proxy is set up

### 1.5.1
- Add upload image compression ratio settings
- limit term description to 2 lines
- plugin css moved to head

### 1.5.0
- Add post.template and post.term method for Timber/Post
- Removed Yoast class from structured script
- Update custom post type/taxonomy label default configuration
- You can now allow column property in yml to page in cpt configuration
- Removed WordPress default font size, gradient, layout styles
- Added template slug in body_class
- Include table plugin in mce
- Better css management when using Gutenberg with iFrame
- Added "Optimize" media button to resize all image based on image > resize > max height/max width config
- Added image > max size option, to allow large pdf but restrict image size
- Added upload information
- Added rest api ip whitelist
- Added svg option
- Added Gutenberg block categories configuration
- Removed block customClassName by default
- Better Rest API formating with ACF
- ACF BLock now use API V3
- Added Relevanssi plugin rol configuration

### 1.4.7
- Fix Yoast primary term resolution priority

### 1.4.6
- block_editor_style defined in config now removed from main editor if blocks are iframed
- new `block_editor_settings_theme_script` & `block_editor_settings_theme_css` filter

### 1.4.5
- display archive pages when trying to create a link
- remove users from wp sitemap

### 1.4.4
- Extend timber Post to prevent use of post_content for excerpt generation
- added wp_customize section in config.yml

### 1.4.3
- Fixed a bug with Contact form 7 used without cf7 antispam

### 1.4.2
- Fixed a bug with Cache control plugin
- remove_submenu_page was buggy

### 1.4.1
- vendors updated to latest version

### 1.4.0
- Switch to acf block api v3
- Fix sidebar bug with WP6.6
- Better cpt name management
- Added "archive" entry group in menu for better ux
- Added "orderby: last_word" option for cpt

### 1.3.7
- Better ACF link management when cloning post using Multisite language switcher

### 1.3.6
- Prevent cf7-antispam from loading on all pages

- ### 1.3.5
- Fix sql query not using the right prefix for sticky post

### 1.3.4
- Remove warning when custom post types or taxonomies is empty in config file

### 1.3.3
- Added condition to output error on login while debugging

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
