# Socialstream

<p align="center">
    <a href="https://github.com/joelbutcher/jetstream-team-transfer/actions">
        <img src="https://github.com/joelbutcher/jetstream-team-transfer/workflows/tests/badge.svg" alt="Build Status">
    </a>
    <a href="https://packagist.org/packages/joelbutcher/jetstream-team-transfer">
        <img src="https://img.shields.io/packagist/dt/joelbutcher/jetstream-team-transfer" alt="Total Downloads">
    </a>
    <a href="https://packagist.org/packages/joelbutcher/jetstream-team-transfer">
        <img src="https://img.shields.io/packagist/v/joelbutcher/jetstream-team-transfer" alt="Latest Stable Version">
    </a>
    <a href="https://packagist.org/packages/joelbutcher/jetstream-team-transfer">
        <img src="https://img.shields.io/packagist/l/joelbutcher/jetstream-team-transfer" alt="License">
    </a>
</p>

## Installation

Getting started with Socialstream is a breeze. With a simple two-step process to get you on your way to creating the next big thing. Inspired by the simplicity of Jetstream's installation process, Socialstream follows the same 'installation':

```sh
composer require joelbutcher/jetstream-team-transfer

php artisan jetstream-team-transfer:install
```

The install command will publish a new `Team` model utilising the `TransfersTeam` trait and a `team-transfer-form.blade.php` stub to the `resources/views/teams` directory. It will also replace the default `TeamPolicy` shipped with Jetstream, with a modified version that includes a `transferTeam` method.

> Note: This package only works with the Livewire Jetstream stack.

To include the form in your teams view, add the following livewire component to the `teams/show.blade.php` file:

```php
@livewire('teams.team-transfer-form', ['team' => $team])
```

## Changelog

Check out the [CHANGELOG](CHANGELOG.md) in this repository for all the recent changes.

## Maintainers

Socialstream is developed and maintained by [Joel Butcher](https://joelbutcher.co.uk)

## License

Socialstream is open-sourced software licensed under the [MIT license](LICENSE.md).
