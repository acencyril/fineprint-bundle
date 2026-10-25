# Fineprint

**The legal pages of a Symfony site, written once and correct for French law.**

Legal notice, terms of sale, privacy policy and cookies, in French and English, under
`/{locale}/…`. The bundle supplies the wording the law asks for; your configuration
supplies the facts. A page never states something you did not declare.

*Documentation française : [README.fr.md](README.fr.md)*

```bash
composer require acencyril/fineprint-bundle
```

---

## What you get

| Page | French path | English path |
|---|---|---|
| Legal notice (LCEN) | `/fr/mentions-legales` | `/en/legal-notice` |
| Terms of sale | `/fr/cgv` | `/en/terms-of-sale` |
| Privacy policy (GDPR) | `/fr/confidentialite` | `/en/privacy` |
| Cookies | `/fr/cookies` | `/en/cookies` |

- **Each page can be switched off**: it then has no route and answers 404.
- **Paths are configurable**, per locale.
- **The privacy page is built from the processing activities you declare**: purpose,
  data, legal basis, retention, processors. Data subject rights and the CNIL contact
  are always there.
- **The terms adapt to who you sell to** (businesses, consumers or both): withdrawal
  right and its model form, legal guarantees, mediator, consumer court rules, late
  payment penalties between businesses. Optional sections for digital services,
  subscriptions, the withdrawal waiver for content delivered at once, and liability
  limits.
- **Footer links** to the enabled pages, and a **service listing their URLs** for your
  sitemap.
- **Mistakes are caught when the container is built, not by a visitor.** A consumer
  sale without a mediator, an undeclared processor, a transfer outside the EU with no
  safeguard, an analytics cookie with no way to withdraw consent: each one stops
  `cache:clear` with a message naming the key and the rule.

## What it does not do

It is not legal advice and it does not certify anything. It writes generic, correct
wording around **your** facts. Read your pages before publishing them; if your
business has particular terms (deliveries, quotes, SLAs), add them as sections.

No SEO, no consent banner, no sitemap file: the bundle exposes its URLs and stops there.

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
    site:
        name: 'Example'
        url: 'https://example.com'

    publisher:
        legal_name: 'Jeanne Martin'
        legal_form: 'entrepreneur individuel (micro-entreprise)'
        siret: '123 456 789 00012'
        vat_number: 'FR00123456789'           # optional
        # registration: 'RCS Paris 123 456 789'  # companies on a register
        # share_capital: '1 000 €'               # companies with share capital
        address: '1 rue de l''Exemple, 75001 Paris, France'
        email: 'contact@example.com'
        phone: '+33 1 00 00 00 00'            # optional, but the LCEN asks professionals for one
        publication_director: 'Jeanne Martin'

    host:                                     # name, address and phone are all required
        name: 'Hosting SAS'
        address: '2 avenue du Serveur, 59100 Roubaix, France'
        phone: '+33 1 00 00 00 01'

    mediator:                                 # required when you sell to consumers
        name: 'Consumer mediator'
        url: 'https://mediator.example'

    locales: [fr, en]                         # default
    parent_template: 'base.html.twig'         # your layout; must define "title" and "body"

    pages:
        cookies: false                        # switch a page off
        terms:
            path: { fr: 'conditions-de-vente', en: 'terms' }
            last_updated: '2026-10-25'
            sections:                         # extra sections, plain text
                - title: { fr: 'Livraison', en: 'Delivery' }
                  body:
                      fr: "Premier paragraphe.\n\nSecond paragraphe."
                      en: "First paragraph.\n\nSecond paragraph."

    terms:
        audience: both                        # b2b (default), b2c or both
        vat_exempt: false                     # true prints "TVA non applicable, art. 293 B du CGI"
        payment_provider: 'Stripe'
        digital_services: true
        subscriptions:
            cancellation: { fr: 'depuis votre espace client.', en: 'from your account page.' }
        immediate_digital_content: true       # withdrawal waiver (L221-28)
        liability_limits: true                # towards business clients only
        court: 'Paris'                        # courts for disputes with business clients

    privacy:
        # controller: 'Jeanne Martin'         # defaults to publisher.legal_name
        # contact_email: 'privacy@example.com'  # defaults to publisher.email
        # dpo_email: 'dpo@example.com'
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
        activities:
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
```

Any text can be given once (`'Server logs'`) or per locale (`{ fr: …, en: … }`). Values
may come from `%env()%`.

### Rules that stop the build

| You declared | What is missing |
|---|---|
| `terms.audience: b2c` or `both` | a `mediator` (Code de la consommation, L612-1) |
| an activity on `legitimate_interests` | its `legitimate_interest` (GDPR, art. 13.1.d) |
| a processor with `outside_eu: true` | its `safeguards` (GDPR, art. 13.1.f) |
| an activity naming a processor | that processor under `privacy.processors` |
| a cookie that is not `essential` | `cookies.manage_url`, to withdraw consent |
| the privacy page enabled | at least one activity |
| a locale | a path for it on every enabled page |

### Things only you can do

- **Withdrawal waiver**: with `immediate_digital_content`, the terms say the consumer's
  express consent and acknowledgement are collected when ordering. Your checkout must
  actually collect them (an unticked checkbox, recorded with the order).
- **Online cancellation**: consumers must be able to cancel a subscription online
  (L215-1-1). The terms say so; your site must offer it.

## Footer links

```twig
{{ include('@Fineprint/footer_links.html.twig') }}

{# or your own markup #}
{% for link in fineprint_links() %}<a href="{{ link.url }}">{{ link.label }}</a>{% endfor %}

{# one page, null when disabled #}
<a href="{{ fineprint_url('privacy') }}">…</a>
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

## Overriding

Templates override the Symfony way, in `templates/bundles/FineprintBundle/`. Extend the
original with `@!Fineprint` and replace only the block you need:

```twig
{# templates/bundles/FineprintBundle/terms.html.twig #}
{% extends '@!Fineprint/terms.html.twig' %}
{% block fineprint_liability %}<section>…your own clause…</section>{% endblock %}
```

- `layout.html.twig`: wraps every page; override it if your base template uses other
  block names than `title` and `body`.
- Every section is a block: `fineprint_publisher`, `fineprint_host`,
  `fineprint_withdrawal`, `fineprint_activities`, `fineprint_rights`, … Add content
  with `fineprint_extra`, or with `pages.<page>.sections` in the configuration.
- Wording: override any key of the `fineprint` translation domain in your own
  `translations/fineprint.fr.yaml`.

## Flex recipe

The recipe lives in [`recipe/`](recipe), in the format of
[symfony/recipes-contrib](https://github.com/symfony/recipes-contrib), ready to be
submitted as `acencyril/fineprint-bundle/0.1/`. Until it is merged, Flex will not apply
it: follow the "Without Flex" steps and copy `recipe/config/` into your `config/`.

## Tests

```bash
composer install
vendor/bin/phpunit
```

## Licence

MIT.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). A clause that is wrong, outdated or missing for
a common case is the most useful report: say which text and which article.
