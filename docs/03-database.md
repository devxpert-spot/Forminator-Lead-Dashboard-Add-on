# 03 — Database Design

## Tables We Read (Forminator's own tables)

These tables are created by Forminator. We only **read** them, never write.

| Table | Purpose |
|---|---|
| `wp_frmt_form_entry` | One row per form submission. Columns: `entry_id`, `form_id`, `entry_type`, `date_created` |
| `wp_frmt_form_entry_meta` | Key-value pairs of field values. Columns: `entry_id`, `meta_key`, `meta_value` |

---

## Tables We Own (created by this plugin)

All our tables use the `fld_` prefix. They are created on plugin activation by `FLD_Database::create_tables()`.

---

### `wp_fld_lead_status`

Tracks the CRM status of each Forminator entry.

```sql
CREATE TABLE wp_fld_lead_status (
    id          bigint(20) AUTO_INCREMENT PRIMARY KEY,
    entry_id    bigint(20) NOT NULL UNIQUE,   -- FK → frmt_form_entry.entry_id
    form_id     bigint(20) NOT NULL,
    status      varchar(50) DEFAULT 'new',    -- new|positive|negative|follow_up|converted|closed
    assigned_to bigint(20) DEFAULT NULL,      -- WP user ID
    priority    varchar(20) DEFAULT 'normal',
    source      varchar(100) DEFAULT '',
    created_at  datetime,
    updated_at  datetime
);
```

**Why a separate table instead of modifying Forminator's tables?**
We never touch Forminator's data. Adding our own table means the plugin can be deactivated cleanly without corrupting Forminator entries.

**Why `UNIQUE KEY entry_id`?**
Each entry can only have one status row. The `update_lead_status()` method does an upsert: it checks if a row exists and either `INSERT`s or `UPDATE`s.

---

### `wp_fld_feedback`

Stores internal notes added by the sales team for any lead.

```sql
CREATE TABLE wp_fld_feedback (
    id         bigint(20) AUTO_INCREMENT PRIMARY KEY,
    entry_id   bigint(20) NOT NULL,   -- which lead this feedback belongs to
    user_id    bigint(20) NOT NULL,   -- who wrote it
    feedback   text NOT NULL,
    rating     varchar(20) DEFAULT 'neutral',  -- positive|neutral|negative
    created_at datetime
);
```

**Why a separate feedback table?**
One lead can have many feedback entries from different team members over time — a one-to-many relationship that must be its own table.

---

### `wp_fld_activity_log`

Audit trail. Every status change and feedback action is recorded here.

```sql
CREATE TABLE wp_fld_activity_log (
    id         bigint(20) AUTO_INCREMENT PRIMARY KEY,
    entry_id   bigint(20) NOT NULL,
    user_id    bigint(20) NOT NULL,
    action     varchar(100) NOT NULL,  -- 'status_change' | 'feedback_added' | 'feedback_deleted'
    details    text,                   -- JSON: { new_status, feedback_id, ... }
    created_at datetime
);
```

---

## How Tables Join Together

```
frmt_form_entry                 fld_lead_status
───────────────                 ───────────────
entry_id  ◄──── JOINED ON ────► entry_id
form_id                         status
date_created                    assigned_to
                                priority

frmt_form_entry_meta
────────────────────
entry_id   ◄──── one-to-many (name, email, phone, etc.)
meta_key
meta_value
```

The main `get_leads()` query does a `LEFT JOIN` so that entries with no status row yet are still returned with status = `'new'` (via `COALESCE(s.status, 'new')`).

---

## `dbDelta()` — Why We Use It

`dbDelta()` is a WordPress function that creates a table if it doesn't exist, or safely adds missing columns if it does. This means the plugin can be updated without needing a manual migration step.
