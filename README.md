# BayanAlert PH

**Alerto sa Bayan. Ligtas ang Lahat.**

A community safety, disaster preparedness, emergency reporting, and public
information platform prototype for the Philippines. Built with PHP 8, MySQL/MariaDB,
Bootstrap 5, and Leaflet.js (OpenStreetMap). Designed to run on XAMPP.

> **This is a college IT project prototype.** It does **not** automatically
> contact emergency services. In a real emergency, always call **911**.
> Community-submitted reports are not official government alerts until an
> administrator verifies them.

---

## 1. Requirements

- [XAMPP](https://www.apachefriends.org/) with **PHP 8.1+**, **Apache**, and **MySQL/MariaDB**
- A modern web browser (Chrome, Firefox, Edge)
- No paid API keys required — the map uses free OpenStreetMap tiles via Leaflet.js

---

## 2. Setup Instructions (Beginner-Friendly)

### Step 1 — Install XAMPP
Download and install XAMPP from https://www.apachefriends.org/ for your OS
(Windows/macOS/Linux). Run the installer with default options.

### Step 2 — Start Apache and MySQL
Open the **XAMPP Control Panel** and click **Start** next to both:
- **Apache**
- **MySQL**

Both status indicators should turn green.

### Step 3 — Copy the project into htdocs
Copy the entire `bayanalert-ph` folder into your XAMPP `htdocs` directory so the
path looks like this:

- **Windows:** `C:\xampp\htdocs\bayanalert-ph`
- **macOS:** `/Applications/XAMPP/htdocs/bayanalert-ph`
- **Linux:** `/opt/lampp/htdocs/bayanalert-ph`

### Step 4 — Open phpMyAdmin
In your browser, go to: `http://localhost/phpmyadmin`

### Step 5 — Create the database
In phpMyAdmin, click **New** (left sidebar) and create a database named exactly:

```
bayanalert_ph
```

(You can skip this — the SQL file also creates it automatically in Step 6.)

### Step 6 — Import the database
1. Click on the `bayanalert_ph` database (or click **Import** at the top if no
   database is selected yet).
2. Click the **Import** tab.
3. Click **Choose File** and select:
   ```
   database/database.sql
   ```
4. Scroll down and click **Go**.
5. Wait for the success message. This creates all tables and inserts sample
   Philippine data (evacuation centers, hospitals, police/fire stations,
   sample alerts, sample reports) plus 3 demo accounts.

### Step 7 — Configure database credentials
Open `config/database.php` in a text editor. The defaults already match a
stock XAMPP installation:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'bayanalert_ph');
define('DB_USER', 'root');
define('DB_PASS', '');   // Default XAMPP MySQL root password is empty
```

If you changed your MySQL root password, update `DB_PASS` accordingly.

### Step 8 — Open the application
In your browser, go to:

```
http://localhost/bayanalert-ph/
```

You should see the BayanAlert PH landing page.

---

## 3. Demo Accounts (Development Only)

These accounts are seeded by `database.sql`. **Change these passwords before
any real deployment** — the system will prompt you to do so on first login.

| Role       | Email                        | Password        |
|------------|-------------------------------|------------------|
| Admin      | admin@bayanalert.test         | `Admin123!`      |
| Responder  | responder@bayanalert.test     | `Responder123!`  |
| Citizen    | citizen@bayanalert.test       | `Citizen123!`    |

You can also register a new citizen account from the **Register** page.

---

## 4. User Roles

- **Citizen** — Register, report incidents, send SOS, view alerts relevant to
  their location, find evacuation centers/facilities, view Learn & Prepare
  guides, manage their profile and notifications.
- **Admin** — Full dashboard with charts, manage/verify/reject reports, manage
  alerts, evacuation centers, facilities, announcements, users, and the
  configurable emergency contacts list.
- **Responder (LGU/Authorized Responder)** — View reports assigned to them and
  reports in their area, update incident status, post announcements, update
  evacuation center occupancy.

Role-based access control is enforced server-side (`includes/auth.php`,
`require_role()`) — a citizen cannot open admin/responder pages and vice versa.

---

## 5. Project Structure

```
bayanalert-ph/
├── index.php, login.php, register.php, logout.php
├── config/database.php          (PDO connection + app settings)
├── includes/                    (auth, header, footer, navbar, functions)
├── citizen/                     (citizen-facing pages)
├── admin/                       (admin dashboard & management)
├── responder/                   (responder dashboard & tools)
├── api/                         (JSON endpoints for the map, etc.)
├── assets/css/style.css, assets/js/*.js
├── uploads/reports/             (incident photo uploads)
└── database/database.sql        (schema + sample data)
```

---

## 6. Security Features Implemented

- PDO prepared statements everywhere (no raw SQL concatenation)
- `password_hash()` / `password_verify()` for all passwords
- CSRF tokens on every state-changing form (`csrf_field()` / `csrf_verify()`)
- Session-based auth with idle timeout (30 minutes) and `session_regenerate_id()` on login
- Role-based authorization on every protected page (`require_role()`)
- Login rate limiting (5 failed attempts → 15-minute lockout)
- File upload validation: MIME-type checked, size-limited, renamed to random
  filenames, and `uploads/` blocks script execution via `.htaccess`
- Output escaping via `e()` (wraps `htmlspecialchars`) to prevent XSS
- `config/` and `.sql`/`.md`/`.log` files blocked from direct web access via `.htaccess`

---

## 7. Notes on Data Honesty

- **Weather** is not live-integrated in this prototype (no paid API used); the
  citizen dashboard links out to PAGASA instead of fabricating conditions.
- **Alerts** always display their **source** (e.g., "PAGASA (sample/demo)")
  so citizens can tell official bulletins apart from prototype/demo data.
- **Sample/demo data** (evacuation centers, facilities, alerts, accounts) is
  clearly labeled and should be replaced with real, verified data before any
  production use.
- **SOS and incident reports** are stored in the database and made visible to
  admins/responders — the system explicitly does **not** claim to auto-dispatch
  emergency services.

---

## 8. Extending This Prototype

This system is structured so it can grow into a real platform:
- Swap the weather placeholder for a real PAGASA data feed once available
- Add SMS/email notifications (e.g., via a gateway API) on top of the existing
  in-app notification system
- Add push notifications for mobile
- Integrate real GIS layers (hazard maps from DENR-MGB, PHIVOLCS) as additional
  Leaflet overlays
- Add multi-language support (Filipino/English toggle)

---

## 9. Going Live (Removing Demo/Sample Data)

Once you're done testing and ready to open the site to real users, remove all
demo accounts and sample data:

1. **Back up your database first** (phpMyAdmin → Export), just in case.
2. In phpMyAdmin, open the **SQL** tab on your `bayanalert_ph` database and
   run `database/go_live_cleanup.sql`. This deletes:
   - The 3 demo accounts (admin/responder/citizen@bayanalert.test)
   - All sample evacuation centers and facilities (`is_sample = 1` rows)
   - The 3 sample incident reports
   - The 3 `[DEMO]`-prefixed sample alerts
   - Relabels the one sample emergency contact row
3. **This deletes the only admin account**, so immediately after, visit:
   ```
   https://yourdomain.com/admin-setup.php?key=YOUR_SETUP_KEY
   ```
   Open `admin-setup.php` first and change the `SETUP_KEY` constant at the
   top (or set a `SETUP_KEY` environment variable) to something private only
   you know, *before* running the cleanup script — this prevents a stranger
   from creating the first admin account before you do.
4. Fill in the form to create your real administrator account.
5. **Delete `admin-setup.php` from the server immediately after use.** It
   self-locks once an admin exists, but it shouldn't remain on a live site.
6. Log in as your new admin and add real evacuation centers, facilities, and
   emergency contacts through the admin panel (Manage → Evacuation /
   Facilities / Settings) to replace the sample ones you just removed.

All demo-specific wording (the login page's demo-account hint, the "Demo
Data" badge, the "(Sample Data)" label on the homepage) has already been
removed from the codebase — the cleanup script only needs to handle the
database rows.

---

## 10. Troubleshooting

- **"Database Connection Error"** — Make sure MySQL is running in XAMPP and
  that `bayanalert_ph` was imported successfully (Step 6).
- **Blank page / 500 error** — Check `php_error.log` in your XAMPP `logs`
  folder; ensure you're running PHP 8.1+.
- **Map not loading** — Requires an internet connection (it loads OpenStreetMap
  tiles and Leaflet.js from public CDNs).
- **Photo upload fails** — Ensure `uploads/reports/` is writable by the web
  server (`chmod 755` or `775` on Linux/macOS).
