# Official WordPress Plugin Development & Security Standards

Reference documentation:
* [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/intro/)
* [WordPress Security APIs Handbook](https://developer.wordpress.org/apis/security/)

---

## 1. Core Architecture & Standards

1. **Never Touch WordPress Core**: All extensions must be implemented as plugin code inside `wp-content/plugins/`.
2. **Direct Access Guard**: Every PHP file in the plugin must begin with a direct access check:
   ```php
   if ( ! defined( 'ABSPATH' ) ) {
       exit; // Exit if accessed directly.
   }
   ```
3. **Plugin Main File & Headers**: Main plugin file (`pixel-on-wp.php`) must contain standard WordPress Plugin Header comment fields (`Plugin Name`, `Plugin URI`, `Description`, `Version`, `Author`, `Text Domain`, `Domain Path`, `Requires at least`, `Requires PHP`, `License`).
4. **Namespaces & Unique Prefixing**: All classes, global functions, constants, options, database tables, and hook names MUST be prefixed with a unique prefix (e.g., `PixelOnWP\`, `pixelonwp_`, `PixelOnWP_`) to prevent naming collisions.

---

## 2. WordPress Security API Guidelines

### 2.1 Core Security Mindset
- **Never Trust User Input**: Don't trust user input, third-party APIs, cookies, server arrays, or unverified database data.
- **Sanitize Input Early, Escape Output Late**: Validate and sanitize data upon ingestion. Escape data at the exact moment of output rendering.
- **Validation > Sanitization**: Validation (rejecting invalid input) is preferred over sanitization (cleaning invalid input).

### 2.2 Input Sanitization
- `sanitize_text_field( $str )`: Cleans single-line text inputs (strips HTML tags, octets, extra whitespace).
- `sanitize_textarea_field( $str )`: Cleans multi-line text input while preserving newline breaks.
- `sanitize_email( $email )`: Strips invalid email characters.
- `sanitize_key( $key )` / `sanitize_title( $title )`: Sanitizes keys, slugs, and system strings (lowercase, alphanumeric, dashes/underscores).
- `esc_url_raw( $url )`: Cleans URLs for database storage or backend API calls.
- Array Sanitization: Sanitize every item recursively using `array_map( 'sanitize_text_field', $array )`.

### 2.3 Output Escaping
- `esc_html( $text )`: Escapes HTML text nodes. Use `esc_html_e()` or `esc_html__()` for localized strings.
- `esc_attr( $text )`: Escapes HTML attributes (`value="<?php echo esc_attr( $val ); ?>"`). Use `esc_attr_e()` for localized attributes.
- `esc_url( $url )`: Escapes URLs for output in `href`, `src`, or action attributes.
- `wp_json_encode( $data )`: Encodes PHP arrays/objects safely for JavaScript injection.
- `wp_kses( $string, $allowed_html )` / `wp_kses_post( $string )`: Escapes HTML while allowing safe HTML tags.

### 2.4 CSRF Protection with Nonces
- **Generating Nonces**:
  - URLs: `wp_nonce_url( $url, 'my_action_name', 'my_nonce_key' )`
  - Forms: `wp_nonce_field( 'my_action_name', 'my_nonce_key' )`
  - JS / AJAX: `wp_create_nonce( 'my_action_name' )`
- **Verifying Nonces**:
  - Form Submit: `check_admin_referer( 'my_action_name', 'my_nonce_key' )`
  - AJAX Request: `check_ajax_referer( 'my_action_name', 'nonce' )`
  - Custom Check: `wp_verify_nonce( $_REQUEST['nonce'], 'my_action_name' )`
  - If verification fails, stop processing immediately and return a `403 Forbidden` or `wp_send_json_error( 'Invalid nonce', 403 )`.

### 2.5 User Authorization & Capability Checking
- **Nonces are NOT Authorization**: Nonces protect against CSRF, but do NOT prove permission.
- Always check explicit capabilities before performing administrative or sensitive operations:
  ```php
  if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( [ 'message' => 'Unauthorized user.' ], 403 );
  }
  ```

### 2.6 Database Query Security (SQL Injection Prevention)
- Never concatenate raw variables directly into SQL queries.
- Always use `$wpdb->prepare()` with explicit placeholders (`%s`, `%d`, `%f`):
  ```php
  $results = $wpdb->get_results(
      $wpdb->prepare(
          "SELECT * FROM {$wpdb->prefix}pixelonwp_event_logs WHERE status = %s AND retry_count < %d",
          $status,
          $max_retries
      )
  );
  ```
- Use `dbDelta()` for custom table creation during plugin activation (`ABSPATH . 'wp-admin/includes/upgrade.php'`).

### 2.7 REST API Security
- Every route registered via `register_rest_route()` MUST define an explicit `permission_callback`:
  ```php
  register_rest_route( 'pixelonwp/v1', '/settings', [
      'methods'             => 'POST',
      'callback'            => [ $this, 'save_settings' ],
      'permission_callback' => function() {
          return current_user_can( 'manage_options' );
      },
  ] );
  ```

---

*Saved into plugin directory for developer reference during PixelOnWP plugin development.*
