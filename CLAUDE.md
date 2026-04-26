# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Setup

This is a standard WordPress plugin — no build system, no npm, no Composer. PHP files are served directly by WordPress; CSS and JS are plain files enqueued via `wp_enqueue_*`.

**To develop locally:**
1. Place this directory under `wp-content/plugins/` of a WordPress installation.
2. Install and activate the [Forminator](https://wpmudev.com/project/forminator-pro/) plugin (free version works).
3. Activate **Forminator Lead Dashboard** from the WordPress Plugins screen.
4. On first activation, `FLD_Database::create_tables()` creates the three custom DB tables automatically.

**To test changes:**
- PHP: refresh the relevant WP admin page — no compilation step.
- JS/CSS: hard-refresh the browser (Ctrl+Shift+R) or bump `FLD_VERSION` in `forminator-lead-dashboard.php` to bust the cache.
- Database schema changes: deactivate and reactivate the plugin (triggers `activate()` → `FLD_Database::create_tables()` via `dbDelta`).

There is no test suite or linter configured.

## Architecture

### Entry Point & Request Lifecycle

`forminator-lead-dashboard.php` bootstraps everything through the `Forminator_Lead_Dashboard` singleton. The initialization order is:

1. `plugins_loaded` (priority 5) — `FLD_Roles::setup()` registers the `sales_admin` role and grants the `fld_manage_leads` cap to administrators. This runs before everything else so capability checks never fail due to race conditions.
2. `plugins_loaded` (default) — checks Forminator is active, then calls `includes()` to load the four class files, registers admin menus, enqueues assets, and registers all AJAX handlers.
3. `register_activation_hook` — creates DB tables and sets defaults.

### PHP Classes

| Class | File | Responsibility |
|---|---|---|
| `Forminator_Lead_Dashboard` | `forminator-lead-dashboard.php` | Singleton; hooks, menus, all AJAX handlers |
| `FLD_Roles` | `includes/class-fld-roles.php` | Role/cap lifecycle; auth helpers |
| `FLD_Database` | `includes/class-fld-database.php` | Table creation, schema, drop |
| `FLD_Leads` | `includes/class-fld-leads.php` | All lead queries, stats, CSV export, activity log |
| `FLD_Feedback` | `includes/class-fld-feedback.php` | Feedback CRUD |

### Database Layer

The plugin owns three tables (all prefixed `{$wpdb->prefix}fld_`):

- `fld_lead_status` — one row per Forminator entry; stores `status`, `assigned_to`, `priority`, `source`. Status defaults to `'new'` (NULL in this table = new).
- `fld_feedback` — many rows per entry; stores feedback text, rating (`positive`/`neutral`/`negative`), and `user_id`.
- `fld_activity_log` — append-only log of every status change and feedback action.

Lead data itself lives in Forminator's own tables: `frmt_form_entry` (one row per submission) and `frmt_form_entry_meta` (EAV key/value pairs for form fields). `FLD_Leads::get_leads()` joins these with `fld_lead_status` via `LEFT JOIN` so that entries with no status row still appear as `'new'`.

### AJAX Pattern

All browser ↔ server communication goes through `wp-admin/admin-ajax.php`. Every request must include:
- `nonce` — verified with `check_ajax_referer('fld_nonce', 'nonce')`
- Capability — checked with `FLD_Roles::can_access()` (or `FLD_Roles::is_admin()` for role-management actions)

The nonce is injected into JS via `wp_localize_script()` as `fld_ajax.nonce`. All responses use `wp_send_json_success()` / `wp_send_json_error()`, so JS always checks `response.success`.

Valid lead statuses (enforced by PHP allowlist): `new`, `positive`, `negative`, `follow_up`, `converted`, `closed`.

### Role & Access Control

Two access levels:

- **Administrator** (`manage_options`) — full access including Settings page and role management.
- **Sales Admin** (`sales_admin` role, `fld_manage_leads` cap) — restricted to the Lead Dashboard and All Leads pages only. On login they are redirected directly to the dashboard (handled via `login_redirect`, `wp_login`, and `woocommerce_login_redirect` filters). All other WP admin pages redirect back to the dashboard (`restrict_sales_admin_access` on `admin_init`). Their admin toolbar is stripped down to essential items.

`FLD_Roles::teardown()` removes the role and cap on plugin deactivation. `FLD_Roles::setup()` re-registers them on every `plugins_loaded` so they survive database restores.

### JavaScript

`assets/js/admin-scripts.js` is a single IIFE (jQuery). It detects the current page by DOM presence (`.fld-dashboard` vs `.fld-leads-page`) and initializes accordingly. Module-level state:
- `currentLeadId` — entry ID currently open in the modal
- `currentPage` — pagination state for the leads list
- `leadsChart` / `statusChart` — Chart.js instances (destroyed and recreated on each data refresh)

The lead detail modal is rendered client-side from the `fld_get_lead` AJAX response. Contact info extraction from form meta uses a key-fallback list (`['email', 'email-1']`, etc.) since Forminator field names vary by form.
