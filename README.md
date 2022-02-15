# Serwisant Online Customer Panel PHP SDK

This package contains complexcustomer panel application including PHP code, GraphQL queries, Twig templates and CSS/JS
assets. Whole application is based on [Silex micro-framework](https://web.archive.org/web/20200813150217/https://silex.symfony.com/doc/2.0/)

Layout is powered by  [Bootstrap](https://getbootstrap.com/) and like whole application is Apache licenced.

## Requirements:

* PHP 7.4 or higher
* all requirements by `serwisant/serwisant-api` package

## Word about versioning

This is special package. Because it contains complex application we can't provide long term compatibility. There is high
risk implementing breaking changes once new features will be added. So when you're building own application, with custom
modifications of templates, assets, logic ***please specify explicite version*** in your `composer.json` i.e.:

```
"require": {
    "serwisant/serwisant-cp": "1.0.0"
},
```

If you installing it as-is, and no modification will be made, you can include major version (`^1.0`), to get upgrades.

***YOU HAVE BEING WARNED.***

## Usage

Require package in `composer.json`, next create `index.php` including composer autoload and application bootstrap. Don't
forget to override apache/nginx config to point every single request, except `/assets-serwisant-cp` and `/webfints` to
your `index.php`. It can be done witj a `.htaccess` file:

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

Typical directory structure looks like
in [reference implementation repository](https://github.com/SerwisantOnline/serwisant-cp-php).

Of course you can attach this module to existing application.

### Bootstrap

Typical, minimal bootstrap looks like below.

```php
$oauth_key = getenv('OAUTH_KEY');
$oauth_secret = getenv('OAUTH_SECRET');

use Serwisant\SerwisantCp;
use Serwisant\SerwisantApi;

$application = new SerwisantCp\Application();

$application->set('access_token_public', new SerwisantApi\AccessTokenOauth(
  $oauth_key,
  $oauth_secret,
  'public',
  new SerwisantApi\AccessTokenContainerEncryptedFile(sha1($oauth_secret))
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

#### set public access token

Public access token is stored in encrypted local file via `AccessTokenContainerEncryptedFile`. By default system `/tmp`
is used for that purpose. You can change TMP dir for whole module by setting a `TMPDIR` env variable, i.e.:

```php
putenv('TMPDIR=' . realpath('/path/to/tmp/dir'));
```

If you running application in distributed, multi-server environment, or you want tio increase performance use SQL
database container:

```
new SerwisantApi\AccessTokenContainerPDO(['mysql:dbname=db;host=127.0.0.1', 'user', 'password'])
```

Public access token is common for all users - it's generated in first request, cached and re-used by other users.

#### set customer access token

Customer access token is kept in session. You can keep it into other storage but you must remember that a customer
access token is user specific, each logged in customer has its own token related to username/password.

#### setRouter

This is default path/routes set. If you want to create own pages or change prefix paths create a new router by extending `Router`.
By default you need to mount 

### Assets

Application contains complex assets to provide a frontend layout via CSS and functionality via JS. Also, those assets
have some dependencies from other JS libraries.

To get frontend working you must to:

- install those dependencies:

```json
{
  "dependencies": {
    "@fortawesome/fontawesome-free": "^5.15.3",
    "bootstrap": "^5.1.0",
    "bootstrap-cookie-alert": "^1.2.1",
    "bootstrap-select": "1.14.0-beta2",
    "datetimepicker": "^0.1.39",
    "filepond": "^4.27.2",
    "filepond-plugin-file-validate-type": "^1.2.6",
    "filepond-plugin-image-preview": "^4.6.6",
    "jquery": "^3.6.0",
    "lodash": "^4.17.21"
  }
}
```

- copy all files from package's asset directory to your public directory.

It's strongly recommended to use `npm` tool for that. In `package.json` define all required dependencies and call
post-install script provided with this package (`npm-postinstall.js copy`).

Sample  `package.json` file can be found
in [reference implementation repository](https://github.com/SerwisantOnline/serwisant-cp-php/blob/main/package.json).
