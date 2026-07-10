# Security Model

- Store compliance evidence outside the public web root.
- Serve files only after authentication, authorization, and vendor-scope checks.
- Use least-privilege role assignments.
- Record sensitive lifecycle actions and reviewer decisions.
- Keep production `.env`, database files, and uploaded evidence out of source control.
- Use malware scanning and content-disarm controls for production uploads.
- Encrypt object storage and databases at rest.
- Enforce HTTPS, secure cookies, rate limiting, and session expiry.
- Rotate credentials and review audit exports regularly.
