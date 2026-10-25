# Fineprint

**The legal pages of a Symfony site, assembled from the clauses your law and your
business need.**

Legal notice, terms of sale, privacy policy and cookies, in French and English, under
`/{locale}/…`. You choose a **jurisdiction pack** (French law, or EU law for any member
state) and declare your **activities** (who you sell to, and what); Fineprint assembles
only the clauses that apply, fills them with your facts, and refuses to build if a
mention your law requires is missing.

*Documentation française : [README.fr.md](README.fr.md)*

```bash
composer require acencyril/fineprint-bundle
```

> **Not legal advice.** Fineprint writes generic, correct wording around the facts you
> declare. It certifies nothing. Have your pages reviewed by a lawyer before you rely
> on them, and add the terms particular to your business.

---

## The pages

| Page | French path | English path |
|---|---|---|
| Legal notice | `/fr/mentions-legales` | `/en/legal-notice` |
| Terms of sale | `/fr/cgv` | `/en/terms-of-sale` |
| Privacy policy | `/fr/confidentialite` | `/en/privacy` |
| Cookies | `/fr/cookies` | `/en/cookies` |

Each page can be switched off (it then has no route and answers 404), and its path set
per locale.

## Jurisdiction packs

A pack holds the clauses of the four pages, their texts in French and English, the
fields its law requires and the checks it runs. Set one with `jurisdiction`.

| Pack | For | Cites | Requires |
|---|---|---|---|
| `fr` | Sites under French law | LCEN, Code de la consommation, Code de commerce, GDPR, loi Informatique et Libertés (CNIL) | SIRET (`company_id`), publication director, host's name, address and phone, `governing_law: France`, a consumer mediator when you sell to consumers |
| `eu` | Any EU member state | EU law only: e-Commerce Directive, Consumer Rights Directive, Directives 2019/770 and 2019/771, ADR Directive, Late Payment Directive, GDPR, ePrivacy Directive, Rome I, Brussels I bis | `publisher.country` |

The `eu` pack states no national rule: your `governing_law` and country are shown as
you declare them, and national obligations beyond EU law (a register format, a
mandatory mediator, a cancellation button) are yours to add as sections, or as a pack
for your country. It has no ODR platform link: the EU platform closed in July 2025.

