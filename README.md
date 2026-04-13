# 🎓 Graduation Project Management System

**نظام إدارة مشاريع التخرج** — A multilingual (Arabic / English / German) web application for managing university graduation projects. An admin manages professors and system settings, professors review and approve projects, and students form teams, complete profiles, and submit projects.

---

## ✨ Features

### Student Flow
- **Landing page** — one-page login screen with system introduction, feature highlights, and multilingual support
- **Register & verify** — create account with student code, optionally verify email
- **Forgot password** — request a password reset link via email; works for both students and professors
- **Complete profile** — personal info (gender, national ID, birth date, governorate, address, phone, department) + upload 3 documents (institute card, national ID, payment receipt) + optional profile picture shown across the platform
- **Create project** — become team leader, get a unique 6-character join code
- **Build a team** — invite members via:
  - 🔗 Shareable invite link (token-based, with expiration)
  - 📱 QR code (auto-generated from invite link)
  - 🔑 Join code (6-char alphanumeric)
  - 👤 Direct invite (search by email or student code)
  - ⚠️ Students can only join teams where all members share the same academic year and department
- **Submit for review** — once team meets size requirements and all member profiles are complete
- **Rich description editor** — bold, italic, underline, lists, links, and image uploads (click, paste, or drag & drop)
- **Track status** — view doctor feedback, review history timeline, resubmit if rejected (when allowed by professor)
- **Transfer leadership** — team leaders can transfer leadership to another team member (when enabled by professor, only while project is in draft status)

### Admin (Super Admin) Flow
- **Dashboard** — overview with professor/student counts, project stats by status, recent professors and projects
- **Manage professors** — create, enable/disable, delete professor accounts; reset passwords; send welcome emails with credentials; impersonate professors
- **Manage students** — same student management interface as professors (list, edit profiles, verify emails, enable/disable, create accounts, impersonate)
- **All projects** — read-only overview of all projects with tab filtering (All, Draft, Under Review, Accepted, Rejected)
- **System settings** — toggle registration, email verification, min/max team size (2–10), student project creation, show reviewer name, leader transfer by team leader, login methods (email & student code / email only / student code only), language selection
- **Department management** — add, edit, and delete departments; departments appear as selectable dropdowns in all profile forms
- **Profile** — edit personal info (gender, phone, department) and upload a profile picture

### Professor (Doctor) Flow
- **Profile** — edit personal info (gender, phone, department) and upload a profile picture
- **Dashboard** — projects organized by status tabs (Draft, Under Review, Accepted, Rejected) with sorting and member counts
- **Review projects** — view all team members' profiles, documents, and images; accept or reject with notes; optionally allow or deny resubmission on rejection
- **Review history** — full audit trail of all accept/reject actions with timestamps, notes, and resubmission permissions
- **Edit projects** — inline editing of project title, type, and rich-text description for any project
- **Duplicate detection** — automatic warning when project titles match
- **System settings** — toggle registration, email verification, min/max team size (2–10), student project creation, show reviewer name, leader transfer by team leader, login methods (email & student code / email only / student code only)
- **Manage students** — list all student accounts, verify emails manually, enable/disable accounts

### Demo Mode
- **Quick login** — one-click login buttons for admin, doctor, and student on the login page
- **Random passwords** — generated on first boot and regenerated on each reset; displayed on the login page
- **Auto-reset** — 30-minute countdown timer starts after any login; resets all data to seed state
- **Countdown banner** — live timer above the navbar shows remaining time before reset
- **Seed users** — 7 pre-created accounts (1 admin + 1 doctor + 5 demo students) created automatically on first request
- **Seed projects** — 2 pre-created projects:
  - 📚 *نظام إدارة المكتبات* (Library Management System) — accepted, 3 members (students 1-3)
  - 🏋️ *تطبيق تتبع اللياقة البدنية* (Fitness Tracking App) — under review, 2 members (students 4-5)
- **All languages enabled** — Arabic, English, and German are all available in demo mode
- **Permanent admin** — `it@admin.com` is a permanent admin account created alongside demo accounts; it is excluded from demo resets (never deleted, password never regenerated) and survives the 30-minute auto-reset cycle
- Enable with `DEMO_MODE=true` in `.env`
- No demo accounts or content exist when demo mode is disabled

### General
- 🌍 Multilingual: Arabic (RTL), English (LTR) & German (LTR) — default language auto-detected from browser; switch via navbar dropdown- 🌙 Dark mode — toggle available on login/register pages and in the navbar; preference saved in browser- � User dropdown menu — click username in navbar for profile & logout
- �📱 Fully responsive (Bootstrap 5)
- 🐳 Dockerized — one command to run

