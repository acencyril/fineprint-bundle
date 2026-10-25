# Contributing

## The one rule: never state a fact the site did not declare

Every sentence in these pages is published under someone else's name. The wording may
be generic; it must never assert something about the publisher that the configuration
did not say ("we never see your card details", "no data leaves the EU", "certified").
If a clause needs a fact, read it from the configuration, and if the law makes that
fact mandatory, make the pack require it so a missing value stops the build instead of
reaching a visitor.

Each legal reference in the translations names its article. Change one only with the
text that supports it. Fineprint is not legal advice, and its texts are no substitute
for a review by a lawyer of the country concerned: say in your pull request what you
checked, and against which version of the law.

## Setting up

```bash
git clone https://github.com/acencyril/fineprint-bundle
cd fineprint-bundle
composer install
vendor/bin/phpunit
```

To try it in an application, a path repository avoids round trips through GitHub:

```json
{ "repositories": [ { "type": "path", "url": "../fineprint-bundle" } ] }
```

```bash
composer require acencyril/fineprint-bundle:@dev
```

## Before a pull request

```bash
vendor/bin/phpunit
composer validate --strict
```

- A new text goes into both languages of its pack (`TranslationKeysTest` fails on a
  key missing in one of them, or referred to by a clause and missing in both).
- A new configuration key goes into the README example and, if a fresh install needs
  it, into `recipe/config/packages/fineprint.yaml` (a test runs both through the pack).

## Write a jurisdiction pack

A pack is a class and its texts. It can live in this repository (`src/Pack/<Code>/`)
or in any bundle or application: every service implementing `PackInterface` is picked
up (autoconfigured, tag `fineprint.pack`), and the site selects it with
`fineprint.jurisdiction`.

### 1. The class

```php
namespace App\Fineprint;

use Acencyril\FineprintBundle\Pack\AbstractPack;
use Acencyril\FineprintBundle\Pack\GdprChecks;

final class DePack extends AbstractPack
{
    use GdprChecks; // the GDPR article 13 checks, for an EU member state

    public function name(): string
    {
        return 'de'; // fineprint.jurisdiction: de; texts in the domain "fineprint_de"
    }

    public function clauses(): array
    {
        return [
            $this->clause('imprint', 'legal_notice', title: 'legal_notice.imprint.title', template: '@Fineprint/clauses/publisher.html.twig'),
            $this->clause('withdrawal', 'terms', title: 'terms.withdrawal.title', body: 'terms.withdrawal.body', activities: ['b2c']),
            $this->clause('late_payment', 'terms', body: 'terms.payment.late', activities: ['b2b']),
            // …
        ];
    }

    public function requiredFields(array $config): array
    {
        return ['publisher.registration', 'publisher.vat_number'];
    }

    public function validate(array $config): array
    {
        $errors = $this->gdprErrors($config);
        if (self::has($config, 'b2c') && !$config['mediator']['enabled']) {
            $errors[] = 'fineprint.mediator: … (cite the article).';
        }

        return $errors;
    }
}
```

The class is instantiated without arguments at build time, to validate the
configuration before any page is served: its constructor takes no dependencies.

### 2. The clauses

`$this->clause($id, $page, …)` builds a `Clause` that declares its pack. Its options:

| Option | Meaning |
|---|---|
| `title` | translation key of the heading; omit it for a clause that continues the previous one |
| `body` | translation key of the text (blank lines start a paragraph), **or** |
| `template` | a Twig template, for text built from structured data |
| `activities` | all of them are required (`['b2c', 'physical_goods']`) |
| `anyOf` | at least one of them is required (`['digital', 'services']`) |
| `excludes` | none of them may be present (`['b2c']` for a business-only variant) |
| `when` | configuration conditions: `'terms.court'` (truthy), `'!terms.vat_exempt'` (falsy), `'page:privacy'` (enabled) |
| `link` | a page whose link ends the last paragraph |

Clauses are shown in the order returned, page by page. A variant that depends on the
audience is usually two or three clauses with `activities`/`excludes` (see
`price_b2b`, `price_b2c`, `price_both` in `FrPack`).

Ids are public: sites use them in `sections.remove` and `sections.override`, and they
become the sections' HTML ids. Keep the ids of the built-in packs where the clause has
the same role (`withdrawal`, `law`, `privacy.rights`…), so a site can switch pack
without rewriting its sections.

### 3. The texts

One file per locale, `fineprint_<name>.<locale>.yaml`, in a `translations/` directory
your bundle registers (a bundle's own `translations/` is registered automatically).
Every pack defines:

- `page.<page>.title`, `.link`, `.link_inline` and `.description` for the four pages;
- the `label.*`, `privacy.*`, `cookies.*` and `terms.withdrawal_form.*` keys used by the
  shared templates in `templates/clauses/`, if it uses them;
- its clauses' titles and bodies.

Bodies may use the parameters `%site%`, `%url%`, `%seller%`, `%email%`, `%country%`,
`%law%`, `%court%`, `%provider%`, `%ranking%`, `%cancellation%`, `%controller%`,
`%contact%`, `%authority%` and `%authority_url%`.

### 4. Required fields and checks

`requiredFields()` returns configuration paths that must be filled; Fineprint reports
the missing ones as `fineprint.<path> is required by the "<name>" pack.` `validate()`
returns any other problem, as a message that names the key and the rule (and the
article). Values set through `%env()%` reach both as `AbstractPack::DYNAMIC`:
`filled()` accepts it, and a format check should skip it with `dynamic()`.

Free settings a pack needs and the core does not know go under `fineprint.options`.

### 5. Tests

Copy what `PackValidationTest` and `PackMatrixTest` do for `fr` and `eu`: each rule of
the pack refused with its message, a valid configuration accepted, and every page
rendered for each mix of activities, in every locale, with no untranslated key. For a
pack in this repository, add it to those tests and to `TranslationKeysTest`.

## Especially welcome

- A clause that is wrong or out of date, with the article and its current version.
- A pack for another country, reviewed by someone who practises its law.
- A common case the packs do not cover yet, as a clause behind an activity or a
  configuration condition.
