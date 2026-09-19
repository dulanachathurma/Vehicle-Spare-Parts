# AutoParts Lanka - Vehicle Spare Parts Management System (VSPMS)

> **Live Hosted Website:** [https://spare-parts.infy.click](https://spare-parts.infy.click)

FROZEN: see `docs/PROJECT_BRIEF.md`, Section 3. This file is set up once
by Module 1 and not edited by later modules.

The authoritative specification for this project is
[`docs/PROJECT_BRIEF.md`](docs/PROJECT_BRIEF.md). Read it before touching
any code.

## Requirements

- WAMP Server (Apache + PHP 8.1+ + MySQL/MariaDB) on Windows
- A browser

## First-time setup

1. Place the project at `C:\wamp64\www\vehicle-spare-parts`.
2. Start WAMP and open phpMyAdmin.
3. Create the database (or let the schema import do it - `schema.sql`
   includes `CREATE DATABASE IF NOT EXISTS vspms_db`).
4. Import the SQL files **in this order**:
   1. `database/schema.sql`
   2. `database/seed_core.sql`
   3. `database/seed_catalogue.sql` (once Module 2 exists)
   4. `database/seed_gateways.sql` (once Module 3 exists - note that the
      two payment-gateway rows are already seeded by `seed_core.sql`; see
      `docs/module1.md` for why, and drop one of the two if you import
      both)
   5. Every file in `database/migrations/`, in numeric order (see
      "Database migrations" below).
5. Copy `config/config.local.example.php` to `config/config.local.php`
   and fill in your own database credentials (and, later, your PayHere
   sandbox Merchant ID/Secret). This file is git-ignored and must never
   be committed.
6. Open `http://localhost/vehicle-spare-parts/`.

## Database migrations

`database/schema.sql` is frozen after the first commit (Section 3, Rule
3) - a feature added later that needs a new column or table adds a
numbered file to `database/migrations/` instead of editing it. These
are **not** imported automatically by `scripts/setup_db.bat`/`.sh` or
the Docker dev environment, so:

- **First-time setup**: import every file in `database/migrations/`,
  in numeric order, right after the seed files (see step 4 above).
- **Pulling later updates**: after `git pull`, check
  `database/migrations/` for any new numbered file you haven't run yet
  against your own database, and import it. Skipping this shows up as
  a SQL error the first time the new feature runs (e.g. "Unknown
  column" or "Table doesn't exist"), not as a failure to pull.

Currently: `004_order_contact_info.sql` (adds `recipientName` /
`recipientPhone` to `orders`, for the name/phone fields on checkout)
and `005_return_requests.sql` (adds the `return_request` table, for
customers requesting a return on a Delivered order) still need to be
run manually against any database created before they were added.

## Seed accounts

| Role  | Username     | Password      |
|-------|--------------|---------------|
| Admin | `admin`      | `Admin@123`   |
| User  | `john_doe`   | `Password123` |
| User  | `jane_smith` | `Password123` |

## Project structure

Folder-per-feature, one PHP page per file, no framework. Each module
owns a fixed set of files - see `docs/PROJECT_BRIEF.md`, Sections 3 and
6, for the full ownership map. Shared code (`config/`, `includes/`,
`assets/css/base.css`, `assets/js/base.js`, `database/schema.sql`) is
frozen after the first commit; anything a module needs beyond it goes
into that module's own `lib/` folder.

## Password reset in development

WAMP has no mail server, so `auth/forgot_password.php` writes the reset
link to `logs/mail.log` and also displays it on screen, so the flow can
be demonstrated without real email.

## Running the test suite

There is no automated PHP test suite in this project (see
`docs/PROJECT_BRIEF.md` for why a framework/tooling stack was avoided).
Module 4a's CI pipeline lints every PHP file and proves the schema
imports cleanly - see `docs/devops.md` once Module 4a is built.
