# Velocita Composer plugin

[![Packagist Version](https://img.shields.io/packagist/v/isaac/composer-velocita)](https://packagist.org/packages/isaac/composer-velocita)
[![Packagist Downloads](https://img.shields.io/packagist/dt/isaac/composer-velocita)](https://packagist.org/packages/isaac/composer-velocita)
![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/isaac/composer-velocita)
[![License](https://img.shields.io/github/license/isaaceindhoven/composer-velocita)](https://github.com/isaaceindhoven/composer-velocita/blob/master/LICENSE)

Fast and reliable Composer package downloads using [Velocita](https://github.com/isaaceindhoven/velocita).

## Getting Started

### Prerequisites

* PHP 7.2 or newer
* A running Velocita instance
* Composer 1 or 2

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

## Authors

* Jelle Raaijmakers - [jelle.raaijmakers@isaac.nl](mailto:jelle.raaijmakers@isaac.nl) / [GMTA](https://github.com/GMTA)

## Contributing

Raise an issue or submit a pull request on [GitHub](https://github.com/isaaceindhoven/composer-velocita).

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
