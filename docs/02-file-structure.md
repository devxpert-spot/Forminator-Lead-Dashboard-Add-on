# 02 — File & Folder Structure

```
forminator-lead-dashboard/
│
├── forminator-lead-dashboard.php   ← Main plugin file (entry point)
│
├── includes/
│   ├── class-fld-database.php      ← Creates / drops DB tables
│   ├── class-fld-leads.php         ← Queries leads, exports CSV, logs activity
│   ├── class-fld-feedback.php      ← CRUD for per-lead feedback/notes
│   └── class-fld-roles.php         ← Custom role & capability management
│
├── templates/
│   ├── dashboard.php               ← Dashboard page HTML (stats + charts)
│   ├── leads.php                   ← All Leads page HTML (table + modal)
│   └── settings.php                ← Settings page HTML (Sales Admin users)
│
├── assets/
│   ├── css/
│   │   └── admin-styles.css        ← All admin UI styles
│   └── js/
│       └── admin-scripts.js        ← All admin UI interactions (jQuery + Chart.js)
│
├── docs/                           ← This documentation folder
│
└── README.md
```

---

## Why This Structure?

| Decision | Reason |
|---|---|
| Single main `.php` file as entry point | WordPress convention — plugin is identified by the header comment in this file |
| `includes/` for class files | Keeps logic separated from presentation; each class has one responsibility |
| `templates/` for HTML | Separates HTML output from business logic; templates are included by the main class |
| `assets/` for CSS & JS | WordPress convention; assets are enqueued via `wp_enqueue_style` / `wp_enqueue_script` |
| Static class methods everywhere | Simple to call from AJAX handlers without needing to instantiate objects |
