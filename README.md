# Job Board Platform

A full-stack job board built with **Laravel 11** (API backend) and **Vue 3** (SPA frontend). Employers post jobs, admins approve them, and candidates search and apply — all in one platform.

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Team & Module Ownership](#team--module-ownership)
- [Project Structure](#project-structure)
- [Getting Started](#getting-started)
- [Environment Variables](#environment-variables)
- [API Overview](#api-overview)
- [User Roles](#user-roles)
- [Database Schema](#database-schema)
- [Payment Flow](#payment-flow)
- [Git Workflow](#git-workflow)

---

## Features

**Employers**
- Register and create a company profile with logo
- Post detailed job listings (category, location, work type, salary, technologies, deadline)
- Edit, close, or delete own listings
- Review applications and accept or reject candidates
- Pay a fee after accepting a candidate (Stripe / PayPal)
- Comment on job posts
- View analytics per listing (views, applicants, conversion rate)

**Candidates**
- Register and build a profile (resume, skills, experience, LinkedIn)
- Search and filter jobs by keyword, category, location, salary, work type, experience level, and date
- Apply by uploading a resume and providing contact details
- Track and cancel applications
- Receive notifications on application status changes

**Admins**
- Approve or reject employer job submissions
- Manage users (suspend / ban accounts)
- Remove inappropriate comments
- Monitor platform-wide activity

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend framework | Laravel 11 |
| Authentication | Laravel Sanctum (SPA token auth) |
| Authorization | Laravel Policies + middleware guards |
| Search | Laravel Scout + Meilisearch |
| Notifications | Laravel Notifications (database + email) |
| Queue / Cache | Redis |
| File storage | Laravel Storage (S3-compatible) |
| Payments | Stripe (Laravel Cashier) + PayPal SDK |
| Frontend framework | Vue 3 (Composition API) |
| State management | Pinia |
| Routing | Vue Router |
| HTTP client | Axios |
| Styling | Tailwind CSS |
| Build tool | Vite |
| Database | MySQL / PostgreSQL |

---

## Team & Module Ownership

Each developer owns a full vertical slice — both the Laravel API and the Vue UI for their module.

| Developer | Module | Scope |
|---|---|---|
| **Wagih** | Authentication & User Management | Sanctum auth, roles & policies, employer/candidate profile APIs, login/register pages, Pinia auth store |
| **Ayman** | Job Listings & Search | Jobs CRUD API, Scout search & filters, categories, job detail & listing pages, employer job management UI |
| **Shalaby** | Applications & Notifications | Applications API, Laravel Notifications, comments API, application submit/track UI, notifications dropdown |
| **Fathy** | Payments, Admin Panel & Analytics | Stripe/PayPal integration, admin approval API, analytics endpoint, payment checkout UI, admin panel UI |

---

## Project Structure

```
JobBoard/
├── package.json               # Root scripts — run client & server from here
├── README.md
│
├── client/                    # Vue 3 SPA (Vite)
│   ├── src/
│   │   ├── views/
│   │   │   ├── auth/
│   │   │   ├── candidate/
│   │   │   ├── employer/
│   │   │   └── admin/
│   │   ├── components/
│   │   ├── stores/            # Pinia stores
│   │   ├── router/
│   │   └── api/               # Axios service layer
│   ├── package.json
│   └── vite.config.js
│
└── server/                    # Laravel 11 API
    ├── app/
    │   ├── Http/
    │   │   ├── Controllers/
    │   │   │   ├── Auth/
    │   │   │   ├── Employer/
    │   │   │   ├── Candidate/
    │   │   │   └── Admin/
    │   │   └── Middleware/
    │   ├── Models/
    │   │   ├── User.php
    │   │   ├── EmployerProfile.php
    │   │   ├── CandidateProfile.php
    │   │   ├── JobListing.php
    │   │   ├── Application.php
    │   │   ├── Comment.php
    │   │   ├── Category.php
    │   │   └── Payment.php
    │   ├── Policies/
    │   └── Notifications/
    ├── database/
    │   ├── migrations/
    │   └── seeders/
    ├── routes/
    │   └── api.php
    └── .env.example
```

---

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 20+
- MySQL or PostgreSQL
- Redis
- Meilisearch (for search)

### 1. Clone the repository

```bash
git clone https://github.com/your-org/job-board.git
cd JobBoard
```

### 2. Server setup (Laravel)

```bash
cd server

# Install PHP dependencies
composer install

# Copy and configure environment
cp .env.example .env
php artisan key:generate

# Run migrations and seed demo data
php artisan migrate --seed

# Start the queue worker (keep running in a separate terminal)
php artisan queue:work
```

### 3. Client setup (Vue)

```bash
cd client

# Install Node dependencies
npm install
```

### 4. Run both together

From the **root** `JobBoard/` folder, install root dependencies once then use the combined scripts:

```bash
# From JobBoard/
npm install

# Run the Vue dev server (client)
npm run client

# Run the Laravel dev server (server)
npm run server
```

> Run `npm run client` and `npm run server` in two separate terminal tabs, or use a process manager like `concurrently`.

### 5. Meilisearch

```bash
# Start Meilisearch locally (Docker)
docker run -p 7700:7700 getmeili/meilisearch

# Index job listings (from the server/ folder)
cd server
php artisan scout:import "App\Models\JobListing"
```

---

## Environment Variables

```env
# Application
APP_NAME="Job Board"
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=job_board
DB_USERNAME=root
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@jobboard.com

# Meilisearch
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://localhost:7700
MEILISEARCH_KEY=

# Stripe
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

# PayPal
PAYPAL_CLIENT_ID=
PAYPAL_CLIENT_SECRET=
PAYPAL_MODE=sandbox

# File storage (S3-compatible)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
```

---

## API Overview

All endpoints are prefixed with `/api`. Authentication uses Laravel Sanctum bearer tokens.

### Auth

| Method | Endpoint | Access |
|---|---|---|
| POST | `/auth/register` | Guest |
| POST | `/auth/login` | Guest |
| POST | `/auth/logout` | Auth |

### Jobs (public)

| Method | Endpoint | Access |
|---|---|---|
| GET | `/jobs` | Public |
| GET | `/jobs/{id}` | Public |
| GET | `/jobs/{id}/comments` | Public |
| POST | `/jobs/{id}/comments` | Auth |

### Employer

| Method | Endpoint | Description |
|---|---|---|
| POST | `/employer/jobs` | Create job listing |
| PUT | `/employer/jobs/{id}` | Edit listing |
| DELETE | `/employer/jobs/{id}` | Delete listing |
| GET | `/employer/jobs/{id}/applications` | View applicants |
| PATCH | `/employer/applications/{id}` | Accept or reject |
| POST | `/employer/applications/{id}/pay` | Initiate payment |
| GET | `/employer/jobs/{id}/analytics` | Listing analytics |

### Candidate

| Method | Endpoint | Description |
|---|---|---|
| POST | `/candidate/apply/{jobId}` | Submit application |
| GET | `/candidate/applications` | My applications |
| DELETE | `/candidate/applications/{id}` | Cancel application |

### Admin

| Method | Endpoint | Description |
|---|---|---|
| GET | `/admin/jobs/pending` | Pending approvals queue |
| PATCH | `/admin/jobs/{id}/approve` | Approve listing |
| PATCH | `/admin/jobs/{id}/reject` | Reject with reason |
| DELETE | `/admin/comments/{id}` | Remove comment |

### Notifications & Payments

| Method | Endpoint | Description |
|---|---|---|
| GET | `/notifications` | List notifications |
| PATCH | `/notifications/{id}/read` | Mark as read |
| POST | `/payments/webhook` | Stripe / PayPal webhook |

---

## User Roles

```
users.role = enum('employer', 'candidate', 'admin')
```

Route groups are protected by role middleware:

```php
Route::middleware(['auth:sanctum', 'role:employer'])->prefix('employer')->group(...);
Route::middleware(['auth:sanctum', 'role:candidate'])->prefix('candidate')->group(...);
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(...);
```

---

## Database Schema

### Core tables

| Table | Purpose |
|---|---|
| `users` | All accounts; role enum differentiates them |
| `employer_profiles` | Company name, logo, website, description |
| `candidate_profiles` | Resume path, skills (JSON), experience, LinkedIn |
| `job_listings` | Full job post; status: `pending / approved / rejected / closed` |
| `applications` | Candidate applications; status: `pending / accepted / rejected / cancelled` |
| `categories` | Job categories (programming, management, …) |
| `comments` | Job post comments; `is_hidden` flag for admin moderation |
| `payments` | Payment records linked to accepted applications |
| `notifications` | Laravel default polymorphic notifications table |

### Job listing status lifecycle

```
draft → pending (submitted) → approved (admin) → closed
                            ↘ rejected (admin) → pending (resubmitted after edit)
```

---

## Payment Flow

1. Employer reviews applications and clicks **Accept** on a candidate.
2. Application status changes to `accepted`.
3. Employer is redirected to the payment checkout (Stripe or PayPal).
4. On successful payment, the webhook fires and updates `payments.status` to `paid`.
5. Candidate contact details (email, phone) become visible to the employer.
6. Both parties receive an email notification.

---

## Git Workflow

```
main          ← production-ready releases only
develop       ← integration branch; all features merge here
feature/xxx   ← individual feature branches (one per task)
```

Branch naming:

```
feature/wagih-auth
feature/ayman-job-listings
feature/shalaby-applications
feature/fathy-payments
```

Pull request rules:
- All PRs target `develop`
- At least one peer review required before merge
- No direct pushes to `main` or `develop`

---

## Sprint Plan

| Sprint | Focus |
|---|---|
| Sprint 1 | DB migrations, Sanctum auth API, Vue setup, auth pages, Pinia stores |
| Sprint 2 | Jobs CRUD + admin approval, applications API, search UI, employer dashboard |
| Sprint 3 | Payments, notifications, analytics, candidate dashboard, bonus features |

---

## License

MIT