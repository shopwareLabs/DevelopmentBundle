# DevelopmentBundle

A Shopware 6 development bundle that provides additional development utilities and tools.

## Overview

This bundle extends the Shopware 6 core framework to provide development-specific functionality.

## Installation

```
composer require shopware/development-bundle
```

Add it to `config/bundles.php`

```php
DevelopmentBundle::class => ['dev' => true],
```

## Structure

```
DevelopmentBundle/
├── composer.json        # Bundle dependencies and autoloading
├── src/
│   └── DevelopmentBundle.php  # Main bundle class
└── README.md           # This file
```

## Requirements

- Shopware 6
- PHP 8.2 or higher

## Usage

This bundle is automatically loaded when placed in the `custom/plugins` directory of your Shopware installation.

## Development

To extend this bundle, add your custom services, commands, or controllers in the `src/` directory following Symfony bundle conventions.
