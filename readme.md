# Composer Yaml Neon Plugin

[![Latest Stable Version](https://poser.pugx.org/forrest79/composer-yaml-neon-plugin/v)](//packagist.org/packages/forrest79/composer-yaml-neon-plugin)
[![Monthly Downloads](https://poser.pugx.org/forrest79/composer-yaml-neon-plugin/d/monthly)](//packagist.org/packages/forrest79/composer-yaml-neon-plugin)
[![License](https://poser.pugx.org/forrest79/composer-yaml-neon-plugin/license)](//packagist.org/packages/forrest79/composer-yaml-neon-plugin)
[![Build](https://github.com/forrest79/composer-yaml-neon-plugin/actions/workflows/build.yml/badge.svg?branch=master)](https://github.com/forrest79/composer-yaml-neon-plugin/actions/workflows/build.yml)

## tl;dr

Plugin to use [Composer](https://github.com/composer/composer) with the config file in [YAML](https://yaml.org/) (`composer.yaml`/`composer.yml`) or [NEON](https://ne-on.org/) (`composer.neon`) format instead of JSON.


## Installation

This plugin must be installed globally:

```bash
$ composer global require forrest79/composer-yaml-neon-plugin && composer global update
```

It is recommended to disable [use-parent-dir](https://getcomposer.org/doc/06-config.md#use-parent-dir) to omit question `No composer.json in current directory, do you want to use the one at ...?` where there is a config file in a `YAML` or `NEON` format and in some parent directory is a config file in `JSON` format. 

```bash
$ composer config --global use-parent-dir false
```


## How to use it

Prepare a config file `composer.yaml`/`composer.yml` (for the `YAML` format), or `composer.neon` (for the `NEON` format) instead of `composer.json`.

> Different config file/path via environment variable `COMPOSER` is also supported. Don't point directly to a `YAML` or `NEON` config file, always point to the (virtual) `JSON` config file and let plugin detect correct config file.

For example `YAML` format:

```yaml
# You can use comments...

name: forrest79/composer-yaml-neon-plugin # ...or this comments

authors:
    -
        name: 'Jakub Trmota'
        email: jakub@trmota.cz

require:
    composer/composer: 2.3.6
    php: '>=8.0'

require-dev:
    squizlabs/php_codesniffer: ^3.5

autoload:
    psr-4:
        Forrest79\ComposerYamlNeonPlugin\: src

bin:
    - bin/composer

scripts:
    phpcs: 'vendor/bin/phpcs -s src'

config:
    allow-plugins:
        dealerdirect/phpcodesniffer-composer-installer: false
```

or `NEON` format:

```
# You can use comments...

name: forrest79/composer-yaml-neon-plugin # ...or this comments

authors:
	-
		name: Jakub Trmota
		email: jakub@trmota.cz

require:
	composer/composer: '2.3.6'
	php: '>=8.0'

require-dev:
	squizlabs/php_codesniffer: ^3.5

autoload:
	psr-4:
		Forrest79\ComposerYamlNeonPlugin\: src

bin:
	- bin/composer

scripts:
	phpcs: vendor/bin/phpcs -s src

config:
	allow-plugins:
		dealerdirect/phpcodesniffer-composer-installer: false
```

> IMPORTANT: You can use only one config file in a directory.


### Generate composer.json

To generate classic `composer.json` file, use `composer generate-composer-json` command.


## How does it work?

Simply! If plugin detects `YAML` or `NEON` config file at the startup, it will generate `composer.json` and at the end is JSON file cleaned. That's the magic.

When `composer.json` is changed by Composer (i.e., after `composer require` command etc.), the new config file in YAML or NEON format is saved next to the original one, and you must make manual diff and merge.
