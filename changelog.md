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
