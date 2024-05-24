### 1.3.0
- Code refactoring
- Better methods comments to remove warning
- Add crop selector for image
- Add sticky option for CPT
- Fixed radio type for custom taxonomies
- Block/Post/Term ACF fields can be added directly in yml config file
- Carbon Field plugin support
- Added "Column" type for menu in admin area
- Fixed CSS in admin area

### 1.2.10
- Added options in General settings to change default email address and sender name

### 1.2.9
- Add "convert to jpg" option for png file in media selector
- Remove "x-redirect-by" on ajax call

### 1.2.8
- Better post and term copy using Multisite language switcher plugin

### 1.2.7
- Setting capability_type: true in post_type or taxonomy will now generate the right capabilities

### 1.2.6
- Fix possible sql injection and unsecured csv export in list-table.php
- Update vendors

### 1.2.5
- Fix editor roles when using map meta cap on custom taxonomy

### 1.2.4
- Fix editor roles typo

### 1.2.3
- Fix editor roles when using map meta cap on cpt

### 1.2.2
- Remove rewrite sanitization introduced in 1.2.0

### 1.2.1
- Fix image id on post/term copy

### 1.2.0
- Fix css in admin menu
- Remove rewrite warning
- Allow translation for inline editor from acf-extensions plugin
- Allow privacy page edition for editor
- Better php8.1 compatibility
- Better roles
- Clone using Multisite language switcher plugin now copy terms

#### Breaking change :
when using 'block_render_callback', call your function directly:
> add_filter( 'block_render_callback', [$this, 'renderBlock']);

### 1.1.7
- Fix broken clone when using blocks and Multisite language switcher plugin

### 1.1.6
- Remove unnecessary Css added by Wordpress 6.1
- Allow non-breakable space in editor
- WordPress link selector now display term and post archive on search

### 1.1.5
- Better post type and taxonomy registration/de-registration

### 1.1.4
- Bugfix using config getter

### 1.1.0
- Deepl integration
- Build hook management
- Better taxonomy capabilities
- Plugins loading optimisation
- Add post state to body class
- Transients cleaner in options
- Better role management
- Bugfix

### 1.0.2
- Gutenberg block configuration

### 1.0.1
#### Bugfix
 - ajax action echo missing translation debug output 
