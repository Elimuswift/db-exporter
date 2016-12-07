[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/elimuswift/DbExporter/badges/quality-score.png?s=7bd2e14ca4097b979efa1d0d558c3ae17dd870bf)](https://scrutinizer-ci.com/g/elimuswift/DbExporter/)
[![Latest Stable Version](https://poser.pugx.org/elimuswift/db-exporter/v/stable.svg)](https://packagist.org/packages/elimuswift/db-exporter) [![Total Downloads](https://poser.pugx.org/elimuswift/db-exporter/d/total)](https://packagist.org/packages/elimuswift/db-exporter) [![Latest Unstable Version](https://poser.pugx.org/elimuswift/db-exporter/v/unstable.svg)](https://packagist.org/packages/elimuswift/db-exporter) [![License](https://poser.pugx.org/elimuswift/db-exporter/license.svg)](https://packagist.org/packages/elimuswift/db-exporter)

# Database Exporter

Export your database quickly and easily as a Laravel Migration and all the data as a Seeder class. This can be done via artisan commands or a controller action.


Please note that I've only tested this package on a **MySQL** database. It has been confirmed it does not work with [Postgres](https://github.com/elimuswift/DbExporter/issues/17#issuecomment-56990481).

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
 Elimuswift\DbExporter\DbExportHandlerServiceProvider::class
```

(Optional) Publish the configuration file.

```
php artisan vendor:publish --provider="Elimuswift\DbExporter\DbExportHandlerServiceProvider"
```

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
*Important: This **requires your database config file to be updated in `app/config/database.php`**.*


#### Uploading migrations/seeds to remote server
**Important: This requires your config/remote.php to be configured.**

**Important: The package configuration remote key needs to be configured to correspond to your remotes directory structure.**


You can with the following command, upload migrations and / or seeds to a remote host with `php artisan db-exporter:remote remoteName [--migrations] [--seeds]`

For instance **to upload the migrations to the production server:**

```
php artisan db-exporter:remote production --migrations
```
Or **upload the seeds to the production server:**

```
php artisan db-exporter:remote production --seeds
```
Or even combine the two:

```
php artisan db-exporter:remote production --migrations --seeds
```

***

### From a controller / route

#### Database to migration

##### Export current database

**This requires your database config file to be updated.** The class will export the database name from your `config/database.php` file, based on your 'default' option.


Make a export route on your development environment

```php

Route::get('export', function()
{
    DbExportHandler::migrate();
});
```

##### Export a custom database

```php

Route::get('export', function()
{
    DbExportHandler::migrate('otherDatabaseName');
});
```

#### Database to seed


This will write a seeder class with all the data of the current database.

```php

Route::get('exportSeed', function()
{
    DbExportHandler::seed();
});
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

DbExportHandler::migrate()->seed();

```
Or with:

```php

DbExportHandler::migrateAndSeed();

```
**Important :** Please note you cannot set a external seed database.
If you know of a way to connect to a external DB with laravel without writing in the app/database.php file [let me know](http://www.twitter.com/elimuswift).


#### Ignoring tables
By default the migrations table is ignored. You can add tabled to ignore with the following syntax:

```php

DbExportHandler::ignore('tableToIgnore')->migrate();
DbExportHandler::ignore('tableToIgnore')->seed();

```
You can also pass an array of tables to ignore.



## Credits
Credits to **@nWidart** the original creator of package (which goal was to generate migrations from a database). Sadly I couldn't get it working as-is, so I decided to rewrite the package to fit the latest versions of laravel, and added a couple a features of my own.

## License (MIT)

Copyright (c) 2016 [Albert Leitato](http://www.elimuswift.com) , wizqydy@gmail.com

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

