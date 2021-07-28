# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Commit 28-07-2021 (v2.3.0)

- Renommage de la methode `getContainer()` en `container()` dans la classe `Horizom\App`
- Ajout de la fonction `app()`, qui retourne l'instance de l'application en cours, dans les helpers.
- Ajout de la fonction `bcrypt()`, pour hacher les chainess de caractères, dans les helpers.
- Divers correction de bug

## Commit 05-05-2020

- [x] Routing complet basé sur [AuraRouter](http://auraphp.com/packages/3.x/Router)
- [x] Moteur de template simple et puissant [Blade Template de Laravel](https://laravel.com/docs/5.8/blade)
- [ ] Système d'injection de dépendance [PHP-DI](http://php-di.org/) (en cours...)
- [ ] Documentation de 80% du code source...

## Commit 04-05-2020

- [x] Ajout d'un Dispatcher psr15
- [x] Système de routing

## [Commit 29-04-2021](https://github.com/horizom/core/commit/23404ed487e0b967b74ab3a9770ccf37ec058818)

- Update Request.php
- Bug sur le port dans l'url de base
