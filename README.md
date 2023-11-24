# Serwisant Online Customer Panel PHP SDK

This package contains complexcustomer panel application including PHP code, GraphQL queries, Twig templates and CSS/JS
assets. Whole application is based
on [Silex micro-framework](https://web.archive.org/web/20200813150217/https://silex.symfony.com/doc/2.0/)

Layout is powered by [Bootstrap](https://getbootstrap.com/) and like whole application is Apache licenced.

## Requirements:

* PHP 7.4 or higher

### development/deploy requirements

* composer (to install PHP dependencies)
* node 10.x or higher and NPM (to build assets)

## Word about versioning

This is special package. Because it contains complex application we can't provide long term backward compatibility.

There is high risk of implementing breaking changes once new features will be added. So when you're building own
application, with custom modifications of templates, assets, logic ***please specify explicite version*** in
your `composer.json` i.e.: `"1.0.0"`. If you installing it as-is, and no modification will be made, you can include
major version (`1.*`), to get upgrades.

***YOU HAVE BEING WARNED.***

## Usage

### Typical files layout

You shold create basic directory and files tree.

```
|--/public
|  /public/.htaccess
|  /public/index.php
|
|--/composer.json
|--/package.json
```

`public` must be root of your webserver. In this directory yoy must create two other files: `index.php` (see Bootstrap
section) and `.htaccess` (see Webserver configuration section). Please note: two otger directories will be created there
when you will build assets.

`composer.json` is a PHP depencency configuration. You need a `composer` tool to install it.
`package.json` is a frontend dependency configuration. Tou need a `npm` tool to install it. See Assets section.

### PHP depeldencies

To install all required depenednecies for your web application you must to create a file `composer.json`:

```json
{
  "name": "my_application",
  "require": {
    "serwisant/serwisant-cp": "1.*"
  },
  "autoload": {
    "psr-0": {
      "Serwisant": "src"
    }
  }
}
```

Once file is created run `composer install`. It will produce a `vendor` directory with many sub-directorues and files
inside.

### Assets (JS, CSS, images)

Application contains complex assets to provide a frontend layout via CSS and functionality via JS. Also, those assets
have some dependencies from other JS libraries.

To get frontend working you must to create `package.json`:

```json
{
  "name": "My Application",
  "private": true,
  "scripts": {
    "postinstall": "node vendor/serwisant/serwisant-cp/npm-package/build.js"
  },
  "dependencies": {
    "serwisant-cp": "file:vendor/serwisant/serwisant-cp/npm-package"
  }
}
```

and run `npm install`. Once all JS dependencies are installed additional script will run, and will generate a files
in `public/assets-serwisant-cp` and `public/webfonts`.

***Please note: JS dependencies must be insaled after PHP dependencies.***

Please note: once you'll install all JS dependencies, you can remove whole `node_modules` directory before you start
upload files to webserver.

### Server configuration

All dependencies can be installed locally, on your terminal, and then FTP/SFTP/SCP can be used tu send all files to your
webserver. If you have access to shell on webserver and `composer`/`npm` tools are installed there you can send a basic
files and run instalaction directly on webserver.

Most important thing is to set your webserver to `public` sub-directory, not to root directory. It's for security
reasons.

Don't forget to override apache/nginx config to point every single request to your `index.php`. It can be done with
a `.htaccess` file:

```
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteRule ^index\.php$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.php [L]
</IfModule>
```

### Bootstrap

Typical, minimal bootstrap looks like below.

```php
$oauth_key = 'YOUR_OAUTH_KEY';
$oauth_secret = 'YOUR_OAUTH_SECRET';

require_once('./vendor/autoload.php');

use Serwisant\SerwisantCp;
use Serwisant\SerwisantApi;

$application = new SerwisantCp\Application();

$application->set('access_token_public', new SerwisantApi\AccessTokenOauth(
  $oauth_key,
  $oauth_secret,
  'public',
  new SerwisantApi\AccessTokenContainerEncryptedFile(sha1($oauth_key))
));

$application->set('access_token_customer', new SerwisantApi\AccessTokenOauthUserCredentials(
  $oauth_key,
  $oauth_secret,
  'customer',
  new SerwisantApi\AccessTokenContainerSession()
));

$application->setRouter(new SerwisantCp\ApplicationRouter());

$application->run();
```

#### $application->set('access_token_public',...

Public access token is stored in encrypted local file via `AccessTokenContainerEncryptedFile`. By default system `/tmp`
is used for that purpose. You can change TMP dir for whole module by setting a `TMPDIR` env variable, i.e.:

```php
putenv('TMPDIR=' . realpath('/path/to/tmp/dir'));
```

If you running application in distributed, multi-server environment, or you want tio increase performance use SQL
database container:

```php
new SerwisantApi\AccessTokenContainerPDO(['mysql:dbname=db;host=127.0.0.1', 'user', 'password'])
```

Public access token is common for all users - it's generated in first request, cached and re-used by other users.

#### $application->set('access_token_customer',...

Customer access token is kept in session. You can keep it into other storage but you must remember that a customer
access token is user specific, each logged in customer has its own token related to username/password.

Session store by default will use files in global, system temporary folder. You can configure own session store, ie.
database in bootstrap file. See section with advanced topics.

#### $application->setRouter(...

This is default path/routes set. If you want to create own pages or change prefix paths create a new router by
extending `Router`. By default you need to mount

## Advanced topics, modyfying application

First of all. ***Don't modyfy/edit any part of code from module directly***. If version will change, and you'll be
forced tu update - all your edits will disapear and you'll need to re-implement it.

Application have some features, that allow apply modifications without touching internnal code.

### Custom views and translations

By default all views are stored inside of `views/` folder and translations in `translations/` inside of module.
You can overwrite views and translations, also you can add your own language translations. When initializing appliation
in bootstrap file, pass additional paths to directories from your application.

For views: put in configured folder files with the same names as original view. Your custom files will take priority
over oryginal views. It's strongly reccomended to do not create custom views from a scratch. Copy oryginal view to
your custom folder and tune it.

For translations: prepare additional translation in your language - you should use `translations/pl.yml` as base. Then
put new file in application directory and add to argument. Application will detect all available translations, and will
serve it using customer browser language settings. If no translation is available, default can be used.

```php
$application = new SerwisantCp\Application('production', ["./views"], [], ["./translations/en.yml", "./translations/de.yml"], 'en_GB');
```

Arguments:

- environment, use `production`
- array of directories (full paths) with alternate views
- (not a scope of this documentation)
- array of translation files
- default translation, if none match

### Custom modifiers

Overwrite logic related to location/translation detection, including translations, timezones, etc. Extend oryginal class
to use static language and use it with `set` method.

```php
class LocaleDetector extends \Serwisant\SerwisantCp\LocaleDetector
{
    public function __construct()
  {
    $this->default_locale = 'pl_PL';
    $this->locale = 'pl_PL';
    $this->country = (new \PragmaRX\Countries\Package\Countries())->where('cca2', explode('PL')->first();
  }
}
$application->set('locale_detector', new LocaleDetector());
```

You can use database session-store - pass new instance of PdoSessionHandler suplied with proper arguments with
database credentials.

```php
$application->set('session_handler', new \Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler(...))
```

## Major changes

- 1.0 - initial release
- 1.1.0 - changed way how assets are built, new approach is not compatibile with previous one