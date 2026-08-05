# PixelOnWP — Plugin Development & Security Rules

> **Version**: 1.0.0  
> **Target Platform**: WordPress 6.0+, PHP 8.0+  
> **Compliance Standards**: Official WordPress Plugin Developer Handbook & Security APIs

---

## 1. Cardinal Architectural Rules

### Rule 1.1: Core Integrity
* **Never Modify WordPress Core**: All custom logic, overrides, and features must reside strictly inside `wp-content/plugins/pixel-on-wp/`.
* **Direct Access Prevention**: Every PHP file in the codebase **MUST** include a direct access check at line 1:
  ```php
  if ( ! defined( 'ABSPATH' ) ) {
      exit; // Exit if accessed directly.
  }
  ```

### Rule 1.2: Namespacing & Global Scope Protection
* All PHP classes, traits, interfaces, and functions MUST reside within the `PixelOnWP` namespace hierarchy (e.g., `PixelOnWP\Includes\Tracking`, `PixelOnWP\Admin`).
* Global options, constants, hooks, AJAX actions, database table names, and CSS classes MUST be uniquely prefixed with `pixelonwp_` or `PixelOnWP_` (e.g., `PixelOnWP_VERSION`, `wp_pixelonwp_event_logs`).

### Rule 1.3: Design Patterns & Bootstrapping
* **Singleton Pattern**: The main plugin class `PixelOnWP_Main` must remain `final` with a `private __construct()`, `private __clone()`, and `__wakeup()` that throws an exception.
* **Hook Loader Pattern**: Never attach `add_action()` or `add_filter()` calls arbitrarily in construct methods. Register all hooks cleanly via `$loader->add_action()` and `$loader->add_filter()` through `PixelOnWP_Loader`.
* **Dependency Injection**: Use `PixelOnWP_Container` for instantiating service classes and managing component lifetimes.

---

## 2. Security Engineering Rules

### Rule 2.1: Input Handling (Sanitize Early)
Treat **ALL** incoming data (`$_POST`, `$_GET`, `$_COOKIE`, `$_SERVER`, REST payloads, 3rd party APIs) as untrusted.

| Data Type | Mandatory Sanitization Function | Example Usage |
| :--- | :--- | :--- |
| Single-Line Text / Input | `sanitize_text_field()` | `$clean_name = sanitize_text_field( $_POST['user_name'] );` |
| Multi-Line Text / Textarea | `sanitize_textarea_field()` | `$clean_bio = sanitize_textarea_field( $_POST['bio'] );` |
| Email Addresses | `sanitize_email()` | `$clean_email = sanitize_email( $_POST['email'] );` |
| Keys / Slugs / Identifiers | `sanitize_key()`, `sanitize_title()` | `$clean_key = sanitize_key( $_POST['setting_key'] );` |
| Raw URLs | `esc_url_raw()` | `$clean_url = esc_url_raw( $_POST['webhook_url'] );` |
| Arrays | `array_map( 'sanitize_text_field', $arr )` | Recursive sanitization for arrays. |

### Rule 2.2: Output Presentation (Escape Late)
Escape data at the **exact moment** of rendering HTML output.

| Context | Mandatory Escaping Function | Example Usage |
| :--- | :--- | :--- |
| Plain HTML Text Nodes | `esc_html()` / `esc_html_e()` | `<span><?php echo esc_html( $text ); ?></span>` |
| HTML Attributes | `esc_attr()` / `esc_attr_e()` | `<input value="<?php echo esc_attr( $val ); ?>" />` |
| URLs (`href`, `src`, `action`) | `esc_url()` | `<a href="<?php echo esc_url( $link ); ?>">` |
| Safe HTML Content | `wp_kses_post()` / `wp_kses()` | `<div><?php echo wp_kses_post( $html ); ?></div>` |
| JavaScript Inline Data | `wp_json_encode()` | `const data = <?php echo wp_json_encode( $arr ); ?>;` |

### Rule 2.3: CSRF Protection & Nonces
* **Generating Nonces**:
  * Form hidden field: `wp_nonce_field( 'pixelonwp_save_settings', 'pixelonwp_nonce' );`
  * URL query parameter: `wp_nonce_url( $url, 'pixelonwp_action' );`
  * JS AJAX variable: `wp_create_nonce( 'PixelOnWP_nonce' );`
* **Verifying Nonces**:
  * AJAX Handlers: `check_ajax_referer( 'PixelOnWP_nonce', 'nonce' );`
  * Form Processing: `check_admin_referer( 'pixelonwp_save_settings', 'pixelonwp_nonce' );`
  * Generic Validation: `if ( ! wp_verify_nonce( $nonce, 'action' ) ) { exit; }`

### Rule 2.4: Authorization & User Capabilities
* Nonces prevent CSRF but **DO NOT** prove user permission.
* Always explicitly check user capability before executing admin actions or returning sensitive data:
  ```php
  if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( [ 'message' => 'Unauthorized user.' ], 403 );
  }
  ```

### Rule 2.5: SQL Injection Prevention
* **NEVER** concatenate variables directly into SQL queries.
* **ALWAYS** use `$wpdb->prepare()` with explicit placeholders (`%s`, `%d`, `%f`):
  ```php
  $results = $wpdb->get_results(
      $wpdb->prepare(
          "SELECT * FROM {$wpdb->prefix}pixelonwp_event_logs WHERE status = %s AND retry_count < %d",
          $status,
          $max_retries
      )
  );
  ```

### Rule 2.6: REST API Endpoint Protection
* Every endpoint registered via `register_rest_route()` MUST define an explicit `permission_callback`:
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

## 3. Code Style & Quality Standards

### Rule 3.1: Strict Typing & Function Signatures
* Enable strict type checking and declare return types on all class methods:
  ```php
  public function track_event( string $event_name, array $payload ): bool
  ```

### Rule 3.2: Comprehensive PHPDoc Comments
* Every file, class, method, property, and hook MUST have full PHPDoc blocks specifying `@package`, `@since`, `@param`, and `@return`.

### Rule 3.3: Error Handling & Logging
* Never swallow exceptions silently or suppress PHP errors with `@`.
* Catch exceptions explicitly and log API failures to `wp_pixelonwp_event_logs` using `PixelOnWP_Logger`.

---

## 4. UI/UX Design System Rules

* **Single Page Application (SPA)**: Admin UI pages must be integrated into `#wpt-admin-app` using ES Modules (`type="module"`).
* **Design System Tokens**: Use CSS variables defined in [assets/css/admin-global.css](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/assets/css/admin-global.css) (`--pp-primary`, `--pp-surface`, `--pp-text-heading`, `--pp-radius`, `--pp-shadow`).
* **Front-end Isolation**: Front-end interactive widgets (such as the Visual Builder) MUST render inside an isolated **Shadow DOM** (`shadowRoot`) to avoid CSS conflicts with active WordPress themes.

---

## 5. Verification Checklist Before Commit

1. **Syntax Check**: Code passes `php -l <filename>`.
2. **Direct Access Check**: Top of file contains `defined('ABSPATH') || exit;`.
3. **Sanitization Audit**: Every parameter extracted from `$_POST`, `$_GET`, or REST payload is sanitized.
4. **Escaping Audit**: Every `echo` statement contains `esc_html()`, `esc_attr()`, `esc_url()`, or `wp_kses_post()`.
5. **Nonce & Permission Check**: Admin actions verify both `check_ajax_referer()` and `current_user_can('manage_options')`.
6. **SQL Audit**: All custom `$wpdb` calls use `$wpdb->prepare()`.

---

*Mandatory development rules for all PixelOnWP maintainers and AI assistants.*
