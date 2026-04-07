# 04 — PHP Classes & Functions

There are 5 PHP classes in this plugin. Each has a single clear responsibility.

---

## `Forminator_Lead_Dashboard` — Main Plugin Class
**File:** `forminator-lead-dashboard.php`

This is the entry point. It is a **singleton** (only one instance ever exists).

### Why a singleton?
WordPress loads plugins once per request. Using `get_instance()` ensures hooks and menus are only registered once, even if the class is referenced from multiple places.

### Key Methods

| Method | What It Does | Why |
|---|---|---|
| `__construct()` | Registers all WordPress hooks | Hooks must be registered early, before pages render |
| `check_forminator()` | Returns `false` if Forminator is not active | Prevents fatal errors if the dependency is missing |
| `init()` | Loads include files, registers admin menu, AJAX handlers | Runs on `plugins_loaded` to ensure all plugins are available first |
| `add_admin_menu()` | Creates the "Lead Dashboard" menu in WP admin | Uses `add_menu_page()` and `add_submenu_page()` |
| `enqueue_admin_assets()` | Loads CSS, Chart.js, and our JS only on our pages | The `$hook` check prevents loading these assets on every admin page |
| `ajax_get_lead()` | Returns a single lead by exact entry ID | Added to fix the "view button mismatch" bug — see [Changelog](./08-changelog.md) |
| `ajax_get_leads()` | Returns paginated, filtered list of leads | Used by the All Leads table |
| `ajax_update_lead_status()` | Updates status for one lead | Called from the modal and bulk actions |
| `ajax_add_feedback()` | Adds a feedback note to a lead | Called from the feedback form in the modal |
| `ajax_get_feedback()` | Returns all feedback for a lead | Loaded when the modal opens |
| `ajax_delete_feedback()` | Deletes a feedback entry | Sales Admins can only delete their own; Admins can delete any |
| `ajax_get_dashboard_stats()` | Returns stats for charts and cards | Called on dashboard load and date range change |
| `ajax_export_leads()` | Returns CSV data for download | Called from the Export button |

---

## `FLD_Database` — Database Manager
**File:** `includes/class-fld-database.php`

Handles creation and removal of the plugin's custom tables.

| Method | What It Does | Why |
|---|---|---|
| `create_tables()` | Creates `fld_lead_status`, `fld_feedback`, `fld_activity_log` | Called on plugin activation via `register_activation_hook` |
| `get_table($name)` | Returns full prefixed table name | Helper to avoid repeating `$wpdb->prefix . 'fld_'` everywhere |
| `drop_tables()` | Drops all plugin tables and removes options | Called on uninstall to clean up the database completely |

---

## `FLD_Leads` — Lead Query Engine
**File:** `includes/class-fld-leads.php`

The most important class. All lead reading, updating, and exporting happens here.

| Method | What It Does | Why |
|---|---|---|
| `get_leads($args)` | Paginated list of leads with filters (form, status, date, search) | Powers both the All Leads table and the recent leads on the dashboard |
| `get_lead($entry_id)` | Fetches a single lead by exact entry ID with its meta and feedback | Used by the View modal — direct lookup, no ambiguity |
| `get_entry_meta($entry_id)` | Returns all form field values for one entry as a key-value array | Called after each `get_leads()` query to attach field data |
| `update_lead_status($entry_id, $status)` | Upserts a row in `fld_lead_status` | The upsert pattern means you don't need to check beforehand whether a status row exists |
| `get_dashboard_stats($days)` | Counts by status, by day, by form for the dashboard charts | Returns everything the dashboard needs in one call |
| `export_leads_csv($form_id, $status)` | Generates a CSV string of up to 10,000 leads | Uses `php://temp` (in-memory file) to build CSV without writing to disk |
| `log_activity($entry_id, $action, $details)` | Inserts a row in `fld_activity_log` | Called automatically by `update_lead_status()`, `FLD_Feedback::add_feedback()`, and `FLD_Feedback::delete_feedback()` |
| `get_forms()` | Returns list of Forminator form IDs and names | Used to populate the form filter dropdown |
| `get_statuses()` | Returns the array of valid status labels | Single source of truth for status values |

### Why `COALESCE(s.status, 'new')` in the query?

When a lead has never been updated, there is no row in `fld_lead_status` for it. The `LEFT JOIN` returns `NULL` for the status column. `COALESCE` turns that `NULL` into `'new'` so every lead always has a status.

---

## `FLD_Feedback` — Feedback Manager
**File:** `includes/class-fld-feedback.php`

Handles all CRUD for feedback entries.

| Method | What It Does | Why |
|---|---|---|
| `add_feedback($args)` | Inserts a feedback row and logs the activity | Sanitizes all inputs before inserting |
| `get_feedback($entry_id)` | Returns all feedback for a lead, joined with the user's display name | `LEFT JOIN wp_users` so we show "Added by John" instead of just a user ID |
| `get_feedback_count($entry_id)` | Returns the count of feedback entries | Used in the leads table to show the feedback indicator |
| `get_feedback_owner($feedback_id)` | Returns the `user_id` who wrote a feedback entry | Used to enforce the rule: Sales Admins can only delete their own feedback |
| `delete_feedback($feedback_id)` | Deletes a feedback row and logs the activity | Fetches `entry_id` before deleting so the activity log can reference it |
| `update_feedback($feedback_id, $args)` | Updates text/rating of an existing feedback | Only updates fields that are actually passed in |
| `get_ratings()` | Returns the three valid rating values with label, icon, color | Single source of truth for rating options |
| `get_stats($days)` | Returns count of feedback by rating for a date range | Useful for reporting |

---

## `FLD_Roles` — Roles & Capabilities
**File:** `includes/class-fld-roles.php`

Manages who can access the plugin.

| Method | What It Does | Why |
|---|---|---|
| `setup()` | Creates the `sales_admin` role and adds `fld_manage_leads` cap to `administrator` | Runs on every `plugins_loaded` so the role survives database restores |
| `teardown()` | Removes the role and capability | Called on plugin deactivation to clean up |
| `can_access()` | Returns `true` if current user has `fld_manage_leads` cap | Used as the permission gate in every AJAX handler |
| `is_admin()` | Returns `true` if current user has `manage_options` (full admin) | Used to differentiate between full admin and Sales Admin |
| `assign($user_id)` | Gives a user the `sales_admin` role | Refuses to modify administrators |
| `remove($user_id)` | Reverts a Sales Admin back to `subscriber` | Called from the Settings page |
| `get_sales_admins()` | Returns all users with the `sales_admin` role | Used to render the user list in Settings |
