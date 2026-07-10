# Contributing

1. Create a feature branch from `main`.
2. Keep domain behavior in services and authorization in middleware/policies.
3. Add or update feature tests for workflow changes.
4. Run `php artisan test`, `npm run build`, and `npm audit --audit-level=high`.
5. Do not commit `.env`, private evidence, databases, logs, `vendor/`, or `node_modules/`.
6. Open a pull request describing the workflow, security, migration, and UI impact.