---

## 🛠 Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.2 |
| Database | MariaDB (latest) |
| Web Server | Apache 2 with `mod_rewrite` |
| Frontend | Bootstrap 5.3.3 RTL, Bootstrap Icons 1.11.3, Vanilla JS |
| Containerization | Docker + Docker Compose |
| Mail | Custom SMTP mailer (STARTTLS/TLS, no external dependencies) |
| Auth | Session-based, bcrypt password hashing |
| File Security | Uploads served via authenticated endpoint, direct access blocked |

---

## 🚀 Quick Start

### Prerequisites
- [Docker](https://docs.docker.com/get-docker/) & [Docker Compose](https://docs.docker.com/compose/install/)

### Setup

```bash
# 1. Clone the repository
git clone <repo-url>
cd graduation-system

# 2. Create environment file
cp .env.example .env
# Edit .env with your SMTP credentials (optional for email features)

# 3. Start the application
docker compose up --build -d

# 4. Open in browser (default port, configurable via APP_PORT)
open http://localhost:8642
```

The database schema is applied automatically on first run.

### Initial Setup (Production)

When `DEMO_MODE=false` (default), the first user to register becomes the **admin**. Navigate to the app after starting it and you will be guided through the initial setup wizard to create the admin account. The admin can then create professor accounts from the admin dashboard.

### Demo Mode

With `DEMO_MODE=true`, demo accounts and sample projects are seeded automatically on first request. See the **Demo Mode** section above for details.

---

## 📁 Project Structure

```
graduation-system/
├── .env.example                  # Environment variables template
├── Dockerfile                    # PHP 8.2-Apache image configuration
├── docker-compose.yml            # App + MariaDB services
├── db/
│   └── schema.sql                # Database schema + seed data
├── data/
│   ├── db_data/                  # Persistent MariaDB data (gitignored)
│   └── uploads_data/             # Persistent file uploads (gitignored)
└── public/                       # Apache document root
    ├── index.php                 # Entry point — redirects by role
    ├── login.php                 # Login page
    ├── register.php              # Student registration
    ├── join.php                  # Join project via token or code
    ├── verify.php                # Email verification handler
    ├── logout.php                # Session logout
    ├── api/                      # REST API endpoints
    │   ├── project.php           # CRUD for projects
    │   ├── invitations.php       # Invitation management
    │   ├── professors.php        # Professor account management (admin only)
    │   ├── profile.php           # Profile read/update/image upload
    │   ├── description-upload.php # Project description image uploads
    │   ├── file.php              # Secure authenticated file serving
    │   ├── submit.php            # Project submission
    │   ├── review.php            # Doctor accept/reject
    │   ├── settings.php          # System settings
    │   ├── users.php             # Student account management
    │   └── demo-reset.php        # Demo mode reset API
    ├── student/
    │   ├── dashboard.php         # Project list, join by code, invitations
    │   ├── project.php           # Project detail, team, invites, submit
    │   └── profile.php           # Profile editor with document uploads
    ├── admin/
    │   ├── dashboard.php         # Admin overview with stats
    │   ├── professors.php        # Professor account management
    │   ├── students.php          # Student account management (admin)
    │   ├── projects.php          # Read-only all-projects overview
    │   ├── settings.php          # System configuration (admin)
    │   └── profile.php           # Admin profile editor
    ├── professor/
    │   ├── dashboard.php         # Project lists by status with stats
    │   ├── project.php           # Project review with student data
    │   ├── settings.php          # System configuration
    │   ├── students.php          # Student account management
    │   └── profile.php           # Professor profile editor
    ├── includes/                 # Shared PHP modules
    │   ├── bootstrap.php         # Common includes loader
    │   ├── auth.php              # Session auth & role enforcement
    │   ├── db.php                # PDO database connection
    │   ├── functions.php         # Helper functions & validators
    │   ├── lang.php              # Multilingual translations (AR/EN/DE)
    │   ├── lang_switcher.php     # Reusable language switcher dropdown
    │   ├── mailer.php            # SMTP mailer (verification, invitation, welcome)
    │   ├── demo.php              # Demo mode helpers
    │   ├── header.php            # HTML head template
    │   ├── navbar.php            # Navigation bar
    │   └── footer.php            # HTML footer
    └── assets/
        ├── css/app.css           # Custom styles
        └── js/
            ├── editor.js         # Shared rich text editor (toolbar, upload, resize)
            └── profile-upload.js # Profile image upload with progress
```

---

## ⚙️ Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_HOST` | Database hostname | `db` |
| `DB_PORT` | Database port | `3306` |
| `DB_NAME` | Database name | `graduation` |
| `DB_USER` | Database user | — |
| `DB_PASS` | Database password | — |
| `DB_ROOT_PASS` | MariaDB root password | — |
| `MAIL_HOST` | SMTP server hostname | — |
| `MAIL_PORT` | SMTP port | `587` |
| `MAIL_USER` | SMTP username / sender email | — |
| `MAIL_PASS` | SMTP password | — |
| `DEMO_MODE` | Enable demo mode with quick-login & auto-reset | `false` |
| `APP_PORT` | Host port mapped to the app container | `8642` |
| `COMPOSE_PROJECT_NAME` | Docker Compose project name (isolates containers per environment) | `graduation-system` |
| `AVAILABLE_LANGUAGES` | Comma-separated list of languages available in this deployment (e.g. `ar,en,de`) | `ar,en,de` |

---

## 🗄 Database Schema

### `settings` — System configuration (singleton)
| Column | Type | Description |
|--------|------|-------------|
| `registration_open` | TINYINT(1) | Allow new student registration |
| `email_verification_required` | TINYINT(1) | Require email verification before login |
| `min_team_size` | TINYINT | Minimum members to submit (default: 2) |
| `max_team_size` | TINYINT | Maximum members allowed (default: 7) |
| `student_project_creation` | TINYINT(1) | Allow students to create projects |
| `show_reviewer_name` | TINYINT(1) | Show reviewer name to students |
| `leader_transfer` | TINYINT(1) | Allow team leaders to transfer leadership |

### `users` — Student & doctor accounts
| Column | Type | Description |
|--------|------|-------------|
| `name` | VARCHAR(255) | Full name |
| `email` | VARCHAR(255) | Unique email |
| `password` | VARCHAR(255) | bcrypt hash |
| `student_code` | VARCHAR(50) | Unique student identifier |
| `role` | ENUM | `student`, `doctor`, or `admin` |
| `gender`, `national_id`, `birth_date`, `governorate`, `address`, `phone`, `section` | — | Profile fields |
| `profile_picture` | VARCHAR(255) | Optional profile picture filename |
| `card_image`, `national_id_image`, `receipt_image` | VARCHAR(255) | Document filenames |
| `profile_completed` | TINYINT(1) | Auto-calculated completeness flag |
| `email_verified` | TINYINT(1) | Email verification status |
| `account_enabled` | TINYINT(1) | Can be disabled by doctor/admin |

### `projects` — Graduation projects
| Column | Type | Description |
|--------|------|-------------|
| `title` | VARCHAR(500) | Project title |
| `type` | VARCHAR(255) | Project type/category |
| `join_code` | VARCHAR(8) | Unique 6-char alphanumeric join code |
| `status` | ENUM | `draft` → `under_review` → `accepted` / `rejected` |
| `group_number` | VARCHAR(10) | Auto-assigned alphanumeric code on acceptance (e.g. WG01) |
| `doctor_note` | TEXT | Professor's feedback |
| `allow_resubmit` | TINYINT(1) | Whether student can edit & resubmit after rejection |

### `project_members` — Team membership
| Column | Type | Description |
|--------|------|-------------|
| `project_id` | INT | FK → projects |
| `user_id` | INT | FK → users |
| `role` | ENUM | `leader` or `member` |

### `invitations` — Team invitations
| Column | Type | Description |
|--------|------|-------------|
| `project_id` | INT | FK → projects |
| `invited_by` | INT | FK → users (sender) |
| `invited_user_id` | INT | FK → users (nullable, for direct invites) |
| `token` | VARCHAR(64) | Unique invitation token |
| `status` | ENUM | `pending`, `accepted`, `declined`, `expired` |
| `expires_at` | DATETIME | Token expiration |
### `project_reviews` — Review audit log
| Column | Type | Description |
|--------|------|-----------|
| `project_id` | INT | FK → projects |
| `reviewer_id` | INT | FK → users (professor) |
| `action` | ENUM | `accepted` or `rejected` |
| `note` | TEXT | Professor's note at time of review |
| `allow_resubmit` | TINYINT(1) | Whether resubmission was allowed (rejections only) |
| `created_at` | TIMESTAMP | When the review action occurred |
---

## 🔌 API Reference

### Projects — `/api/project`
| Method | Params | Description |
|--------|--------|-------------|
| `GET` | — | List current user's projects |
| `GET` | `?id=X` | Get project details + members |
| `POST` | `{title, type}` | Create project (caller becomes leader) |
| `PUT` | `{project_id, title?, type?}` | Update project (leader only, draft) |
| `DELETE` | `{project_id, remove_user_id?}` | Leave project or remove member |

### Invitations — `/api/invitations`
| Method | Params | Description |
|--------|--------|-------------|
| `GET` | `?project_id=X` | List sent invitations (leader) |
| `GET` | — | List received invitations |
| `POST` | `{project_id, invite_type: 'link'}` | Generate invite link with token |
| `POST` | `{project_id, invite_type: 'direct', search}` | Invite by email/code |
| `PUT` | `{token/join_code/invitation_id, action}` | Accept or decline |
| `DELETE` | `{invitation_id}` | Cancel invitation (leader) |

### Profile — `/api/profile`
| Method | Params | Description |
|--------|--------|-------------|
| `GET` | — | Get own profile |
| `PUT` | `{gender, national_id, ...}` | Update profile fields |
| `POST` | Multipart `{type, file}` | Upload document image or profile picture (type: card, national_id, receipt, profile_picture) |

### File Serving — `/api/file`
| Method | Params | Description |
|--------|--------|-------------|
| `GET` | `?user={id}&file={filename}` | Serve uploaded file with auth check (students: own files only; doctors: any) |

### Submit — `/api/submit`
| Method | Params | Description |
|--------|--------|-------------|
| `POST` | `{project_id}` | Submit project for review (validates team size + profile completeness) |

### Review — `/api/review`
| Method | Params | Description |
|--------|--------|-------------|
| `POST` | `{project_id, action, doctor_note?, allow_resubmit?}` | Accept or reject project (doctor only) |

### Professors — `/api/professors` (admin only)
| Method | Params | Description |
|--------|--------|-------------|
| `GET` | `?search=X` | List all professor accounts |
| `POST` | `{action: 'create_professor', name, email, password, section?, send_email?}` | Create a new professor account |
| `POST` | `{action: 'enable', user_id}` | Enable a professor account |
| `POST` | `{action: 'disable', user_id}` | Disable a professor account |
| `POST` | `{action: 'delete', user_id}` | Delete a professor account |
| `POST` | `{action: 'reset_password', user_id, password, send_email?}` | Reset professor password |
| `POST` | `{action: 'impersonate', user_id}` | Impersonate a professor |

### Settings — `/api/settings`
| Method | Params | Description |
|--------|--------|-------------|
| `GET` | — | Get system settings |
| `POST` | `{registration_open, email_verification_required, min_team_size, max_team_size}` | Update settings (admin or doctor) |

### Users — `/api/users`
| Method | Params | Description |
|--------|--------|-------------|
| `GET` | — | List all student accounts (admin or doctor) |
| `POST` | `{user_id, action}` | Toggle verify/enable/disable (admin or doctor) |

---

## 🐳 Docker Details

| Service | Port | Image |
|---------|------|-------|
| `app` | `APP_PORT` (default `8642`) → `80` | Custom PHP 8.2-Apache |
| `db` | Internal only | `mariadb:latest` |

Container names are derived from `COMPOSE_PROJECT_NAME` (e.g. `graduation-system-dev-app-1`).

**Deployment environments:**

| Environment | Branch | SSH variable | Port | Demo Mode | Rsync excludes |
|-------------|--------|-------------|------|-----------|----------------|
| Dev | `main` | `SSH_PATH_DEV` | `8642` | enabled | `.git`, `.github`, `.env` |
| Production | `production` | `SSH_PATH_PROD` | `8643` | disabled | `.git`, `.github`, `.env`, `data/` |

**Configuration:**
- Upload limit: 50MB per file / 55MB POST
- Apache `mod_rewrite` enabled for clean URLs (no `.php` extension in browser)
- Schema auto-applied on first database initialization
- Demo seed data created automatically via PHP when `DEMO_MODE=true` (no seed data in SQL for non-demo mode)
- Health check on MariaDB before app container starts

### Common Commands

```bash
# Start
docker compose up -d

# Rebuild after code changes
docker compose up --build -d

# View logs
docker compose logs app -f

# Reset database (wipe and recreate)
docker compose down
rm -rf data/db_data
docker compose up --build -d

# Access database CLI
docker compose exec db mariadb -u grad_user -p graduation
```

---

## 🌍 Internationalization

The application supports **Arabic** (RTL), **English** (LTR), and **German** (LTR). Language is auto-detected from the browser's `Accept-Language` header on first visit, switchable via the navbar dropdown, and persisted in the session. All UI strings are managed in `public/includes/lang.php` with 150+ translation keys.

Bootstrap RTL CSS is automatically loaded when Arabic is active.

---

## 📝 License

This project was built for academic purposes as part of a graduation project management workflow.
