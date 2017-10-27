# Velocita Composer plugin

Use a Velocita Composer caching instance transparently for all your projects.

## Getting Started

### Prerequisites

* PHP 7.1 or newer
* Composer
* A running [Velocita](https://github.com/isaaceindhoven/velocita) instance

### Installing

Installation and configuration of the Velocita plugin is global, which means you can use it for all projects that use
Composer without having to install it separately.

```
composer global require isaac/composer-velocita
composer velocita:enable https://url.to.your.velocita.tld/
```

### Disabling

Disable the plugin by executing:

```
composer velocita:disable
```

## Authors

* Jelle Raaijmakers - [jelle.raaijmakers@isaac.nl](mailto:jelle.raaijmakers@isaac.nl) / [GMTA](https://github.com/GMTA)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