Any bundle or application can add a pack (`de`, `uk`, `us`…): see
[Write a jurisdiction pack](CONTRIBUTING.md#write-a-jurisdiction-pack).

## Activities

```yaml
activities: [b2b, b2c, digital, subscription, physical_goods, marketplace, services]
```

Declare at least `b2b` or `b2c`, then what you sell. Every clause declares the
activities it needs, so the terms only say what concerns you:

| Activity | Brings |
|---|---|
| `b2b` | prices excluding VAT, late payment penalties, no consumer withdrawal, liability limits, a court for disputes |
| `b2c` | prices including taxes, 14-day withdrawal and the model form, legal guarantees, mediation, consumer jurisdiction rules |
| `digital` | digital services, conformity guarantee for digital content, the withdrawal waiver |
| `services` | performance of services, proportional payment on withdrawal, the withdrawal waiver |
| `subscription` | renewal and cancellation (online cancellation for French consumers) |
| `physical_goods` | delivery, transfer of risk, returns, conformity guarantee for goods |
| `marketplace` | third-party sellers, trader status, ranking parameters |

The checks follow: a mediator is required only with `b2c` (in the `fr` pack), the
withdrawal waiver only with `b2c` and `digital` or `services`, ranking parameters only
with `marketplace`.

---

## Installation

With Symfony Flex the recipe registers the bundle and writes
`config/packages/fineprint.yaml` and `config/routes/fineprint.yaml`. **Replace every
`CHANGE ME` before going live**: these are your legal pages, published as written.

Without Flex:

```php
// config/bundles.php
Acencyril\FineprintBundle\FineprintBundle::class => ['all' => true],
```

```yaml
# config/routes/fineprint.yaml
fineprint:
    resource: .
    type: fineprint
```

The bundle needs the translator (`framework.translator`) and Twig.

## Configuration

```yaml
# config/packages/fineprint.yaml
fineprint:
    jurisdiction: fr                          # fr, eu, or a pack another bundle adds
    governing_law: 'France'                   # shown in the terms
    activities: [b2b, b2c, digital, subscription]

    site:
        name: 'Example'
        url: 'https://example.com'

    publisher:
        legal_name: 'Jeanne Martin'
        legal_form: 'entrepreneur individuel (micro-entreprise)'
        company_id: '123 456 789 00012'       # the SIRET in the "fr" pack
        vat_number: 'FR00123456789'           # optional
        # registration: 'RCS Paris 123 456 789'
        # share_capital: '1 000 €'
        address: '1 rue de l''Exemple, 75001 Paris'
        country: 'France'                     # required by the "eu" pack
        email: 'contact@example.com'
        phone: '+33 1 00 00 00 00'            # optional, but the LCEN asks professionals for one
        publication_director: 'Jeanne Martin' # required by the "fr" pack

    host:                                     # required by the "fr" pack, optional in "eu"
        name: 'Hosting SAS'
        address: '2 avenue du Serveur, 59100 Roubaix, France'
        phone: '+33 1 00 00 00 01'

    mediator:                                 # "fr": required with b2c; "eu": optional ADR body
        name: 'Consumer mediator'
        url: 'https://mediator.example'

    locales: [fr, en]                         # default
    parent_template: 'base.html.twig'         # your layout; must define "title" and "body"

    pages:
        cookies: false                        # switch a page off
        terms:
            path: { fr: 'conditions-de-vente', en: 'terms' }
            last_updated: '2026-10-25'
            sections:
                remove: [liability]
                override:
                    law: 'legal.law'                       # translation key, "messages" domain
                    payment: 'legal/payment.html.twig'     # or a template
                add:
                    - id: quotes
                      title: { fr: 'Devis', en: 'Quotes' }
                      body: { fr: 'Un devis vaut 30 jours.', en: 'A quote is valid for 30 days.' }
                      position: 'after:order'              # start, end, before:<id>, after:<id>

    terms:
        payment_provider: 'Stripe'
        withdrawal_waiver: true               # your checkout must collect the consent
        court: 'Paris'                        # courts for disputes with business clients
        subscription_cancellation: { fr: 'depuis votre espace client.', en: 'from your account page.' }
        # vat_exempt: true                    # "fr": TVA non applicable, art. 293 B du CGI
        # liability_limits: true              # towards business clients only
        # marketplace_ranking: { fr: '…', en: '…' }  # required with the marketplace activity

    privacy:
        # controller: 'Jeanne Martin'         # defaults to publisher.legal_name
        # contact_email: 'privacy@example.com'
        # dpo_email: 'dpo@example.com'
        # authority: { name: '…', url: '…' }  # "eu": your lead supervisory authority
        host_is_processor: true               # lists the host under the key "host"
        processors:
            mail:   { name: 'Brevo', role: email, country: 'France' }
            stripe: { name: 'Stripe', role: payment, country: 'Ireland' }
            ai:
                name: 'AI provider'
                role: ai                      # hosting, email, payment, ai, analytics, other
                country: 'United States'
                outside_eu: true
                safeguards: { fr: 'clauses contractuelles types', en: 'standard contractual clauses' }
        processing:
            - purpose: { fr: 'Traiter les commandes', en: 'Processing orders' }
              data: { fr: 'nom, e-mail, adresse de facturation', en: 'name, email, billing address' }
              legal_basis: contract           # consent, contract, legal_obligation,
                                              # vital_interests, public_task, legitimate_interests
              retention: { fr: '10 ans (pièces comptables)', en: '10 years (accounting records)' }
              processors: [host, stripe, mail]
            - purpose: 'Server logs'
              legal_basis: legitimate_interests
              legitimate_interest: { fr: 'protéger le site', en: 'protecting the site' }
              retention: { fr: '90 jours', en: '90 days' }
              processors: [host]

    cookies:
        # manage_url: '/cookies/choices'     # required as soon as one cookie is not essential
        list:
            - name: 'PHPSESSID'
              type: essential                 # essential, preferences, analytics, marketing
              purpose: { fr: 'Session', en: 'Session' }
              duration: { fr: 'Fin de la session', en: 'End of session' }

    # options: { … }                          # free settings for third-party packs
```

Any text can be given once (`'Server logs'`) or per locale (`{ fr: …, en: … }`). Values
may come from `%env()%`; format checks skip them.

### Checks that stop the build

The core checks the shape (paths per locale, who you sell to, processors, sections);
the pack checks its law. A broken configuration stops `cache:clear` with every problem
listed, each naming the key and the rule:

```
Invalid Fineprint configuration:
- fineprint.host.phone is required by the "fr" pack.
- fineprint.activities includes b2c: declare a consumer mediator under fineprint.mediator (Code de la consommation, L612-1).
- fineprint.privacy.processors.ai sends data outside the EEA: say under "safeguards" what frames the transfer (GDPR, art. 13.1.f).
```

Both built-in packs also check: legitimate interests described, processors declared,
transfers framed, non-essential cookies withdrawable (`cookies.manage_url`), a non-empty
privacy page.

### Things only you can do

- **Withdrawal waiver**: the terms say the consumer's express consent and
  acknowledgement are collected when ordering. Your checkout must collect them (an
  unticked checkbox, recorded with the order).
- **Online cancellation** (`fr`, consumers): the terms say a cancellation function
  exists (L215-1-1); your site must offer it.

## Sections

Each page is a list of clauses with ids (`law`, `withdrawal`, `price_b2b`…). The ids
of a pack are in its class (`src/Pack/Fr/FrPack.php`, `src/Pack/Eu/EuPack.php`) and on
the page itself: every section has `id="…"` (underscores become dashes).

- **`remove: [ids]`** leaves clauses out.
- **`override: {id: …}`** replaces a clause's text and keeps its title. The value is a
  translation key in your `messages` domain, a template name ending in `.twig`, or
  `{body|template, title, domain}`.
- **`add: [{id, title, body|template, position}]`** inserts your own sections. `body`
  is plain text (blank lines start a paragraph); `position` is `start`, `end` (default),
  `before:<id>` or `after:<id>`. A section anchored to a clause your activities leave
  out goes to the end.

Unknown ids, taken ids and empty pages are refused at build time.

Templates (an override or an added section) get the page's context: `site`,
`publisher`, `host`, `mediator`, `fineprint` (the whole configuration), `section`,
`params`, `domain` and `locale`.

## Footer links

```twig
{{ include('@Fineprint/footer_links.html.twig') }}

{% for link in fineprint_links() %}<a href="{{ link.url }}">{{ link.label }}</a>{% endfor %}

<a href="{{ fineprint_url('privacy') }}">…</a>   {# null when disabled #}
```

## Sitemap

`Acencyril\FineprintBundle\Sitemap\FineprintUrls` (also `fineprint.urls`) lists the
enabled pages:

```php
foreach ($fineprintUrls->all() as $url) {
    // ['loc' => 'https://example.com/fr/cgv', 'page' => 'terms', 'locale' => 'fr',
    //  'alternates' => ['fr' => '…/fr/cgv', 'en' => '…/en/terms-of-sale']]
}
```

URLs are built on `site.url`, so a console command gets the same list as a request.

## Overriding templates

The Symfony way, in `templates/bundles/FineprintBundle/`:

- `layout.html.twig` wraps every page: override it if your base template uses other
  block names than `title` and `body`.
- `terms.html.twig` (and the other three) extend `page.html.twig`: override one to
  change one page, with the blocks `fineprint_sections` and `fineprint_extra`.
- `_section.html.twig` renders one section; `clauses/*.html.twig` are the clauses
  built from structured data (publisher, host, processing, processors, cookie list,
  withdrawal form, guarantees, mediator).
- Wording: override any key of a pack's translation domain (`fineprint_fr`,
  `fineprint_eu`) in your own `translations/fineprint_fr.fr.yaml`.

