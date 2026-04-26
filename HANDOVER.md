# Forminator Lead Dashboard — Handover Document

**Plugin:** Forminator Lead Dashboard by DevXpert  
**Version:** 1.0.1  
**Author:** Anup Kankale  
**Requires:** WordPress 5.0+, PHP 7.4+, Forminator plugin (free version)  
**Branch:** `main` — all OTP feature files are **uncommitted** (run `git status` before resuming)

---

## What Was Built in This Session

A complete **Email OTP spam-prevention feature** using Brevo SMTP was added to the plugin.

### New Files
| File | Purpose |
|---|---|
| `includes/class-fld-otp.php` | OTP logic: rate limiting, 6-digit code generation, transient storage, Brevo SMTP send via `wp_mail()` + `phpmailer_init`, one-time token issue / verify / consume |
| `assets/js/fld-otp.js` | Front-end widget injected into Forminator forms; 4-state machine (idle → sending → awaiting_code → verified); cookie-based token transport |
| `assets/css/fld-otp.css` | Scoped styles for the OTP widget |

### Modified Files
| File | What Changed |
|---|---|
| `forminator-lead-dashboard.php` | `require_once` for OTP class; 4 public AJAX hooks; `wp_enqueue_scripts`; `enqueue_public_assets()`; `ajax_send_otp()`; `ajax_verify_otp()`; `check_otp_on_submit()` Forminator filter; `FLD_OTP::init_defaults()` call |
| `templates/settings.php` | New **Spam Prevention — Email OTP** section with SMTP fields and per-form checkboxes |

---

## Brevo SMTP Credentials

Already saved to `wp_options` via `FLD_OTP::init_defaults()` (uses `add_option` — safe to re-run, won't overwrite).

| wp_option key | Value |
|---|---|
| `fld_smtp_host` | smtp-relay.brevo.com |
| `fld_smtp_port` | 587 |
| `fld_smtp_username` | a8a8c5001@smtp-brevo.com |
| `fld_smtp_password` | *(saved in DB — do not re-enter unless rotating)* |
| `fld_smtp_encryption` | tls |
| `fld_brevo_sender_name` | Trendzy Tours |
| `fld_brevo_sender_email` | tradestrome@gmail.com |

> **Note:** `tradestrome@gmail.com` must be added as a **verified sender** in Brevo (Senders & IPs → Senders) or emails will not be delivered.

---

## How to Enable OTP on a Form

1. WP Admin → **Lead Dashboard → Settings**
2. Scroll to **Spam Prevention — Email OTP**
3. Confirm SMTP fields are pre-filled correctly
4. Tick the checkbox next to the form(s) you want to protect
5. Click **Save Settings**
6. Visit the page with that form — the **"Send Verification Code"** widget should appear above the Submit button

---

## How the OTP Flow Works

```
User fills form
    ↓
Clicks "Send Verification Code"
    → AJAX fld_send_otp → Brevo SMTP → 6-digit code emailed
    ↓
User enters code + clicks "Verify"
    → AJAX fld_verify_otp → server issues one-time token
    → JS stores token in cookie (fld_otp_token, 30 min TTL)
    ↓
User clicks Submit
    → Forminator AJAX fires (sends cookies automatically)
    → forminator_custom_form_submit_errors filter (PHP)
    → reads $_COOKIE['fld_otp_token']
    → verifies transient → consumes token → clears cookie
    → form passes → lead created in dashboard
```

**Token storage (WordPress transients):**
- `fld_otp_{md5(email)}` — the OTP code, TTL 10 min
- `fld_otp_token_{token}` — verified email, TTL 30 min
- `fld_otp_rate_{md5(ip)}` — request count, TTL 1 hour (max 5 sends/IP/hour)

---

## Critical Bug Fixed in This Session

**Problem:** Forminator renders `<form data-form-id="123">` but the JS was looking for `form[data-id]` — a non-existent attribute. Zero forms matched, the widget never appeared, no cookie was ever set, and the server-side check blocked every submission with *"Your form is not valid, please fix the errors!"*

**Fix:** Selector updated to `form[data-form-id]` in `assets/js/fld-otp.js` inside the `attachToForms()` function.

---

## What Still Needs Testing

- [ ] End-to-end OTP flow: widget appears → code arrives in inbox → verified → form submits → lead appears in dashboard
- [ ] Rate limiting: send OTP 6 times from same IP in 1 hour → 6th request should fail
- [ ] Token expiry: verify OTP, wait 31+ min, then submit → should be blocked
- [ ] Email field reset: change the email after verifying → widget should reset to idle, old cookie cleared
- [ ] Unprotected form: a form NOT in the enabled list should submit normally with zero OTP interference

---

## Existing Plugin Features (Untouched)

| Feature | Status |
|---|---|
| Lead Dashboard with stats + charts | Working |
| All Leads page with filters, search, pagination | Working |
| Lead detail modal (status change, feedback, activity log) | Working |
| CSV export | Working |
| Sales Admin role (locked-down WP admin) | Working |
| Email notifications on new lead | Settings exist, `wp_mail()` call **not yet implemented** |
| Auto-assign new leads | Settings exist, Forminator hook **not yet wired** |

---

## Quick Sanity Check on Resume

```bash
php -l forminator-lead-dashboard.php
php -l includes/class-fld-otp.php
php -l templates/settings.php
# All should output: No syntax errors detected
```

---

## New WordPress Options Summary

| Option | Type | Description |
|---|---|---|
| `fld_smtp_host` | string | Brevo SMTP host |
| `fld_smtp_port` | int | SMTP port (587) |
| `fld_smtp_username` | string | SMTP login |
| `fld_smtp_password` | string | SMTP password |
| `fld_smtp_encryption` | string | `tls` / `ssl` / empty |
| `fld_brevo_sender_name` | string | "From" display name |
| `fld_brevo_sender_email` | string | "From" address |
| `fld_otp_enabled_forms` | int[] | Form IDs requiring OTP |
