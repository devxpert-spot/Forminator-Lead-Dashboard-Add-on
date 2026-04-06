# 01 — Project Overview

## What This Plugin Does

The **Forminator Lead Dashboard** is a WordPress admin addon built on top of the [Forminator](https://wpmudev.com/project/forminator-pro/) form builder plugin.

When a visitor fills in a Forminator form on your website, the submission is stored as an **Entry** in Forminator's database. This plugin reads those entries and layers a **lead management system** on top of them, allowing your sales team to:

- View all form submissions (leads) in one place
- Assign a status to each lead (New, Positive, Negative, Follow Up, Converted, Closed)
- Add internal feedback/notes with a rating (👍 Positive, 😐 Neutral, 👎 Negative)
- Export leads to CSV
- See summary stats and charts on a dashboard
- Have a restricted "Sales Admin" login that can only see the Lead Dashboard

---

## How It Works — High-Level Flow

```
Visitor fills Forminator form
        │
        ▼
Forminator stores entry in:
  wp_frmt_form_entry        ← one row per submission
  wp_frmt_form_entry_meta   ← field values (name, email, phone …)
        │
        ▼
This plugin reads those tables and adds its own:
  wp_fld_lead_status        ← status, priority, assigned_to per entry
  wp_fld_feedback           ← internal notes added by sales team
  wp_fld_activity_log       ← audit trail of every change
        │
        ▼
WordPress Admin → Lead Dashboard menu
  ┌─────────────────┐
  │   Dashboard     │  Stats cards + charts + recent leads
  ├─────────────────┤
  │   All Leads     │  Filterable table + View modal
  ├─────────────────┤
  │   Settings      │  Manage Sales Admin user accounts
  └─────────────────┘
```

---

## Dependency on Forminator

The plugin checks `class_exists('Forminator')` on every load. If Forminator is not active, a wp-admin notice is shown and all plugin features are disabled. This prevents fatal errors from missing database tables.
