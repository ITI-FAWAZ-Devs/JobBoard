# Job Board API — Testing Guide

**Base URL:** `http://localhost:8000/api`

---

## Setup

```bash
# From server/
php artisan serve
```

---

## 1. Authentication

### Register a Candidate

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password",
    "password_confirmation": "password",
    "role": "candidate"
  }'
```

### Register an Employer

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Acme Corp",
    "email": "employer@example.com",
    "password": "password",
    "password_confirmation": "password",
    "role": "employer"
  }'
```

### Register an Admin

> Admin role must be set directly in the database (not via the API):
> ```sql
> UPDATE users SET role = 'admin' WHERE email = 'admin@example.com';
> ```

### Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "employer@example.com",
    "password": "password"
  }'
```

**Response** — save the `token` value for all subsequent requests:

```json
{
  "token": "1|abc123def456...",
  "user": { ... }
}
```

---

## 2. Public Endpoints (no auth required)

### List & Search Jobs (GET /api/jobs)

```bash
# All approved jobs
curl http://localhost:8000/api/jobs

# Search by keyword
curl "http://localhost:8000/api/jobs?q=laravel"

# Filter by category
curl "http://localhost:8000/api/jobs?category_id=1"

# Filter by location
curl "http://localhost:8000/api/jobs?location=New+York"

# Filter by work type
curl "http://localhost:8000/api/jobs?work_type=remote"

# Filter by salary range
curl "http://localhost:8000/api/jobs?salary_min=50000&salary_max=120000"

# Filter by date posted
curl "http://localhost:8000/api/jobs?date_from=2026-01-01&date_to=2026-06-01"

# Combined search + filters
curl "http://localhost:8000/api/jobs?q=developer&work_type=full-time&category_id=2&salary_min=60000"

# Custom pagination (default 15, max 100)
curl "http://localhost:8000/api/jobs?per_page=5"
```

### View Single Job (GET /api/jobs/{id})

```bash
curl http://localhost:8000/api/jobs/1
```

> This increments the job's `views_count`.

---

## 3. Employer Endpoints (auth + role:employer required)

Set the token for the following requests:

```bash
TOKEN="1|abc123def456..."
```

### List My Jobs

```bash
curl http://localhost:8000/api/employer/jobs \
  -H "Authorization: Bearer $TOKEN"
```

### Create a Job Listing

```bash
curl -X POST http://localhost:8000/api/employer/jobs \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Senior Laravel Developer",
    "description": "We are looking for an experienced Laravel developer...",
    "requirements": "- 5+ years PHP experience\n- Laravel expertise\n- REST API design",
    "benefits": "Remote work, competitive salary, health insurance",
    "location": "New York, NY",
    "salary_min": 90000,
    "salary_max": 130000,
    "work_type": "full-time",
    "deadline": "2026-07-01",
    "category_id": 1
  }'
```

> The job is created with `status: "pending"` — visible only to the employer and admin until approved.

### Get Single Employer Job

```bash
curl http://localhost:8000/api/employer/jobs/1 \
  -H "Authorization: Bearer $TOKEN"
```

### Update a Job Listing

```bash
curl -X PUT http://localhost:8000/api/employer/jobs/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Updated: Senior Laravel Developer",
    "salary_max": 140000
  }'
```

> Only the owning employer can update their own jobs.

### Delete a Job Listing

```bash
curl -X DELETE http://localhost:8000/api/employer/jobs/1 \
  -H "Authorization: Bearer $TOKEN"
```

> Uses soft-delete — the record remains in the database.

---

## 4. Admin Endpoints (auth + role:admin required)

Set the token:

```bash
ADMIN_TOKEN="2|xyz789abc..."
```

### List Pending Jobs

```bash
curl http://localhost:8000/api/admin/jobs/pending \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

### Approve a Job

```bash
curl -X PATCH http://localhost:8000/api/admin/jobs/1/approve \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

> Sets `status: "approved"` and `approved_at` timestamp. The job becomes visible in public search.

### Reject a Job

```bash
curl -X PATCH http://localhost:8000/api/admin/jobs/1/reject \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Incomplete job description. Please add more details about the role."
  }'
```

> Sets `status: "rejected"` and stores the `rejection_reason`.

---

## 5. User Management (auth required)

```bash
TOKEN="1|abc123def456..."
```

### Get My Profile

```bash
curl http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer $TOKEN"
```

### Logout

```bash
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer $TOKEN"
```

### List Users (admin only)

```bash
curl http://localhost:8000/api/users \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

---

## Workflow: End-to-End Test

```bash
# 1. Register employer
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Tech Co","email":"tech@test.com","password":"password","password_confirmation":"password","role":"employer"}'

# 2. Login as employer
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"tech@test.com","password":"password"}'
# → Save token as EMPLOYER_TOKEN

# 3. Create a job
curl -X POST http://localhost:8000/api/employer/jobs \
  -H "Authorization: Bearer $EMPLOYER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Backend Dev","description":"Hiring...","work_type":"remote"}'

# 4. Try public search (should be empty — not yet approved)
curl "http://localhost:8000/api/jobs?q=Backend"

# 5. Login as admin (promote user first in DB)
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","password":"password"}'
# → Save token as ADMIN_TOKEN

# 6. Approve the job
curl -X PATCH http://localhost:8000/api/admin/jobs/1/approve \
  -H "Authorization: Bearer $ADMIN_TOKEN"

# 7. Public search now returns the job
curl "http://localhost:8000/api/jobs?q=Backend"
```

---

## Expected Responses

### Success (single job)
```json
{
  "data": {
    "id": 1,
    "title": "Senior Laravel Developer",
    "description": "We are looking for...",
    "requirements": "- 5+ years PHP...",
    "benefits": "Remote work...",
    "location": "New York, NY",
    "salary_min": "90000.00",
    "salary_max": "130000.00",
    "work_type": "full-time",
    "deadline": "2026-07-01",
    "status": "approved",
    "views_count": 1,
    "rejection_reason": null,
    "approved_at": "2026-05-30 12:00:00",
    "created_at": "2026-05-30 11:00:00",
    "updated_at": "2026-05-30 12:00:00",
    "employer_profile": {
      "id": 1,
      "user_id": 1,
      "company_name": "Tech Co",
      "logo_url": null,
      "website": null,
      "phone": null,
      "location": null,
      "description": null
    },
    "category": {
      "id": 1,
      "name": "Software Development",
      "slug": "software-development",
      "description": null
    }
  }
}
```

### Paginated list
```json
{
  "data": [ ... ],
  "links": {
    "first": "http://localhost:8000/api/jobs?page=1",
    "last": "http://localhost:8000/api/jobs?page=3",
    "prev": null,
    "next": "http://localhost:8000/api/jobs?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "per_page": 15,
    "to": 15,
    "total": 35
  }
}
```

### Validation Error
```json
{
  "message": "The title field is required.",
  "errors": {
    "title": ["The title field is required."]
  }
}
```

### Authorization Error
```json
{
  "message": "Unauthorized. Required role: employer"
}
```
