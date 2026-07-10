# Changelog

## Consolidated stability and CI repair

- Upgraded the framework requirement to Laravel 12 and removed unused PHP packages that blocked secure dependency resolution.
- Repaired route/controller bindings, authorization traits, policy abilities, auditor report access, and vendor-scoped upload rules.
- Added context-aware authorization for initial uploads versus document replacements.
- Corrected file/expiry validation, private storage behavior, version-history auditing, and reviewer notifications.
- Normalized Composer and npm metadata, removed private registry URLs, and hardened the GitHub Actions workflow.
- Repaired PHPUnit environment configuration, unit-test discovery, branding assertions, vendor-portal redirects, and Blade component syntax.
- Removed stale Livewire references after simplifying the frontend dependency graph.
- Added source-level route, authorization, Blade, package-registry, and artifact checks before packaging.

## Portfolio platform upgrade

### Interface

- Replaced the basic dashboard styling with the VendorGuard enterprise operations design system.
- Added a responsive dark navigation shell, command bar, metric cards, pipelines, portfolio summaries, and mobile navigation.
- Redesigned the login, command center, vendor portfolio, vendor record, review queue, review workspace, vendor checklist, reports, audit log, and auditor dashboard.
- Added accessible statuses, risk indicators, progress bars, toast messages, confirmation prompts, and keyboard search focus.

### Operations

- Added portfolio search, score-band filters, risk filters, sorting, and portfolio summaries.
- Added reviewer queue search, risk/status filters, waiting-time signals, and workload summaries.
- Added richer dashboard analytics for compliance rate, review throughput, priority remediation, category composition, and expiry exposure.
- Added a user notification center with read and read-all workflows.
- Added auditor access to audit history and CSV export.
- Expanded controlled demo notifications and risk scenarios.

### Platform

- Added cache and queue schema support.
- Added SQLite-first Windows setup and persistent storage placeholders.
- Added queue worker and scheduler services to Docker Compose.
- Reworked Docker images into optimized frontend/dependency build stages.
- Updated CI to Node 24-based GitHub actions and Node.js 22.
- Added new notification feature tests, screenshots, deployment guidance, and verification records.