## Upgrading from 0.1

0.2 replaces the configuration:

| 0.1 | 0.2 |
|---|---|
| — | `jurisdiction`, `governing_law` (required) |
| `terms.audience: b2b/b2c/both` | `activities: [b2b]`, `[b2c]`, `[b2b, b2c]` |
| `terms.digital_services`, `terms.subscriptions` | activities `digital`, `subscription` |
| `terms.subscriptions.cancellation` | `terms.subscription_cancellation` |
| `terms.immediate_digital_content` | `terms.withdrawal_waiver` |
| `publisher.siret` | `publisher.company_id` |
| `privacy.activities` | `privacy.processing` |
| `pages.<page>.sections: [{title, body}]` | `pages.<page>.sections.add: [{id, title, body}]` |
| translation domain `fineprint` | `fineprint_fr` (and `fineprint_eu`) |
| one template per page with a block per section | `page.html.twig` + sections; `clauses/*.html.twig` |

## Flex recipe

The recipe lives in [`recipe/`](recipe), in the format of
[symfony/recipes-contrib](https://github.com/symfony/recipes-contrib), ready to be
submitted as `acencyril/fineprint-bundle/0.2/`. Until it is merged, Flex will not apply
it: follow the "Without Flex" steps and copy `recipe/config/` into your `config/`.

## Tests

```bash
composer install
vendor/bin/phpunit
```

## Licence

MIT.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md), including how to write a jurisdiction pack. A
clause that is wrong, outdated or missing for a common case is the most useful report:
say which text and which article.
