# CEIT CvSU — Web-Based Complaint and Inquiry Management System

A working prototype built to match your capstone paper's approved modules
(Account Management, Complaint Management, Inquiry Management, Status
Tracking, Report Generation) and the wireframes in your defense deck
(Create Account, Login, Student Dashboard, Submission, Tracking, Admin
Dashboard, Case Page, Report Page, Team Page).

Stack: **PHP + MySQL + HTML/CSS/JavaScript**, no frameworks — runs directly
on XAMPP, matching the tools listed in your paper.

## 1. Install & start XAMPP
Download from https://www.apachefriends.org if you don't have it, then
open the XAMPP Control Panel and **Start** both **Apache** and **MySQL**.

## 2. Copy the project files
Copy the entire `ceit-complaint-system` folder into your XAMPP `htdocs`
directory, so you end up with:
```
C:\xampp\htdocs\ceit-complaint-system\        (Windows)
/Applications/XAMPP/htdocs/ceit-complaint-system/   (Mac)
```

## 3. Create the database
1. Open http://localhost/phpmyadmin in your browser.
2. Click **Import** → **Choose File** → select `schema.sql` from this
   folder → click **Go**.
3. This creates the `ceit_complaints` database with all six tables
   (`users`, `cases`, `attachments`, `status_history`, `messages`,
   `notifications`). No dummy accounts are pre-loaded — you'll register
   your own in step 5.

## 4. Check the database credentials
Open `config/db.php`. The defaults (`root` / no password / `localhost`)
match a stock XAMPP install, so you usually don't need to change anything.
If your MySQL uses a different username/password, update the four
variables at the top of that file.

## 5. Open the site and register your first accounts
Go to:
```
http://localhost/ceit-complaint-system/
```
You'll land on the login page. Click **Create One Here** to register:
- Register once with the **Admin** toggle selected → this is your CEIT
  staff / guidance office account.
- Register again (different email) with **Student** selected → this is
  what students will use to submit complaints and inquiries.

From then on, log in with whichever account you want to test.

## What's included

| Area | Pages |
|---|---|
| Auth | Register (role toggle), Login, Logout |
| Student | Dashboard (stats + recent cases), Submit (Complaint/Inquiry tabs), Track (filterable list), Case detail (progress stepper + chat), Settings |
| Admin | Dashboard (stats + filters + search), Case detail (status update + chat), Report (category/status/priority breakdowns), Team (manage staff accounts, view students), Settings |

## Notes / things to know before you defend or extend this

- **Anonymous submissions**: the student's identity is still stored (so
  status notifications work) but hidden from the admin's view when the
  "Submit Anonymously" toggle is on.
- **File uploads** are stored in `/uploads` and limited to JPEG/PNG/PDF,
  10MB. A `.htaccess` in that folder blocks any uploaded file from being
  executed as a script.
- **Admin self-registration**: the wireframe you approved shows a
  Student/Admin toggle right on the public signup form, so that's what's
  implemented. For an actual production rollout at CEIT you'd likely
  want to lock that down (e.g., admin accounts created only via the Team
  page, or gated behind an invite code) — flagged as a comment at the
  top of `auth/register.php`.
- **Passwords** are hashed with PHP's `password_hash()` (bcrypt) — never
  stored in plain text.
- This is a working functional prototype sized for a capstone demo —
  for a real institutional rollout you'd want things like email
  verification, rate limiting on login, HTTPS, and a proper file-storage
  service instead of a local `/uploads` folder.

## If something breaks
Paste the exact error message (PHP will usually show a clear one) and
which page you were on — most issues at this stage are either a
database-credential mismatch in `config/db.php` or the schema not being
imported yet.
