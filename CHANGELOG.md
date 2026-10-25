# Changelog

All notable changes to Fineprint.
Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
versioning follows [SemVer](https://semver.org/).

## [Unreleased]

## [0.1.0] — 2026-10-25

### Added
- Four pages under `/{locale}/`, in French and English: legal notice, terms of sale,
  privacy policy, cookies. Each one can be switched off (no route, 404) and its path
  set per locale.
- One configuration for the publisher, the host, the mediator and the site.
- Privacy policy built from declared processing activities and processors; data subject
  rights and the CNIL contact always included.
- Terms of sale for businesses, consumers or both, with optional sections: digital
  services, subscriptions, withdrawal waiver for content delivered at once, liability
  limits.
- Build-time checks for mandatory mentions (consumer mediator, legitimate interest,
  transfer safeguards, undeclared processors, cookie consent withdrawal).
- `fineprint_links()`, `fineprint_url()`, `@Fineprint/footer_links.html.twig`.
- `FineprintUrls`: the enabled pages' URLs, for a sitemap.
- Flex recipe in `recipe/`.
- Tests (PHPUnit) and CI.
