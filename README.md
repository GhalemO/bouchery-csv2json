# Challenge de Fred Bouchery : transformation de fichier CSV !

## Utilisation du générateur de JSON

`php main.php csv2json file.csv [--fields "field1,field2,..." --aggregate "field" --desc path/to/schema`

## Procédure de prise en main immédiate :

```
git clone https://github.com/liorchamla/bouchery-csv2json.git

cd bouchery-csv2json

php main.php unit-test

php main.php csv2json file.csv --aggregate "id" --desc schema.bini
```

## Formats de sortie :

Deux formats de sortie sont disponibles : XML et JSON

- JSON : format par défaut (mais aussi accessible avec `--format json`
- XML : accessible avec `--format xml`

Les deux options fonctionne en mode PRETTY ou sans (en ajoutant `--pretty`)

## Fichiers de description (schémas)

Vous pouvez fournir des fichiers de description dans deux formats :

- Bouchery Description Format (.bini)
- Xml Description Format (.xml)

Les extensions des fichiers sont importantes car c'est grâce à elles qu'on sait quel loader utiliser.

Les types de données qu'on peut spécifier sont :
* Nombres entiers : int | integer
* Nombres à virgule flotante : float
* Booléens : bool | boolean (accèpte : 1, 0, '1', '0', true, 'true', false, 'false', 'on', 'off', 'yes', 'no')
* Dates : date (yyyy-MM-dd)
* DateTimes : datetime (yyyy-MM-dd H:i:s)
* Times : time (H:i:s)
* Chaines de caractères : string

### Bouchery Description Format (.bini)

On peut décrire la structure et le formattage grâce à un format spécifique :

```
# Un commentaire
name=string
# On a le droit aux espaces
date = datetime
# Et on peut définir des données optionnelles
id=?int
```

Voir le readme de Fred Bouchery

### Xml Description Format (.xml)

On peut décrire la structure et le formattage grâce à un fichier XML

```xml
<schema>
    <field id="name" type="string" />
    <field id="id" type="integer" optional="true" />
    <field id="date" type="datetime" />
</schema>
```

### Créer son propre format de description 
Vous aurez peut-être envie de créer votre propre format de description (schema). Vous pouvez simplement créer une nouvelle classe qui implémente l'interface `DescLoaderInterface` et vous assurer de bien charger ce nouveau loader dans le fichier `app.php`


## Tests unitaires

`php main.php unit-test [path/to/a/SuiteFile.php]`

## Créer ses propres tests :

Vous pouvez compléter les tests (qui sont loins d'être complets, notamment pour les validateurs) en créant un fichier dont le nom finit par `Suite.php` dans le dossier des tests :

```php
<?php

// tests/TutorialSuite.php


// La fonction describe(string, callable) vous permet de décrire une suite de tests :
describe('A tutorial : how to decribe a suite', function() {
    // Vous pouvez mettre en place des valeurs qui vont servir dans le reste de la suite
    $firstName = 'Lior';

    // La fonction it(string, callable) vous permet d'écrire un test. Elle doit forcément retourner
    // un boolean ou une string contenant le message d'erreur (si la fonction retourne une string
    // on considérera le test comme foiré)
    it('should have the firstName "Lior"', function() use ($firstName) {
        // Vous pouvez simplement retourner true si le test est réussi selon vos critères
        return true;

        // Vous pouvez simplement retourner false si le test est raté selon vos critères
        return false;

        // Vous pouvez retourner une string si le test est raté et que vous voulez expliquer pourquoi
        return "Le test a foiré car X et Y";

        // La fonction assertEquals(mixed, mixed) vous permet de faire une comparaison stricte entre deux valeurs
        return assertEquals($firstName, 'Lior');

        // La fonction assertSameArrays(array, array) vous permet de faire une vérification entre deux tableaux
        return assertSameArrays(['L', 'i', 'o', 'r'], explode('', $firstName));

        // La foncion assertCodeWillThrowException(callable [, string]) vous permet de vérifier qu'un code lance
        // bien une exception (vous pouvez préciser la classe d'exception attendue si vous voulez tester
        // encore plus précisément)
        return assertCodeWillThrowException(function() use ($firstName) {
            $sum = $firstName + 12;
        }, Exception::class);
    });


    // Vous pouvez imbriquer une suite dans une autre suite
    describe('Une suite imbriquée', function() use ($firstName) {
        // ...
    });
});

// Vous pouvez aussi bien sur créer plusieurs suites dans un seul fichier
describe('Une deuxième suite dans le fichier', function() {
    // ...
});
```

## Appeler une seule suite quand on fait tourner les tests

La commande `php main.php unit-test` exécutera tous les fichiers finissant par `Suite.php` mais vous pouvez préciser une suite à faire tourner si vous le souhaitez avec `php main.php unit-test path/to/DesiredSuite.php` !
