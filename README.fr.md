# Fineprint

**Les pages légales d'un site Symfony, écrites une fois et justes en droit français.**

Mentions légales, CGV, politique de confidentialité et cookies, en français et en
anglais, sous `/{locale}/…`. Le bundle fournit la rédaction que la loi demande ; votre
configuration fournit les faits. Une page n'affirme jamais ce que vous n'avez pas déclaré.

*English documentation: [README.md](README.md)*

```bash
composer require acencyril/fineprint-bundle
```

---

## Ce que vous obtenez

| Page | Chemin français | Chemin anglais |
|---|---|---|
| Mentions légales (LCEN) | `/fr/mentions-legales` | `/en/legal-notice` |
| Conditions générales de vente | `/fr/cgv` | `/en/terms-of-sale` |
| Politique de confidentialité (RGPD) | `/fr/confidentialite` | `/en/privacy` |
| Cookies | `/fr/cookies` | `/en/cookies` |

- **Chaque page peut être désactivée** : elle n'a alors plus de route et répond 404.
- **Les chemins sont configurables**, par langue.
- **La page de confidentialité est construite à partir des traitements déclarés** :
  finalité, données, base légale, durée de conservation, sous-traitants. Les droits des
  personnes et le contact de la CNIL y figurent toujours.
- **Les CGV s'adaptent à votre clientèle** (professionnels, consommateurs ou les deux) :
  droit de rétractation et son formulaire type, garanties légales, médiateur, règles de
  compétence pour les consommateurs, pénalités de retard entre professionnels. Sections
  optionnelles : services numériques, abonnements, renonciation à la rétractation pour
  un contenu fourni immédiatement, limites de responsabilité.
- **Des liens de pied de page** vers les pages actives, et **un service qui liste leurs
  URL** pour votre sitemap.
- **Les erreurs sont arrêtées à la construction du conteneur, pas par un visiteur.**
  Vente aux consommateurs sans médiateur, sous-traitant non déclaré, transfert hors UE
  sans garantie, cookie de mesure d'audience sans moyen de retirer son accord : chacune
  bloque `cache:clear` avec un message qui nomme la clé et la règle.

## Ce qu'il ne fait pas

Ce n'est pas un conseil juridique et il ne certifie rien. Il rédige un texte générique
et correct autour de **vos** faits. Relisez vos pages avant de les publier ; si votre
activité a des conditions particulières (livraison, devis, niveau de service),
ajoutez-les en sections.

Pas de SEO, pas de bandeau de consentement, pas de fichier sitemap : le bundle expose
ses URL et s'arrête là.

---

## Installation

Avec Symfony Flex, la recette enregistre le bundle et écrit
`config/packages/fineprint.yaml` et `config/routes/fineprint.yaml`. **Remplacez chaque
`CHANGE ME` avant la mise en ligne** : ce sont vos pages légales, publiées telles quelles.

Sans Flex :

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

Le bundle a besoin du traducteur (`framework.translator`) et de Twig.

## Configuration

L'exemple complet, commenté, est dans le [README anglais](README.md#configuration) : les
clés sont les mêmes. L'essentiel :

```yaml
fineprint:
    site: { name: 'Exemple', url: 'https://exemple.fr' }
    publisher:
        legal_name: 'Jeanne Martin'
        legal_form: 'entrepreneur individuel (micro-entreprise)'
        siret: '123 456 789 00012'
        address: '1 rue de l''Exemple, 75001 Paris'
        email: 'contact@exemple.fr'
        phone: '01 00 00 00 00'         # facultatif, mais la LCEN le demande aux professionnels
        publication_director: 'Jeanne Martin'
    host: { name: 'Hébergeur SAS', address: '2 avenue du Serveur, 59100 Roubaix', phone: '01 00 00 00 01' }
    terms:
        audience: b2b                   # b2b, b2c ou both
        vat_exempt: true                # « TVA non applicable, art. 293 B du CGI »
    privacy:
        activities:
            - purpose: { fr: 'Répondre aux messages', en: 'Answering messages' }
              legal_basis: legitimate_interests
              legitimate_interest: { fr: 'répondre aux demandes', en: 'answering requests' }
              retention: { fr: '3 ans', en: '3 years' }
              processors: [host]
```

