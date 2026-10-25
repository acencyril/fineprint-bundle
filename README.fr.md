# Fineprint

**Les pages légales d'un site Symfony, assemblées à partir des clauses dont votre droit
et votre activité ont besoin.**

Mentions légales, CGV, politique de confidentialité et cookies, en français et en
anglais, sous `/{locale}/…`. Vous choisissez un **pack de juridiction** (droit français,
ou droit de l'Union pour tout État membre) et vous déclarez vos **activités** (à qui vous
vendez, et quoi) ; Fineprint n'assemble que les clauses qui s'appliquent, les remplit
avec vos faits, et refuse de construire si une mention exigée par votre droit manque.

*English documentation: [README.md](README.md)*

```bash
composer require acencyril/fineprint-bundle
```

> **Ce n'est pas un conseil juridique.** Fineprint rédige un texte générique et correct
> autour des faits que vous déclarez. Il ne certifie rien. Faites relire vos pages par
> un juriste avant de vous y fier, et ajoutez les conditions propres à votre activité.

---

## Les pages

| Page | Chemin français | Chemin anglais |
|---|---|---|
| Mentions légales | `/fr/mentions-legales` | `/en/legal-notice` |
| Conditions générales de vente | `/fr/cgv` | `/en/terms-of-sale` |
| Politique de confidentialité | `/fr/confidentialite` | `/en/privacy` |
| Cookies | `/fr/cookies` | `/en/cookies` |

Chaque page peut être désactivée (elle n'a alors plus de route et répond 404), et son
chemin réglé par langue.

## Les packs de juridiction

Un pack contient les clauses des quatre pages, leurs textes en français et en anglais,
les champs que son droit exige et les contrôles qu'il applique. On le choisit avec
`jurisdiction`.

| Pack | Pour | Cite | Exige |
|---|---|---|---|
| `fr` | Les sites soumis au droit français | LCEN, Code de la consommation, Code de commerce, RGPD, loi Informatique et Libertés (CNIL) | SIRET (`company_id`), directeur de la publication, nom, adresse et téléphone de l'hébergeur, `governing_law: France`, un médiateur de la consommation dès que vous vendez aux consommateurs |
| `eu` | Tout État membre de l'UE | Le seul droit de l'Union : directive commerce électronique, directive droits des consommateurs, directives 2019/770 et 2019/771, directive REL, directive retards de paiement, RGPD, directive vie privée, Rome I, Bruxelles I bis | `publisher.country` |

Le pack `eu` n'énonce aucune règle nationale : votre `governing_law` et votre pays sont
affichés tels que déclarés, et les obligations nationales qui vont au-delà du droit de
l'Union (format d'immatriculation, médiateur obligatoire, bouton de résiliation) sont à
ajouter en sections, ou dans un pack pour votre pays. Il ne contient pas de lien vers la
plateforme RLL : la plateforme européenne a fermé en juillet 2025.

