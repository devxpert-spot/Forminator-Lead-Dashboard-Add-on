# 05 — AJAX Actions

All AJAX communication between the browser and WordPress goes through `wp-admin/admin-ajax.php`. Every action requires:

1. A valid **nonce** (`fld_nonce`) — prevents CSRF attacks
2. The user must have the `fld_manage_leads` capability — prevents unauthorized access

The nonce is generated in PHP and passed to JavaScript via `wp_localize_script()` as `fld_ajax.nonce`.

---

## Complete Action Reference

| Action | PHP Handler | JS Caller | Purpose |
|---|---|---|---|
| `fld_get_lead` | `ajax_get_lead()` | `openLeadModal()` | Fetch one lead by exact entry ID for the View modal |
| `fld_get_leads` | `ajax_get_leads()` | `loadLeads()`, `loadRecentLeads()` | Fetch paginated, filtered lead list |
| `fld_update_lead_status` | `ajax_update_lead_status()` | `saveLeadStatus()`, `applyBulkAction()` | Change status of one lead |
| `fld_add_feedback` | `ajax_add_feedback()` | `submitFeedback()` | Add a note/feedback to a lead |
| `fld_get_feedback` | `ajax_get_feedback()` | `loadFeedback()` | Load all feedback for a lead |
| `fld_delete_feedback` | `ajax_delete_feedback()` | `deleteFeedback()` | Delete one feedback entry |
| `fld_get_dashboard_stats` | `ajax_get_dashboard_stats()` | `loadDashboardStats()` | Load stats/charts for dashboard |
| `fld_export_leads` | `ajax_export_leads()` | `exportLeads()` | Generate CSV for download |
| `fld_get_assignable_users` | `ajax_get_assignable_users()` | Settings page | List users that can become Sales Admins |
| `fld_assign_sales_admin` | `ajax_assign_sales_admin()` | Settings page | Grant Sales Admin role to a user |
| `fld_remove_sales_admin` | `ajax_remove_sales_admin()` | Settings page | Remove Sales Admin role from a user |

---

## Request / Response Format

All responses use WordPress's standard `wp_send_json_success()` / `wp_send_json_error()`:

```json
// Success
{ "success": true, "data": { ... } }

// Error
{ "success": false, "data": "Error message" }
```

JavaScript always checks `response.success` before using `response.data`.

---

## `fld_get_lead` — Detailed

**POST params:**
| Param | Type | Description |
|---|---|---|
| `nonce` | string | Security nonce |
| `entry_id` | int | The Forminator entry ID to fetch |

**Response `data`:**
```json
{
  "entry_id": 42,
  "form_id": 3,
  "date_created": "2024-11-01 10:30:00",
  "status": "positive",
  "assigned_to": null,
  "priority": "normal",
  "source": "",
  "meta": { "name-1": "John Doe", "email-1": "john@example.com" },
  "feedback": [ ... ]
}
```

---

## `fld_get_leads` — Detailed

**POST params:**
| Param | Type | Description |
|---|---|---|
| `nonce` | string | Security nonce |
| `form_id` | int | Filter by form (0 = all) |
| `status` | string | Filter by status (empty = all) |
| `date_from` | string | `YYYY-MM-DD` start date |
| `date_to` | string | `YYYY-MM-DD` end date |
| `search` | string | Text search across form field values |
| `page` | int | Page number for pagination |
| `per_page` | int | Results per page |

**Response `data`:**
```json
{
  "leads": [ ... ],
  "total": 87,
  "pages": 5,
  "current_page": 1
}
```

---

## Security Notes

- `check_ajax_referer('fld_nonce', 'nonce')` — verifies the nonce and dies if invalid
- `FLD_Roles::can_access()` — verifies capability, returns JSON error if unauthorized
- All POST inputs are sanitized: `intval()`, `sanitize_text_field()`, `sanitize_textarea_field()`
- All DB queries use `$wpdb->prepare()` with placeholders — prevents SQL injection
