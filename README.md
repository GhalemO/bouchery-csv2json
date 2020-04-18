# Challenge de Fred Bouchery : transformation de fichier CSV !

## Utilisation du générateur de JSON

`php main.php csv2json file.csv [--fields "field1,field2,..." --aggregate "field" --desc path/to/schema`

## Tests unitaires

`php main.php unit-test [path/to/a/SuiteFile.php]`

## Procédure de prise en main immédiate :

```
git clone https://github.com/liorchamla/bouchery-csv2json.git

cd bouchery-csv2json

php main.php unit-test

php main.php csv2json file.csv --aggregate "id" --desc schema.ini
```

