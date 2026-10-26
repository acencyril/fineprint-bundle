# Changelog

All notable changes to Fineprint.
Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
versioning follows [SemVer](https://semver.org/).

## [Unreleased]

## [0.2.1] — 2026-10-26

### Fixed
- Symfony 8: required texts (a section's `title`, a processing's `purpose` and `retention`,
  a cookie's `purpose` and `duration`) no longer also carry a default value, which Symfony 8
  refuses ("cannot be required and have a default value").

## [0.2.0] — 2026-10-25

Fineprint becomes a clause assembler. The 0.1 configuration is replaced: see
"Upgrading from 0.1" in the README.

### Added
- **Jurisdiction packs** (`jurisdiction`): the clauses, texts, required fields and checks
  of a law, discovered through `PackInterface` (autoconfigured, tag `fineprint.pack`) so
  any bundle can add one. Two packs ship:
  - `fr`: the 0.1 content (LCEN, Code de la consommation, Code de commerce, CNIL);
  - `eu`: any EU member state, citing EU law only (e-Commerce, Consumer Rights, sale of
    goods and digital content, ADR and Late Payment Directives, GDPR, ePrivacy, Rome I,
    Brussels I bis). No ODR platform link: the platform closed in July 2025.
- **Activities** (`activities`: b2b, b2c, digital, subscription, physical_goods,
  marketplace, services): every clause declares the activities it applies to and only
  the relevant ones are assembled. New clauses for delivery of goods, services and
  marketplaces.
- **Sections** per page: `remove`, `override` (translation key or template) and `add`
  (with a position). Unknown or taken ids are refused at build time.
- `governing_law` (required), shown in the terms.
- Pack checks that follow the activities: mediator only with b2c (`fr`), withdrawal
  waiver only with b2c and digital or services, ranking parameters with marketplace.
- All configuration errors reported together when the container is built.
- "Write a jurisdiction pack" in CONTRIBUTING.

### Changed
- `publisher.siret` → `publisher.company_id`; `privacy.activities` →
  `privacy.processing`; `terms.immediate_digital_content` → `terms.withdrawal_waiver`;
  `terms.subscriptions.cancellation` → `terms.subscription_cancellation`.
- `terms.audience`, `terms.digital_services` and `terms.subscriptions` are replaced by
  `activities`.
- Texts live in one translation domain per pack (`fineprint_fr`, `fineprint_eu`).
- Pages render through `page.html.twig` and `_section.html.twig`; structured clauses are
  templates in `clauses/`.
- `host` is optional in the core, required by the `fr` pack.

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
