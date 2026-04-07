# 07 — Roles & Permissions

The plugin introduces a two-tier access model on top of WordPress's native role system.

---

## Roles

| Role | WordPress Slug | Who They Are | What They Can Do |
|---|---|---|---|
| **Administrator** | `administrator` | Site owner / developer | Everything — all WP admin + all Lead Dashboard features including Settings |
| **Sales Admin** | `sales_admin` | Sales team members | Only the Lead Dashboard (Dashboard + All Leads). Cannot access any other WP admin page |

---

## Custom Capability

The plugin registers one custom capability: **`fld_manage_leads`**

- The `sales_admin` role has this capability
- The `administrator` role also gets this capability added (on `setup()`)
- All AJAX handlers check `FLD_Roles::can_access()` which calls `current_user_can('fld_manage_leads')`

Using a custom capability (rather than checking the role directly) is the WordPress best practice — it means the capability can be granted to other roles in future without code changes.

---

## How Sales Admin Restrictions Work

### Menu Restriction (`restrict_sales_admin_menu`)
Runs at `admin_menu` priority 999 (after all menus are registered). Loops through `$menu` global and calls `remove_menu_page()` for everything except `lead-dashboard`.

### Page Access Restriction (`restrict_sales_admin_access`)
Runs on `admin_init`. If the user is a Sales Admin and the current page is not in `['lead-dashboard', 'lead-dashboard-leads']`, they are redirected to the Lead Dashboard. AJAX calls are always allowed through.

### Toolbar Cleanup (`restrict_sales_admin_toolbar`)
Removes unnecessary items from the WP admin bar (WP logo, updates, comments, new content, etc.) so the Sales Admin sees a clean interface.

### Login Redirect
Three hooks cover different login scenarios:
- `login_redirect` — standard `wp-login.php` login
- `wp_login` — any custom login form
- `woocommerce_login_redirect` — WooCommerce My Account login

All three redirect Sales Admins directly to the Lead Dashboard instead of the standard WP Dashboard.

---

## Feedback Deletion Permission

Admins can delete any feedback. Sales Admins can only delete their own feedback.

This is enforced in `ajax_delete_feedback()`:

```php
if (!FLD_Roles::is_admin()) {
    $owner = FLD_Feedback::get_feedback_owner($feedback_id);
    if ($owner !== get_current_user_id()) {
        wp_send_json_error('You can only delete your own feedback');
    }
}
```

---

## Managing Sales Admin Users

Done from **Lead Dashboard → Settings** (visible to Administrators only).

The Settings page calls:
- `fld_get_assignable_users` — lists all non-admin WP users and flags which ones are already Sales Admins
- `fld_assign_sales_admin` — calls `FLD_Roles::assign($user_id)` which calls `$user->set_role('sales_admin')`
- `fld_remove_sales_admin` — calls `FLD_Roles::remove($user_id)` which calls `$user->set_role('subscriber')`

---

## Why `setup()` Runs on Every `plugins_loaded`

```php
add_action('plugins_loaded', array($this, 'setup_roles'), 5);
```

WordPress roles are stored in the database. If the database is restored from a backup, the `sales_admin` role may be lost. Running `setup()` on every load ensures the role and capability always exist, not just on first activation.
