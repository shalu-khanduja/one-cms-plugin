# OneCMS Data Migration

## Getting Started
Steps for local environment

### Prerequisites

1. [PHP 7.4](https://www.php.net/)
2. [Composer 2.0](https://getcomposer.org/)

## Installation

1.Clone the Repo and change to that directory. All other commands assume that this is your present working directory.

```bash
git clone https://<username>@bitbucket.org/idg-dev/wpmigration.git
```

2.Install all the dependencies of node modules and vue modules

```bash
composer install
```

3.Compile code
```bash
composer dump-autoload -o
```

4.Inside in project-root/src/config/ Create GlobalConstant.php class with empty construct
```bash
public static string $MAPPING_DIR = 'dir path of mapping files ending with /';
public static string $LOG_DIR = 'directory path of log folder ending with /';
public static string $WP_SETUP = 'wp-load.php path of content hub set up';
public static string $WP_TAXONOMY_PATH = 'taxonomy.php path for content hub set up';
public static array $PARAMS = [
    'host' => 'xxxxx',
    'port' => 'xxxxx',
    'database' => 'xxxxx',
    'user' => 'xxxxx',
    'password'=> 'xxxxx'
    ];
```
