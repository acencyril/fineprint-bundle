# Contributing

## The one rule: never state a fact the site did not declare

Every sentence in these pages is published under someone else's name. The wording may
be generic; it must never assert something about the publisher that the configuration
did not say ("we never see your card details", "no data leaves the EU", "certified").
If a clause needs a fact, add a configuration key, and if the law makes that fact
mandatory, add a check in `Configuration::crossCheck()` so a missing value stops the
build instead of reaching a visitor.

Each legal reference in the translations names its article. Change one only with the
text that supports it.

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

- A new text goes into both `translations/fineprint.fr.yaml` and `fineprint.en.yaml`
  (the tests fail on an untranslated key).
- A new configuration key goes into the README example and, if a fresh install needs
  it, into `recipe/config/packages/fineprint.yaml` (a test keeps the recipe valid).

## Especially welcome

- A clause that is wrong or out of date, with the article and its current version.
- A common case the terms do not cover yet (goods delivery, quotes), as an optional
  section behind a configuration key.
