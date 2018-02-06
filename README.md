# Velocita Composer plugin

A Composer plugin that enables transparent Velocita caching for your projects.

## Getting Started

### Prerequisites

* PHP 7.1 or newer
* Composer
* A running [Velocita](https://github.com/isaaceindhoven/velocita) instance

### Installing

Installation and configuration of the Velocita plugin is global, so you can use it for all projects that use Composer
without having to add it as a requirement to your `composer.json`.

```
composer global require isaac/composer-velocita
composer velocita:enable https://url.to.your.velocita.tld/
```

### Usage

After enabling and configuring the Velocita plugin, it is automatically used for all Composer projects when running
`require`, `update`, `install`, etcetera.

### Disabling

Disable the plugin by executing:

```
composer velocita:disable
```

## Authors

* Jelle Raaijmakers - [jelle.raaijmakers@isaac.nl](mailto:jelle.raaijmakers@isaac.nl) / [GMTA](https://github.com/GMTA)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
