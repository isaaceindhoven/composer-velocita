# Velocita Composer plugin

[![Latest Stable Version](https://poser.pugx.org/isaac/composer-velocita/version)](https://packagist.org/packages/isaac/composer-velocita)
[![Total Downloads](https://poser.pugx.org/isaac/composer-velocita/downloads)](https://packagist.org/packages/isaac/composer-velocita)
[![License](https://poser.pugx.org/isaac/composer-velocita/license)](https://packagist.org/packages/isaac/composer-velocita)

Fast and reliable Composer package downloads by using [Velocita](https://github.com/isaaceindhoven/velocita).

## Getting Started

### Prerequisites

* PHP 7.2 or newer
* A running Velocita instance
* Compatible with Composer 1

### Installation

Installation and configuration of the Velocita plugin is global, so you can use it for all projects that use Composer
without having to add it to your project's `composer.json`.

```
composer global require isaac/composer-velocita
composer velocita:enable https://url.to.your.velocita.tld/
```

NOTE: when using Composer 1.10.5 or lower, you need to explicitly pass a version constraint of `^2`:

```
composer global require isaac/composer-velocita:^2
```

### Usage

After enabling and configuring Velocita, it is automatically used for all Composer projects when running `require`,
`update`, `install`, etcetera.

### Removal

Disable the plugin by executing:

```
composer velocita:disable
```

If you want to remove the plugin completely, execute:

```
composer global remove isaac/composer-velocita
```

## Known issues

* The `composer create-project` command initially downloads from Packagist directly, but will use Velocita for the
  subsequent installation of dependencies. See [Composer issue #7090](https://github.com/composer/composer/issues/7090).
* The Symfony Flex and Prestissimo plugins might try to download directly from Packagist; although this should never
  happen for `composer install` with an existing `composer.lock` file.

## Authors

* Jelle Raaijmakers - [jelle.raaijmakers@isaac.nl](mailto:jelle.raaijmakers@isaac.nl) / [GMTA](https://github.com/GMTA)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
