# 06 — JavaScript Functions

**File:** `assets/js/admin-scripts.js`

All JS is wrapped in an IIFE `(function($) { ... })(jQuery)` so it doesn't pollute the global scope and is safe to use the `$` alias for jQuery even when other plugins use `$` for something else.

---

## Global State Variables

```js
let currentLeadId = null;   // Entry ID of the lead currently open in the modal
let currentPage   = 1;      // Current pagination page on the Leads table
let leadsChart    = null;   // Chart.js instance for the leads-over-time line chart
let statusChart   = null;   // Chart.js instance for the status doughnut chart
```

These are kept at module scope (not inside any function) so multiple functions can share and update them.

---

## Initialization

| Function | Trigger | Purpose |
|---|---|---|
| `initDashboard()` | `$(document).ready` when `.fld-dashboard` exists | Sets up dashboard page: loads stats, attaches date range and refresh handlers |
| `initLeadsPage()` | `$(document).ready` when `.fld-leads-page` exists | Sets up leads page: loads leads, attaches filter/search/export/bulk handlers |
| `initModalHandlers()` | `$(document).ready` always | Attaches event listeners for the View modal (open, close, save status, feedback) |

**Why check for `.fld-dashboard` / `.fld-leads-page`?**
Both pages share the same JS file. The check ensures only the relevant code runs on each page, avoiding errors from missing DOM elements.

---

## Dashboard Functions

### `loadDashboardStats()`
Calls `fld_get_dashboard_stats` AJAX action with the selected date range. On success calls `updateStatsCards()`, `updateCharts()`, and `updateFormsTable()`.

### `updateStatsCards(data)`
Sets text content of the 5 stat counter elements (`#stat-total-leads`, `#stat-new-leads`, etc.).

### `updateCharts(data)`
- Destroys any existing Chart.js instances first (avoids memory leaks and double-rendering)
- Builds a **line chart** (`fld-leads-chart`) of leads per day
- Builds a **doughnut chart** (`fld-status-chart`) of leads by status

### `loadRecentLeads()`
Fetches the latest 10 leads via `fld_get_leads` and calls `renderRecentLeads()`.

### `renderRecentLeads(leads)`
Builds the recent leads table rows on the dashboard. Each row has a "View" button with `data-id` set to the `entry_id`.

---

## Leads Page Functions

### `loadLeads()`
Reads all filter field values and calls `fld_get_leads`. On success calls `renderLeadsTable()` and `renderPagination()`.

### `renderLeadsTable(leads)`
Builds the full leads table. For each lead it calls `findMetaValue()` to extract name/email/phone from the form meta for the Contact column.

### `findMetaValue(meta, keys)`
Tries each key in `keys` array against the meta object. Returns the first match. Used because different forms may name their fields `email`, `email-1`, `email_address`, etc.

```js
// Example
findMetaValue(lead.meta, ['email', 'email-1', 'your-email'])
// Returns the first matching value found
```

### `renderPagination(data)`
Builds pagination buttons from `data.pages` and `currentPage`. Shows at most 5 page numbers at a time with `...` ellipsis for large ranges.

### `updateSelectedCount()`
Updates the "X selected" counter when checkboxes are ticked.

### `applyBulkAction()`
Reads the selected leads and chosen action, then fires one `fld_update_lead_status` AJAX call per selected lead. Shows a success notice after all requests complete.

### `exportLeads()`
Calls `fld_export_leads` and passes the result to `downloadCSV()`.

### `downloadCSV(csv, filename)`
Creates a temporary `<a>` element with a Blob URL, programmatically clicks it to trigger the browser's download, then removes the element.

---

## Modal Functions

### `openLeadModal(entryId)` ← **This was the buggy function**
Opens the lead detail modal for a specific lead.

**Before fix:** Called `fld_get_leads` with `search: entryId` — a text search that could return the wrong lead (e.g. searching for "5" matched entries 5, 15, 25...).

**After fix:** Calls the dedicated `fld_get_lead` action with `entry_id: entryId` — a direct database lookup by primary key. Always returns exactly the right lead.

### `renderLeadModal(lead)`
Populates the modal with lead data:
- Sets the lead ID in the modal header
- Loops over all `lead.meta` key-value pairs and renders them as labeled rows using `formatFieldName()`
- Checks the correct status radio button

### `loadFeedback(entryId)`
Calls `fld_get_feedback` and passes results to `renderFeedback()`.

### `renderFeedback(feedbackList)`
Renders each feedback entry with its rating icon, text, author name, date, and delete button.

### `saveLeadStatus()`
Reads the checked status radio and calls `fld_update_lead_status`. On success, also updates the status badge in the background table row (without reloading the page).

### `submitFeedback()`
Validates the feedback text field is not empty, then calls `fld_add_feedback`. On success clears the input and reloads feedback via `loadFeedback()`.

### `deleteFeedback(feedbackId)`
Shows a confirm dialog, then calls `fld_delete_feedback`. On success reloads feedback.

### `closeModal()`
Hides the modal and resets `currentLeadId = null`.

---

## Utility Functions

| Function | Purpose |
|---|---|
| `showLoading(show)` | Shows/hides the `#fld-loading` spinner |
| `showNotice(type, message)` | Appends a floating notification (`success`/`error`/`warning`) that auto-hides after 3 seconds |
| `formatStatus(status)` | Converts internal status slugs (`follow_up`) to display labels (`Follow Up`) |
| `formatFieldName(key)` | Converts form field keys (`email-1`, `phone_number`) to readable labels (`Email 1`, `Phone Number`) |
| `escapeHtml(text)` | Prevents XSS by escaping user content before inserting into the DOM. Uses `div.textContent` trick — the safest method in vanilla JS |

### Why `escapeHtml` Uses `div.textContent`?

```js
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;   // browser handles escaping automatically
    return div.innerHTML;     // returns the safely escaped string
}
```

This is safer than a manual regex replace because it lets the browser's own HTML parser handle all edge cases.
