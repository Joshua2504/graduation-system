# 🎓 Graduation Project Management System

**نظام إدارة مشاريع التخرج** — A bilingual (Arabic / English) web application for managing university graduation projects. Students form teams, complete profiles, submit projects for review, and professors approve or reject them.

---

## ✨ Features

### Student Flow
- **Landing page** — one-page login screen with system introduction, feature highlights, and bilingual support
- **Register & verify** — create account with student code, optionally verify email
- **Complete profile** — personal info (gender, national ID, birth date, governorate, address, phone, department) + upload 3 documents (institute card, national ID, payment receipt) + optional profile picture shown across the platform
- **Create project** — become team leader, get a unique 6-character join code
- **Build a team** — invite members via:
  - 🔗 Shareable invite link (token-based, with expiration)
  - 📱 QR code (auto-generated from invite link)
  - 🔑 Join code (6-char alphanumeric)
  - 👤 Direct invite (search by email or student code)
- **Submit for review** — once team meets size requirements and all member profiles are complete
- **Rich description editor** — bold, italic, underline, lists, links, and image uploads (click, paste, or drag & drop)
- **Track status** — view doctor feedback, resubmit if rejected

### Professor (Doctor) Flow
- **Dashboard** — projects organized by status tabs (Draft, Under Review, Accepted, Rejected) with sorting and member counts
- **Review projects** — view all team members' profiles, documents, and images; accept or reject with notes
- **Edit projects** — inline editing of project title, type, and rich-text description for any project
- **Duplicate detection** — automatic warning when project titles match
- **System settings** — toggle registration, email verification, min/max team size (2–10)
- **Manage students** — list all student accounts, verify emails manually, enable/disable accounts

### Demo Mode
- **Quick login** — one-click login buttons for doctor and student on the login page
- **Random passwords** — generated on first boot and regenerated on each reset; displayed on the login page
- **Auto-reset** — 30-minute countdown timer starts after any login; resets all data to seed state
- **Countdown banner** — live timer above the navbar shows remaining time before reset
- **Seed users** — 7 pre-created accounts (1 doctor + 1 test student + 5 demo students) survive every reset
- Enable with `DEMO_MODE=true` in `.env`

### General
- 🌍 Bilingual: Arabic (RTL) & English (LTR) — default language auto-detected from browser; toggle via navbar- 🌙 Dark mode — toggle available on login/register pages and in the navbar; preference saved in browser- � User dropdown menu — click username in navbar for profile & logout
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

# 4. Open in browser
open http://localhost:8642
```

The database schema and seed data are applied automatically on first run.

### Default Credentials

| Role | Email | Password |
|------|-------|----------|
| Professor | `doctor@treudler.net` | `doctor123` |
| Student | `student@treudler.net` | `student123` |
| Demo Student 1–5 | `student1@treudler.net` … `student5@treudler.net` | *random — shown on login page* |

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
    │   ├── profile.php           # Profile read/update/image upload
    │   ├── upload.php            # Document image uploads
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
    ├── professor/
    │   ├── dashboard.php         # Project lists by status with stats
    │   ├── project.php           # Project review with student data
    │   ├── settings.php          # System configuration
    │   └── students.php          # Student account management
    ├── includes/                 # Shared PHP modules
    │   ├── auth.php              # Session auth & role enforcement
    │   ├── db.php                # PDO database connection
    │   ├── functions.php         # Helper functions
    │   ├── lang.php              # Bilingual translations (AR/EN)
    │   ├── mailer.php            # SMTP mailer
    │   ├── demo.php              # Demo mode helpers
    │   ├── header.php            # HTML head template
    │   ├── navbar.php            # Navigation bar
    │   └── footer.php            # HTML footer
    └── assets/
        ├── css/app.css           # Custom styles
        └── js/uploader.js        # File upload utility
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

---

## 🗄 Database Schema

### `settings` — System configuration (singleton)
| Column | Type | Description |
|--------|------|-------------|
| `registration_open` | TINYINT(1) | Allow new student registration |
| `email_verification_required` | TINYINT(1) | Require email verification before login |
| `min_team_size` | TINYINT | Minimum members to submit (default: 2) |
| `max_team_size` | TINYINT | Maximum members allowed (default: 7) |

### `users` — Student & doctor accounts
| Column | Type | Description |
|--------|------|-------------|
| `name` | VARCHAR(255) | Full name |
| `email` | VARCHAR(255) | Unique email |
| `password` | VARCHAR(255) | bcrypt hash |
| `student_code` | VARCHAR(50) | Unique student identifier |
| `role` | ENUM | `student` or `doctor` |
| `gender`, `national_id`, `birth_date`, `governorate`, `address`, `phone`, `section` | — | Profile fields |
| `profile_picture` | VARCHAR(255) | Optional profile picture filename |
| `card_image`, `national_id_image`, `receipt_image` | VARCHAR(255) | Document filenames |
| `profile_completed` | TINYINT(1) | Auto-calculated completeness flag |
| `email_verified` | TINYINT(1) | Email verification status |
| `account_enabled` | TINYINT(1) | Can be disabled by doctor |

### `projects` — Graduation projects
| Column | Type | Description |
|--------|------|-------------|
| `title` | VARCHAR(500) | Project title |
| `type` | VARCHAR(255) | Project type/category |
| `join_code` | VARCHAR(8) | Unique 6-char alphanumeric join code |
| `status` | ENUM | `draft` → `under_review` → `accepted` / `rejected` |
| `group_number` | INT | Auto-assigned on acceptance |
| `doctor_note` | TEXT | Professor's feedback |

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
| `POST` | `{project_id, action, doctor_note?}` | Accept or reject project (doctor only) |

### Settings — `/api/settings`
| Method | Params | Description |
|--------|--------|-------------|
| `GET` | — | Get system settings |
| `POST` | `{registration_open, email_verification_required, min_team_size, max_team_size}` | Update settings (doctor only) |

### Users — `/api/users`
| Method | Params | Description |
|--------|--------|-------------|
| `GET` | — | List all student accounts (doctor only) |
| `POST` | `{user_id, action}` | Toggle verify/enable/disable (doctor only) |

---

## 🐳 Docker Details

| Container | Port | Image |
|-----------|------|-------|
| `grad-app` | `8642` → `80` | Custom PHP 8.2-Apache |
| `grad-db` | Internal only | `mariadb:latest` |

**Configuration:**
- Upload limit: 50MB per file / 55MB POST
- Apache `mod_rewrite` enabled for clean URLs (no `.php` extension in browser)
- Schema auto-applied on first database initialization
- Health check on MariaDB before app container starts

### Common Commands

```bash
# Start
docker compose up -d

# Rebuild after code changes
docker compose up --build -d

# View logs
docker logs grad-app -f

# Reset database (wipe and recreate)
docker compose down
rm -rf data/db_data
docker compose up --build -d

# Access database CLI
docker exec -it grad-db mariadb -u grad_user -p graduation
```

---

## 🌍 Internationalization

The application supports **Arabic** (default, RTL) and **English** (LTR). Language is toggled via the navbar and persisted in the session. All UI strings are managed in `public/includes/lang.php` with 100+ translation keys.

Bootstrap RTL CSS is automatically loaded when Arabic is active.

---

## 📝 License

This project was built for academic purposes as part of a graduation project management workflow.
