<?php

// tests/TutorialSuite.php


// La fonction describe(string, callable) vous permet de décrire une suite de tests :
describe('A tutorial : how to decribe a suite', function () {
    // Vous pouvez mettre en place des valeurs qui vont servir dans le reste de la suite
    $firstName = 'Lior';

    // La fonction it(string, callable) vous permet d'écrire un test. Elle doit forcément retourner
    // un boolean ou une string contenant le message d'erreur (si la fonction retourne une string
    // on considérera le test comme foiré)
    it('should have the firstName "Lior"', function () use ($firstName) {
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
        return assertCodeWillThrowException(function () use ($firstName) {
            $sum = $firstName + 12;
        }, Exception::class);
    });


    // Vous pouvez imbriquer une suite dans une autre suite
    describe('Une suite imbriquée', function () use ($firstName) {
        // ...
    });
});

// Vous pouvez aussi bien sur créer plusieurs suites dans un seul fichier
describe('Une deuxième suite dans le fichier', function () {
    // ...
});
