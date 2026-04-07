# 08 — Bug Fixes & Changelog

---

## v1.0.1 — View Button Mismatch Fix

### Bug: Wrong lead data shown in View modal

**Symptom:** Clicking "View" on a lead in the dashboard showed data from a different lead.

**Root Cause:**

The `openLeadModal()` function in `admin-scripts.js` was fetching lead data using a **text search** instead of a direct ID lookup:

```js
// BEFORE (buggy)
$.ajax({
    data: {
        action: 'fld_get_leads',
        search: entryId,   // ← searched meta_value LIKE '%5%'
        per_page: 1
    }
});
const lead = response.data.leads[0];  // ← took the FIRST result, wrong lead!
```

The `search` parameter does a `LIKE '%entryId%'` query on form meta values. Searching for entry ID `5` would also match entries `15`, `25`, `50`, etc. The first result returned was not necessarily the correct lead.

**Fix:**

1. Added a dedicated `fld_get_lead` AJAX action in PHP that calls `FLD_Leads::get_lead($entry_id)` — an exact `WHERE entry_id = %d` lookup.

2. Updated `openLeadModal()` to call the new action:

```js
// AFTER (fixed)
$.ajax({
    data: {
        action: 'fld_get_lead',
        entry_id: entryId   // ← direct lookup by primary key
    }
});
renderLeadModal(response.data);  // ← always the exact right lead
```

**Files Changed:**
- `forminator-lead-dashboard.php` — added `wp_ajax_fld_get_lead` hook and `ajax_get_lead()` method
- `assets/js/admin-scripts.js` — updated `openLeadModal()` function

---

## v1.0.0 — Initial Release

- Lead Dashboard with stats cards and charts
- All Leads table with filters, search, and pagination
- Lead View modal with status management and feedback
- CSV export
- Sales Admin role with restricted WP admin access
- Activity log for all status and feedback changes
