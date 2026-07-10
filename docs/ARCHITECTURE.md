# Architecture

```text
Browser / Vendor Portal
        │
        ▼
Nginx → Laravel Web Application
        │
        ├── Authentication and RBAC middleware
        ├── Vendor-scoped authorization policies
        ├── Vendor, evidence, version, and review services
        ├── Compliance score and lifecycle engine
        ├── Notification and report services
        └── Append-only audit service
        │
        ├── MySQL / SQLite
        ├── Private document storage / S3-compatible disk
        ├── Queue worker
        └── Scheduler / expiry monitoring
```

The application uses service classes for domain workflows rather than placing compliance behavior directly in controllers. Evidence storage is abstracted behind a dedicated private Laravel filesystem disk. Every role enters through the same authenticated application but receives route, policy, and vendor-scope restrictions.
