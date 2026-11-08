# Security policy

## Supported versions

Only the latest minor version receives security fixes.

| Version | Supported |
|---------|-----------|
| 0.2.x   | ✅        |
| < 0.2   | ❌        |

Fineprint runs on PHP 8.1 and later and on Symfony 6.4, 7.x and 8.x. The oldest and newest
lines are tested on each push (PHP 8.1 with Symfony 6.4, PHP 8.2 with Symfony 7.4, PHP 8.4,
PHP 8.5 with Symfony 8; see `.github/workflows/tests.yml`), as well as `composer audit` on
the dependencies.

## Reporting a vulnerability

Please **do not open a public issue** for a vulnerability.

Report it privately through GitHub: on this repository, open the **Security** tab and click
**Report a vulnerability**
(<https://github.com/acencyril/fineprint-bundle/security/advisories/new>).
If you cannot use GitHub, write to **cdelage.dev@gmail.com** with the subject "Security", in
English or French.

Include what you can: the affected version, the configuration, the steps to reproduce, and
what an attacker gains. You will get an answer within 72 hours. A confirmed vulnerability is
fixed within 7 days. Once a fix is released, a GitHub security advisory is published with
credit to you, unless you prefer otherwise. This is best effort: Fineprint has a single
maintainer, and the policy says so rather than promise more.

In scope: a legal page that renders markup or script it was not given as a template (the
facts you declare and the translations are escaped), a page switched off that still answers,
or a request that changes what a page says. A clause that is legally wrong or outdated is not
a security issue: open an issue for it (see `CONTRIBUTING.md`).

The full disclosure policy (timelines, safe harbour, scope):
<https://mirelabs.fr/en/responsible-disclosure>.

## Versions

Fineprint follows [SemVer](https://semver.org/). While it is in 0.x, a minor version
(0.2 → 0.3) may change behaviour or configuration: every such change is in `CHANGELOG.md`
and, when you have something to do, in the README ("Upgrading from …"). A patch version
(0.2.0 → 0.2.1) never does.
