# Vendor Compliance Portal

[![CI](https://github.com/SAHARIARSHOWMIK/vendor-compliance-portal/actions/workflows/ci.yml/badge.svg)](https://github.com/SAHARIARSHOWMIK/vendor-compliance-portal/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.3-8892bf.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20.svg)](https://laravel.com)
[![Tests](https://img.shields.io/badge/tests-129%20passing-brightgreen.svg)](#testing)

A full-stack Laravel 11 application for managing vendor onboarding, compliance document collection, review workflows, and expiry monitoring. Built as a portfolio project demonstrating enterprise Laravel patterns.

---

## Portfolio Highlights

This project is designed as a full-stack Laravel portfolio system, not a simple CRUD demo. It demonstrates role-based access control, private document storage, approval workflows, compliance scoring, scheduled expiry checks, CSV reporting, email/in-app notifications, and immutable audit logging.

## Features

- **5-role RBAC** — Super Admin, Compliance Admin, Reviewer, Vendor User, Auditor with middleware-enforced route groups and Policy-layer action gates
- **12-status vendor lifecycle** — draft → invited → registered → documents_pending → under_review → correction_required → partially_approved → fully_compliant → expiring_soon → non_compliant → suspended → archived
- **10-status document lifecycle** — with full version history (reupload never overwrites; old versions are snapshotted to `document_versions`)
- **Private file storage** — all vendor documents stored on a private disk, served only through authenticated controller actions, never via public URLs
- **Compliance engine** — deterministic score (0–100) and 8-status compliance derivation; recalculates on every document upload, review decision, and nightly expiry check
- **Review workflow** — 5 decision types (approve / reject / correction\_requested / need\_more\_info / escalate); append-only review history
- **Nightly expiry monitoring** — `php artisan compliance:check-expiry` with `--dry-run` support, scheduled via `routes/console.php`
- **7 report types** — compliance summary, missing documents, expiring documents, rejected documents, vendor onboarding, reviewer workload, audit trail — each with CSV export
- **Immutable audit log** — every significant system action recorded with actor/vendor snapshots that survive record deletion
- **Email notifications** — document review decisions and expiry warnings (uses `MAIL_MAILER=log` in development)

---

## Architecture

```
app/
├── Console/Commands/        # CheckDocumentExpiry (nightly scheduler)
├── Enums/                   # RoleName (5 roles with label() + role-set helpers)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/           # DashboardController, VendorController, AdminDocumentController, ReportController
│   │   ├── Auditor/         # AuditorDashboardController (read-only)
│   │   ├── Reviewer/        # ReviewQueueController
│   │   └── VendorPortal/    # VendorDocumentController, AcceptInvitationController
│   ├── Middleware/          # EnsureUserHasRole, EnsureVendorScopedAccess, RecordLastLogin
│   └── Requests/            # Per-action form requests with authorization
├── Mail/                    # DocumentReviewedMail, ExpiryWarningMail
├── Models/                  # 11 Eloquent models with relationships and domain helpers
├── Notifications/           # VendorInvitationNotification
├── Policies/                # VendorPolicy, VendorDocumentPolicy, ReviewPolicy
└── Services/
    ├── AuditService.php     # Centralised audit logging (actor/vendor snapshots, IP)
    ├── ComplianceService.php # Score calculation and 8-status derivation
    ├── DocumentService.php  # Upload, versioning, private-disk download
    ├── NotificationService.php # In-app + email notification dispatch
    ├── ReportService.php    # 7 report types with CSV streaming
    ├── ReviewService.php    # 5 decision types; triggers compliance recalc
    └── VendorService.php    # Vendor lifecycle transitions + audit logging

database/
├── factories/               # 6 model factories with state methods
├── migrations/              # 11 migrations in verified dependency order
└── seeders/
    ├── DocumentTypeSeeder.php  # 9 document types + 28 category requirements
    └── DemoSeeder.php          # 5 demo vendors + 9 user accounts

tests/Feature/
├── Auth/                    # AuthenticationTest (20 tests)
├── Compliance/              # ComplianceEngineTest (20 tests)
├── Dashboard/               # DashboardAndReportsTest (18 tests)
├── Document/                # DocumentUploadTest (18 tests)
├── Review/                  # ReviewWorkflowTest (18 tests)
├── Schema/                  # MigrationSchemaTest (12 tests)
└── Vendor/                  # VendorManagementTest (23 tests)
```

---

## Quick Start (Docker)

```bash
# 1. Clone and enter the project
git clone https://github.com/SAHARIARSHOWMIK/vendor-compliance-portal.git
cd vendor-compliance-portal

# 2. Copy and configure environment
cp .env.example .env
# Edit .env — set APP_KEY (or let step 4 generate it)

# 3. Start the containers
docker compose up -d --build

# 4. First-time setup (run once)
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed

# 5. Open the app
open http://localhost:8080
```

The app will be available at **http://localhost:8080**.

---

## Demo Credentials

All demo accounts use the password **`password`**.

| Role | Email | Access |
|------|-------|--------|
| Super Admin | `super.admin@demo.test` | Full system access |
| Compliance Admin | `compliance.admin@demo.test` | Vendor management + reports |
| Reviewer | `reviewer@demo.test` | Document review queue |
| Auditor | `auditor@demo.test` | Read-only vendor + audit log |
| Vendor (Alpha) | `vendor.alpha@demo.test` | Fully compliant vendor |
| Vendor (CyberNet) | `vendor.cybernet@demo.test` | NDA missing, docs under review |
| Vendor (BuildPro) | `vendor.buildpro@demo.test` | Insurance expiring in 7 days |
| Vendor (Noor) | `vendor.noor@demo.test` | Bank document rejected |
| Vendor (SecureGate) | `vendor.securegate@demo.test` | Multiple docs under review |

---

## Local Development (without Docker)

```bash
# PHP 8.3 + Composer + Node 20 required
composer install
npm ci && npm run build
cp .env.example .env
php artisan key:generate
# Configure DB_* in .env, then:
php artisan migrate --seed
php artisan serve
```

---

## Testing

```bash
# Run full test suite (129 tests across 7 test classes)
php artisan test

# Parallel (faster)
php artisan test --parallel

# Specific group
php artisan test tests/Feature/Compliance/
php artisan test tests/Feature/Review/

# Dry-run the expiry check command
php artisan compliance:check-expiry --dry-run
```

All tests use `RefreshDatabase` + `Storage::fake('vendor_documents')` + `Mail::fake()`. No external services required.

---

## Key Design Decisions

**Private file storage** — `vendor_documents` disk maps to `storage/app/private/vendor-documents`. Files are never served via public URLs; always streamed through `DocumentService::streamDownload()` after an auth + policy check.

**Version history is immutable** — reuploading a document snapshots the existing row into `document_versions` before updating `vendor_documents`. Old files are never deleted from disk.

**Compliance engine is deterministic** — no AI, no external APIs. The score formula is `(approved/required)*100 - (expired/required)*40 - (rejected/required)*20 - (expiring/required)*10`, capped at [0, 100].

**Suspended vendors are never auto-reinstated** — `ComplianceService::deriveVendorStatus()` guards against the compliance engine promoting a manually-suspended vendor back to active, even if all their documents are approved.

**Audit log is append-only** — `AuditLog` uses `$guarded = ['id']` (no mass-assignment restriction) but has no `update` or `delete` calls anywhere in the codebase. Actor name and vendor name are snapshotted at write time so audit entries survive user/vendor deletion.

**Delete route intentionally excluded** — `Route::resource('vendors', VendorController::class)->except(['destroy'])`. Vendors are archived, not deleted, to preserve the audit trail.

---

## Compliance Score Formula

```
score = (approved / required) × 100
      − (expired  / required) × 40   ← heaviest penalty
      − (rejected / required) × 20
      − (expiring / required) × 10
      (clamped to 0–100, rounded to nearest integer)
```

| Scenario | Score |
|----------|-------|
| All 4 docs approved | 100% |
| 3/4 approved, 1 rejected | 70% |
| 3/4 approved, 1 expired | 65% |
| 2/4 approved, 2 missing | 50% |
| All docs missing | 0% |

---

## Nightly Expiry Check

```bash
# Check what would change without making any DB writes
php artisan compliance:check-expiry --dry-run

# Run live (also triggered daily at 08:00 by the scheduler)
php artisan compliance:check-expiry
```

Thresholds (configurable in `.env`):

| Variable | Default | Effect |
|----------|---------|--------|
| `COMPLIANCE_EXPIRY_EARLY_WARNING_DAYS` | 60 | First in-app + email notification |
| `COMPLIANCE_EXPIRY_REMINDER_DAYS` | 30 | Second notification |
| `COMPLIANCE_EXPIRY_URGENT_DAYS` | 7 | Urgent notification, badge turns red |

---

## Stack

| Layer | Choice | Reason |
|-------|--------|--------|
| Backend | Laravel 11 / PHP 8.3 | Rich ecosystem, first-class auth/policy/queue |
| Frontend | Blade + Tailwind CSS | Avoids JS build complexity; Tailwind for rapid utility styling |
| Database | MySQL 8 | JSON columns for audit log, window function support |
| File storage | Local private disk | No S3 credentials needed to demo; swap by changing `FILESYSTEM_DISK` |
| Testing | PHPUnit via `php artisan test` | RefreshDatabase + Storage::fake + Mail::fake |
| CI | GitHub Actions | Free tier; matrix for multiple PHP versions |
| Containerisation | Docker Compose (PHP-FPM + Nginx + MySQL) | One-command local start |

---

## Resume Bullets

- Architected a **Laravel 11 vendor compliance portal** with 5-role RBAC, 12-status vendor lifecycle, 10-status document lifecycle, and a deterministic compliance scoring engine; **129 feature tests** with 0 failures
- Built a **private-disk document management system** enforcing file versioning (reupload snapshots previous versions), authenticated download (no public URLs), and per-document-type MIME/size validation
- Designed an **append-only audit trail** with actor/vendor snapshots surviving record deletion, accessible to Auditor role via a read-only portal and filterable CSV export
- Implemented a **nightly Artisan expiry-monitoring command** with `--dry-run` support, three configurable notification thresholds, and automatic compliance score recalculation
- Packaged with **Docker Compose** (PHP-FPM + Nginx + MySQL), **GitHub Actions CI**, and a seeder providing 5 demo vendors covering all compliance scenarios

---

## License

MIT
