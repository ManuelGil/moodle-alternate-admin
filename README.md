# Alternate Admin for Moodle

[![GitHub Release](https://img.shields.io/github/v/release/ManuelGil/moodle-alternate-admin)]()
[![GitHub Release Date](https://img.shields.io/github/release-date/ManuelGil/moodle-alternate-admin)]()
[![GitHub license](https://img.shields.io/github/license/ManuelGil/vscode-moodle-snippets)]()

![preview](https://raw.githubusercontent.com/ManuelGil/moodle-alternate-admin/main/docs/images/preview.png)

This wrapper for Moodle adds a new interface to streamline your administrative tasks.

## Recommended

-   Plugin [VSCode Moodle Pack](https://marketplace.visualstudio.com/items?itemName=imgildev.vscode-moodle-snippets)
-   Plugin [VSCode Mustache Snippets](https://marketplace.visualstudio.com/items?itemName=imgildev.vscode-mustache-snippets)

## Features

-   No dependencies that require jQuery
-   Bootstrap 5 Admin Dashboard Template
-   Simple Vue.js 2 CDN integration
-   Friendly Alerts via Sweet Alert
-   Easy installation via Composer
-   Gravatar Profile image
-   Integration with Mustage Engine
-   Friendly URLs

## Requirements

-   PHP 7.2 or later
-   MySQL or MariaDB
-   Apache Server
-   Moodle 3.x Installation

## Installation

You can install this wrapper via composer with the following commands:

```bash
$ composer create-project manuelgil/moodle-alternate-admin {directory} --prefer-dist
```

## Configure the project

-   Copy the [`.env.example`](./.env.example)
    file and call it `.env`.

```bash
$ cp .env.example .env
```

-   Edit the environment variables in the .env file as you need.

    > MODE_DEBUG => show errors

    > MDL_CONFIG => moodle config file path (required)

-   Make www-data the owner to `logs` folder.

```bash
$ sudo chown www-data: logs/
```

## Built With

-   PHP 7.4.3 ([XAMPP](https://www.apachefriends.org/download.html))
-   COMPOSER 2.0.9 ([COMPOSER](https://getcomposer.org/download/))
-   Moodle 3.10.1 ([Moodle](https://download.moodle.org/))
-   Visual Studio Code 1.53.0 ([VSCode](https://code.visualstudio.com/download))
-   Moodle Snippets for VSCode 1.1.0 ([Moodle Pack](https://marketplace.visualstudio.com/items?itemName=imgildev.vscode-moodle-snippets))

## Changelog

See [CHANGELOG.md](./CHANGELOG.md)

## Contributing

Thank you for considering contributing to alternate admin. The contribution guide can be found in the [CONTRIBUTING.md](./.github/CONTRIBUTING.md).

## Code of Conduct

In order to ensure that the alternate admin community is welcoming to all, please review and abide by the [CODE_OF_CONDUCT](./.github/CODE_OF_CONDUCT.md).

## Authors

-   **Manuel Gil** - _Owner_ - [ManuelGil](https://github.com/ManuelGil)

See also the list of [contributors](https://github.com/ManuelGil/moodle-alternate-admin/contributors)
who participated in this project.

## License

Alternate Admin is licensed under the GPL v3 or later License - see the
[GNU GPL v3 or later](http://www.gnu.org/copyleft/gpl.html) for details.
