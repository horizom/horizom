# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Commit 08-07-2022 (v3.0.0)

- Refactorings
- Add `auth` package
- Add `session` package
- Add `validation` package

## Commit 06-07-2022 (v2.5.2)

- Add `resource()` method to `Horizom\Routing\RouteCollector`.
- Add `resources()` method to `Horizom\Routing\RouteCollector`.
- Add `redirect()` method to `Horizom\Routing\RouteCollector`.
- Add `redirectPermanently()` method to `Horizom\Routing\RouteCollector`.

## Commit 08-03-2022 (v2.5.0)

- Implement request file upload.

## Commit 06-02-2022 (v2.4.2)

- Add `Whoops\Handler\PrettyPageHandler` to deal with AJAX requests with an equally-informative JSON response.

## Commit 06-09-2021 (v2.4.0)

- Suppression de la librairie `Horizom\Auth` qui permettait de gérer l'authentification
- Ajout de la méthode `any()` dans la classe `Horizom\Routing\RouteCollector`, qui permet de définir une route accessible via toute les méthode.
- Ajout de la méthode `middleware()` dans la classe `Horizom\Routing\Route`.
- Ajout de la méthode `name()` dans la classe `Horizom\Routing\Route`.
- Divers correction de bug

## Commit 28-07-2021 (v2.3.0)

- Renommage de la méthode `getContainer()` en `container()` dans la classe `Horizom\App`
- Ajout de la fonction `app()`, qui retourne l'instance de l'application en cours, dans les helpers.
- Ajout de la fonction `bcrypt()`, pour hacher les chainess de caractères, dans les helpers.
- Divers correction de bug

## Commit 05-05-2020

- [x] Routing complet basé sur [AuraRouter](http://auraphp.com/packages/3.x/Router)
- [x] Moteur de template simple et puissant [Blade Template de Laravel](https://laravel.com/docs/5.8/blade)
- [x] Système d'injection de dépendance [PHP-DI](http://php-di.org/) (en cours...)
- [ ] Documentation de 80% du code source...

## Commit 04-05-2020

- [x] Ajout d'un Dispatcher psr15
- [x] Système de routing

## [Commit 29-04-2021](https://github.com/horizom/core/commit/23404ed487e0b967b74ab3a9770ccf37ec058818)

- Update Request.php
- Bug sur le port dans l'url de base
