## Sprint Plan

> **Wagih starts first (Sprint 1 alone)** — his auth API and Vue setup unblock the rest of the team. Everyone works in parallel from Sprint 2 onward.

---

### Sprint 1 — Foundation _(Week 1 & 2)_

**Only Wagih works this sprint.** He sets up both ends of the stack so the team can pull a working base.

| Developer | Backend tasks                                                                                                                                        | Frontend tasks                                                                                             |
| --------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- |
| **Wagih** | Laravel project + Sanctum auth (register/login/logout), roles & policies middleware, `users` / `employer_profiles` / `candidate_profiles` migrations | Vue 3 + Vite + Pinia setup, register & login pages, Pinia auth store, route guards, shared navbar & layout |
| Ayman     | ⏳ waiting                                                                                                                                           | ⏳ waiting                                                                                                 |
| Shalaby   | ⏳ waiting                                                                                                                                           | ⏳ waiting                                                                                                 |
| Fathy     | ⏳ waiting                                                                                                                                           | ⏳ waiting                                                                                                 |

**Sprint 1 deliverables:**

- Working auth API (register, login, logout)
- Register & login pages live in Vue
- Role-based routing (employer / candidate / admin dashboards)
- All user & profile migrations
- Dev environment `.env` shared with the team

> Wagih merges `feature/wagih-auth` → `develop`. Everyone pulls and begins Sprint 2.

---

### Sprint 2 — Core Features _(Week 3 & 4)_

All 4 developers work in parallel on their own module.

| Developer   | Backend tasks                                                                              | Frontend tasks                                                                                           |
| ----------- | ------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------- |
| **Wagih**   | Employer & candidate profile APIs, file upload service (logos, resumes to S3)              | Employer profile edit page, candidate profile edit page                                                  |
| **Ayman**   | Job listings CRUD API, categories API, `job_listings` migration, Scout + Meilisearch setup | Public job listing page with filter sidebar, job detail page                                             |
| **Shalaby** | Applications API (submit, cancel), comments API, `applications` & `comments` migrations    | Application submit modal (resume upload + contact info), my applications page, comment section component |
| **Fathy**   | Admin approve/reject job API, admin user suspend/ban API                                   | Admin panel — pending jobs queue, admin panel — user management                                          |

**Sprint 2 deliverables:**

- Jobs can be created, searched, and filtered
- Candidates can apply for jobs
- Admin can approve or reject listings
- Profile pages (employer & candidate) complete
- Comments working on job detail page

> Integration test on `develop` → fix any merge conflicts → Sprint 3.

---

### Sprint 3 — Payments & Notifications _(Week 5 & 6)_

Close the money loop and connect all the status flows.

| Developer   | Backend tasks                                                                                                             | Frontend tasks                                                                                          |
| ----------- | ------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------- |
| **Wagih**   | PHPUnit feature tests for auth & profiles, advanced search query scopes (salary range, date posted)                       | Bug fixes & polish on auth and profile UI                                                               |
| **Ayman**   | Job listing tests                                                                                                         | Employer manage listings page (edit, close, delete, status badge), create/edit job form with all fields |
| **Shalaby** | Laravel Notifications (email + DB channels), notifications API (list & mark-as-read), accept/reject application endpoints | Notifications dropdown component, employer applications inbox (accept/reject buttons)                   |
| **Fathy**   | Stripe integration + webhook handler, PayPal integration + webhook handler, `payments` migration                          | Payment checkout flow UI, accept → pay → reveal candidate contact flow                                  |

**Sprint 3 deliverables:**

- Employer can accept a candidate and complete payment
- Candidate contact details revealed after successful payment
- Email & in-app notifications sent on all status changes
- Employer manage listings UI complete
- Core platform is fully functional end-to-end

> Full QA pass on `develop` → fix bugs → Sprint 4 (bonus features).

---

### Sprint 4 — Bonus Features & Polish _(Week 7 & 8)_

Optional enhancements and final release preparation.

| Developer   | Backend tasks                                                      | Frontend tasks                                                                          |
| ----------- | ------------------------------------------------------------------ | --------------------------------------------------------------------------------------- |
| **Wagih**   | LinkedIn OAuth integration                                         | LinkedIn connect button on candidate profile page, end-to-end tests & final code review |
| **Ayman**   | Resume database search API (search candidates by skill/experience) | Employer candidate pool search UI, search UX polish & empty states                      |
| **Shalaby** | In-platform application form API (custom questions per listing)    | Custom application form UI, notification preferences page                               |
| **Fathy**   | Analytics endpoint (views, applicant count, conversion rate)       | Employer analytics charts dashboard, admin comment moderation UI, demo data seeders     |

**Sprint 4 deliverables:**

- Employer analytics dashboard live
- Resume database search working (with candidate opt-in)
- LinkedIn profile connect on registration
- All bonus features complete
- Demo data seeded for presentation
- `develop` merged → `main` — production release

---

## License

MIT