Tout bundle ou toute application peut ajouter un pack (`de`, `uk`, `us`…) : voir
[Écrire un pack de juridiction](CONTRIBUTING.md#write-a-jurisdiction-pack).

## Les activités

```yaml
activities: [b2b, b2c, digital, subscription, physical_goods, marketplace, services]
```

Déclarez au moins `b2b` ou `b2c`, puis ce que vous vendez. Chaque clause déclare les
activités dont elle a besoin : les CGV ne disent que ce qui vous concerne.

| Activité | Apporte |
|---|---|
| `b2b` | prix hors taxes, pénalités de retard, pas de rétractation, limites de responsabilité, tribunal compétent |
| `b2c` | prix TTC, rétractation de 14 jours et formulaire type, garanties légales, médiation, règles de compétence pour le consommateur |
| `digital` | services numériques, garantie de conformité des contenus numériques, renonciation à la rétractation |
| `services` | exécution des prestations, paiement proportionnel en cas de rétractation, renonciation à la rétractation |
| `subscription` | renouvellement et résiliation (résiliation en ligne pour les consommateurs, pack `fr`) |
| `physical_goods` | livraison, transfert des risques, retours, garantie de conformité des biens |
| `marketplace` | vendeurs tiers, qualité de professionnel, paramètres de classement |

Les contrôles suivent : un médiateur n'est exigé qu'avec `b2c` (pack `fr`), la
renonciation à la rétractation qu'avec `b2c` et `digital` ou `services`, les paramètres de
classement qu'avec `marketplace`.

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
clés sont les mêmes. Une micro-entreprise qui vend des prestations à des professionnels :

```yaml
fineprint:
    jurisdiction: fr
    governing_law: 'France'
    activities: [b2b, services]
    site: { name: 'Exemple', url: 'https://exemple.fr' }
    publisher:
        legal_name: 'Jeanne Martin'
        legal_form: 'entrepreneur individuel (micro-entreprise)'
        company_id: '123 456 789 00012'
        address: '1 rue de l''Exemple, 75001 Paris'
        email: 'contact@exemple.fr'
        phone: '01 00 00 00 00'         # facultatif, mais la LCEN le demande aux professionnels
        publication_director: 'Jeanne Martin'
    host: { name: 'Hébergeur SAS', address: '2 avenue du Serveur, 59100 Roubaix', phone: '01 00 00 00 01' }
    pages:
        terms:
            sections:
                add:
                    - { id: devis, title: 'Devis', body: 'Un devis vaut 30 jours.', position: 'after:order' }
    terms:
        vat_exempt: true                # « TVA non applicable, art. 293 B du CGI »
        court: 'Melun'
    privacy:
        processing:
            - purpose: { fr: 'Répondre aux messages', en: 'Answering messages' }
              legal_basis: legitimate_interests
              legitimate_interest: { fr: 'répondre aux demandes', en: 'answering requests' }
              retention: { fr: '3 ans', en: '3 years' }
              processors: [host]
```

Tout texte se donne une fois (`'Journaux'`) ou par langue (`{ fr: …, en: … }`). Les
valeurs peuvent venir de `%env()%` ; les contrôles de format les ignorent.

### Les contrôles qui bloquent la construction

Le cœur vérifie la forme (chemins par langue, à qui vous vendez, sous-traitants,
sections) ; le pack vérifie son droit. Une configuration fautive arrête `cache:clear` en
listant chaque problème, avec la clé et la règle :

```
Invalid Fineprint configuration:
- fineprint.host.phone is required by the "fr" pack.
- fineprint.activities includes b2c: declare a consumer mediator under fineprint.mediator (Code de la consommation, L612-1).
```

### Ce que vous seul pouvez faire

- **Renonciation à la rétractation** : les CGV disent que l'accord exprès et la
  reconnaissance du consommateur sont recueillis à la commande. Votre tunnel de commande
  doit vraiment les recueillir (une case non cochée par défaut, enregistrée avec la
  commande).
- **Résiliation en ligne** (`fr`, consommateurs) : les CGV disent qu'une fonctionnalité
  de résiliation existe (L215-1-1) ; votre site doit la proposer.

## Les sections

Chaque page est une liste de clauses identifiées (`law`, `withdrawal`, `price_b2b`…).
Les identifiants d'un pack sont dans sa classe (`src/Pack/Fr/FrPack.php`,
`src/Pack/Eu/EuPack.php`) et sur la page elle-même : chaque section porte un `id="…"`
(les tirets bas deviennent des tirets).

- **`remove: [ids]`** retire des clauses.
- **`override: {id: …}`** remplace le texte d'une clause et garde son titre. La valeur
  est une clé de traduction de votre domaine `messages`, un nom de gabarit en `.twig`, ou
  `{body|template, title, domain}`.
- **`add: [{id, title, body|template, position}]`** insère vos propres sections. `body`
  est du texte brut (une ligne vide commence un paragraphe) ; `position` vaut `start`,
  `end` (par défaut), `before:<id>` ou `after:<id>`. Une section ancrée sur une clause que
  vos activités écartent va à la fin.

Les identifiants inconnus ou déjà pris, et les pages vides, sont refusés à la
construction.

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

## Surcharger les gabarits

À la manière de Symfony, dans `templates/bundles/FineprintBundle/` :

- `layout.html.twig` enveloppe toutes les pages : surchargez-le si votre gabarit de base
  utilise d'autres blocs que `title` et `body`.
- `terms.html.twig` (et les trois autres) étendent `page.html.twig` : surchargez-en un
  pour changer une seule page, avec les blocs `fineprint_sections` et `fineprint_extra`.
- `_section.html.twig` affiche une section ; `clauses/*.html.twig` sont les clauses
  construites à partir de données (éditeur, hébergeur, traitements, sous-traitants,
  cookies, formulaire de rétractation, garanties, médiateur).
- Rédaction : surchargez n'importe quelle clé du domaine d'un pack (`fineprint_fr`,
  `fineprint_eu`) dans votre `translations/fineprint_fr.fr.yaml`.

## Passer de la 0.1 à la 0.2

La 0.2 remplace la configuration : voir le tableau de correspondance dans le
[README anglais](README.md#upgrading-from-01).

## Recette Flex

La recette est dans [`recipe/`](recipe), au format de
[symfony/recipes-contrib](https://github.com/symfony/recipes-contrib), prête à être
proposée sous `acencyril/fineprint-bundle/0.2/`. Tant qu'elle n'est pas acceptée, Flex ne
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

Voir [CONTRIBUTING.md](CONTRIBUTING.md), dont la marche à suivre pour écrire un pack de
juridiction. Une clause fausse, dépassée ou absente pour un cas courant est le
signalement le plus utile : dites quel texte et quel article.
