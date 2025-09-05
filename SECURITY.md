# Security Policy

Thanks for helping keep **Wake on LAN (Nextcloud app)** secure!  
Please **do not** open public issues for security reports.

## Supported Versions

| Version | Supported |
|--------:|:---------:|
| main / unreleased | ✅ |
| latest release     | ✅ |
| older releases     | ❌ |

> We generally patch the current release and `main`. Older tags may receive fixes only if the change is trivial.

## Reporting a Vulnerability

**Preferred:** Use GitHub’s private advisory workflow

1. Go to **Security → Advisories → Report a vulnerability**  
   (URL: `https://github.com/FungY911/wakeonlan-nextcloud/security/advisories/new`)
2. Provide a clear, reproducible report (see “What to include” below).
3. We’ll coordinate a fix and a coordinated disclosure window.

### What to include
- Affected version/commit and Nextcloud version
- Environment (PHP, DB, web server / proxy)
- Vulnerability class (e.g., RCE, CSRF bypass, authz bypass, SSRF)
- Impact and severity (your CVSS estimate if you have one)
- Reproducer: steps, PoC request/response, or minimal payload
- Any mitigation/workaround you’re aware of

### Scope
This policy covers **this app only**. Issues in Nextcloud core or other apps should be reported to their respective trackers/policies.

### Our process & timelines
- **Acknowledgement:** within **3 business days**
- **Triage & validation:** typically **7–14 days**
- **Fix & release:** depends on complexity; for critical issues we prioritize an expedited release
- **Credit:** we’ll thank reporters in release notes unless you prefer anonymity

### Safe harbor
We support good-faith research and will not pursue action for testing that:
- avoids privacy violations, data destruction, or service disruption
- respects rate limits and legal boundaries
- uses private reporting (no public disclosure until a fix is available)

