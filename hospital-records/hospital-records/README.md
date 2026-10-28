# MediCore – Hospital Records System (pure PHP)

A PHP port of the "Hospital Records System Prototype" Figma Make design.
No JavaScript, no frameworks, no database – PHP 8.1+ only.

## Run

```bash
cd hospital-records
php -S localhost:8000
```

Open http://localhost:8000 and sign in with any password using one of the prototype usernames:

| Username | Workspace |
|----------|-----------|
| `admin`  | Admin (dashboard, users, roles, departments, rooms, medications, archive, audit logs) |
| `staff`  | Front desk (dashboard, patients, appointments, admissions, billing) |
| `doctor` | Doctor (dashboard, my appointments, patients, lab results) |

`wrong` and `disabled` as usernames show the login error states.

## Structure

```
index.php                 Front controller: POST actions, auth, routing
includes/bootstrap.php    Session, escaping, URL, CSRF, flash helpers
includes/data.php         Navigation, route tables, sample data
includes/icons.php        Inline SVG icon set
includes/components.php   badge(), button(), field(), tables, tabs, ...
layouts/app_header.php    Sidebar + top bar
layouts/app_footer.php    Modal + toast + closing tags
partials/modal.php        Add/Edit dialogs (opened with ?modal=...)
pages/login.php           Sign-in screen
pages/admin|staff|doctor  One file per screen
pages/shared/patients.php Patients list used by Staff and Doctor
assets/css/style.css      Styles ported from the design
```

## Notes

- Everything is server-rendered. Filters are GET forms (press Enter or "Apply"), tabs and
  accordions are links, dialogs open via `?modal=Name`, and state changes use POST with a CSRF token.
- Sample data lives in the session (disable/enable users, delete rows, discharge patients).
  Nothing is written to disk. Swap `seed_*()` in `includes/data.php` for real queries (PDO) when ready.
- The Archive "Export archive" button downloads a real CSV.
- Login accepts any non-empty password (prototype behaviour) – replace with real authentication before use.
