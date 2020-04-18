<?php

spl_autoload_register(function (string $className) {
    if (strpos($className, 'App\\') !== 0) {
        return;
    }

    $path = str_replace('App\\', 'src/', $className);
    $path = str_replace('\\', '/', $path) . '.php';

    if (!file_exists($path)) {
        throw new Exception(sprintf(
            "Classe '%s' impossible à charger, le fichier '%s' n'existe pas !",
            $className,
            $path
        ));
    }

    require_once $path;
});
