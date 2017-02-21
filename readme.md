[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Elimuswift/db-exporter/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Elimuswift/db-exporter/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/elimuswift/db-exporter/v/stable.svg)](https://packagist.org/packages/elimuswift/db-exporter) [![Total Downloads](https://poser.pugx.org/elimuswift/db-exporter/d/total)](https://packagist.org/packages/elimuswift/db-exporter) [![Latest Unstable Version](https://poser.pugx.org/elimuswift/db-exporter/v/unstable.svg)](https://packagist.org/packages/elimuswift/db-exporter) [![License](https://poser.pugx.org/elimuswift/db-exporter/license.svg)](https://packagist.org/packages/elimuswift/db-exporter)

# Database Exporter

Export your database quickly and easily as a Laravel Migration and all the data as a Seeder class. This can be done via artisan commands or a controller action.


Please note that I've only tested this package on a **MySQL** database. It has been confirmed it does not work with Postgres
## Installation

Add `"elimuswift/db-exporter"`* as a requirement to `composer.json`:

```php
{
    ...
    "require": {
        ...
		"elimuswift/db-exporter": "1.0"
    },
}

```

Update composer:

```
$ php composer.phar update
```

Add the service provider to `config/app.php`:

```php
 Elimuswift\DbExporter\DbExporterServiceProvider::class
```

(Optional) Publish the configuration file.

```
php artisan vendor:publish --provider="Elimuswift\DbExporter\DbExporterServiceProvider"
```

After publishing the config file make sure you change storage location for migrations and seeds.

*Use `dev-master` as version requirement to be on the cutting edge*


## Documentation

### From the commandline

#### Export database to migration

**Basic usage**

```
php artisan db-exporter:migrations
```

**Specify a database**

```
php artisan db-exporter:migrations otherDatabaseName
```

**Ignoring tables**

You can ignore multiple tables by seperating them with a comma.

```
php artisan db-exporter:migrations --ignore="table1,table2"
```

#### Export database table data to seed class
This command will export all your database table data into a seed class.

```
php artisan db-exporter:seeds
```
*Important: This **requires your database config file to be updated in `config/database.php`**.*


#### Uploading migrations/seeds to Storage Disk


**Important: The package backup destinations paths should match your desired disk location


You can backup migrations and / or seeds to a storage any disk that you application supports.


```
php artisan db-exporter:backup --migrations
```
Or **upload the seeds to the production server:**

```
php artisan db-exporter:backup --seeds
```
Or even combine the two:

```
php artisan db-exporter:backup --migrations --seeds
```

***


##### Export current database

**This class will export the database name from your `config/database.php` file, based on your 'default' option.



```php

    DbExporter::migrate();

```

#### Export a custom database

```php

    DbExporter::migrate('otherDatabaseName');

```

#### Database to seed


This will write a seeder class with all the data of the current database.

```php

    DbExporter::seed();

```
#### Seed a custom database
Just pass the nameof the database to be seeded.

```php

    DbExporter::seed('myOtherDB');

```
Next all you have to do is add the call method on the base seed class:

```php

$this->call('nameOfYourSeedClass');

```

Now you can run from the commmand line:

* `php artisan db:seed`,
* or, without having to add the call method: `php artisan db:seed --class=nameOfYourSeedClass`

#### Chaining
You can also combine the generation of the migrations & the seed:

```php

DbExporter::migrate()->seed();

```
Or with:

```php

DbExporter::migrateAndSeed();

```

#### Ignoring tables
By default the migrations table is ignored. You can add tabled to ignore with the following syntax:

```php

DbExporter::ignore('tableToIgnore')->migrateAndSeed();
DbExporter::ignore('table1','table2','table3')->migrateAndSeed();


```
You can also pass an array of tables to ignore.



## Credits
Credits to **@nWidart** the original creator of the package [DbExporter](https://github.com/nWidart/DbExporter). I couldn't get it working as-is, so I decided to rewrite the package to fit the latest versions of laravel, and added a couple a features of my own.