Tout texte se donne une fois (`'Journaux'`) ou par langue (`{ fr: …, en: … }`). Les
valeurs peuvent venir de `%env()%`.

### Les règles qui bloquent la construction

| Vous avez déclaré | Ce qui manque |
|---|---|
| `terms.audience: b2c` ou `both` | un `mediator` (Code de la consommation, L612-1) |
| un traitement fondé sur `legitimate_interests` | son `legitimate_interest` (RGPD, art. 13.1.d) |
| un sous-traitant `outside_eu: true` | ses `safeguards` (RGPD, art. 13.1.f) |
| un traitement qui nomme un sous-traitant | ce sous-traitant sous `privacy.processors` |
| un cookie qui n'est pas `essential` | `cookies.manage_url`, pour retirer son accord |
| la page de confidentialité active | au moins un traitement |
| une langue | un chemin pour elle sur chaque page active |

### Ce que vous seul pouvez faire

- **Renonciation à la rétractation** : avec `immediate_digital_content`, les CGV disent
  que l'accord exprès et la reconnaissance du consommateur sont recueillis à la
  commande. Votre tunnel de commande doit vraiment les recueillir (une case non cochée
  par défaut, enregistrée avec la commande).
- **Résiliation en ligne** : le consommateur doit pouvoir résilier un abonnement en
  ligne (L215-1-1). Les CGV le disent ; votre site doit le permettre.

## Liens de pied de page

```twig
{{ include('@Fineprint/footer_links.html.twig') }}

{% for link in fineprint_links() %}<a href="{{ link.url }}">{{ link.label }}</a>{% endfor %}

<a href="{{ fineprint_url('privacy') }}">…</a>   {# null si la page est désactivée #}
```

## Sitemap

`Acencyril\FineprintBundle\Sitemap\FineprintUrls` (alias `fineprint.urls`) renvoie les
URL absolues des pages actives, avec leurs versions dans les autres langues. Elles sont
construites sur `site.url` : une commande console obtient la même liste qu'une requête.

## Surcharger

À la manière de Symfony, dans `templates/bundles/FineprintBundle/`. Étendez l'original
avec `@!Fineprint` et ne remplacez que le bloc voulu :

```twig
{# templates/bundles/FineprintBundle/terms.html.twig #}
{% extends '@!Fineprint/terms.html.twig' %}
{% block fineprint_liability %}<section>…votre clause…</section>{% endblock %}
```

- `layout.html.twig` enveloppe toutes les pages : surchargez-le si votre gabarit de base
  utilise d'autres blocs que `title` et `body`.
- Chaque section est un bloc (`fineprint_publisher`, `fineprint_withdrawal`,
  `fineprint_activities`, …). Ajoutez du contenu avec `fineprint_extra`, ou avec
  `pages.<page>.sections` dans la configuration.
- Rédaction : surchargez n'importe quelle clé du domaine de traduction `fineprint` dans
  votre `translations/fineprint.fr.yaml`.

## Recette Flex

La recette est dans [`recipe/`](recipe), au format de
[symfony/recipes-contrib](https://github.com/symfony/recipes-contrib), prête à être
proposée sous `acencyril/fineprint-bundle/0.1/`. Tant qu'elle n'est pas acceptée, Flex ne
l'applique pas : suivez l'installation sans Flex et copiez `recipe/config/` dans votre
`config/`.

## Tests

```bash
composer install
vendor/bin/phpunit
```

## Licence

MIT.

## Contribuer

Voir [CONTRIBUTING.md](CONTRIBUTING.md). Une clause fausse, dépassée ou absente pour un
cas courant est le signalement le plus utile : dites quel texte et quel article.
