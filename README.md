# Serwisant Online Customer Panel PHP SDK

This package contains complexcustomer panel application including PHP code, GraphQL queries, Twig templates and CSS/JS
assets.

Layout is powered by  [Bootstrap](https://getbootstrap.com/) and like whole application is Apache licenced.

## Requirements:

* PHP 7.2 or higher
* all requirements by `serwisant/serwisant-api` package

## Word about versioning

This is special package. Because it contains complex application we can't provide long term compatibility. There is high
risk implementing breaking changes once new features will be added. So when you're building own application, with custom
modifications of templates, assets, logic ***please specify explicite version*** or at least major in your `
composer.json i.e.:

```
"require": {
    "serwisant/serwisant-cp": "1.0.*"
},
```

All breaking changes will be increasing major version by one, i.e. "1.0.5" -> "1.1.1".

If you installing it as-is you can include any version (`*`), to get upgrades.

***YOU HAVE BEING WARNED.***

## Usage

Require package in `composer.json`, next create `index.php` including composer autoload and application bootstrap. Don't
forget to override apache/nginx config to point every single request, except `/assets` and `/webfints` to
your `index.php` (`.htaccess` file).

Typical directory structure looks like
in [reference implementation repository](https://github.com/SerwisantOnline/serwisant-cp-php).

### Bootstrap

Typical, minimal bootstrap looks like below.

```php
$oauth_key = getenv('OAUTH_KEY');
$oauth_secret = getenv('OAUTH_SECRET');

use Serwisant\SerwisantCp;
use Serwisant\SerwisantApi;

$application = new SerwisantCp\Application();

$application->setPublicAccessToken(new SerwisantApi\AccessTokenOauth(
  $oauth_key,
  $oauth_secret,
  'public',
  new SerwisantApi\AccessTokenContainerEncryptedFile(sha1($oauth_secret))
));

$application->setCustomerAccessToken(new SerwisantApi\AccessTokenOauthUserCredentials(
  $oauth_key,
  $oauth_secret,
  'customer',
  new SerwisantApi\AccessTokenContainerSession()
));

$application->setRouter(new SerwisantCp\ApplicationRouter());

$application->run();
```

#### setPublicAccessToken

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

#### setCustomerAccessToken

Customer access token is kept in session. You can keep it into other storage but you must remember that a customer
access token is user specific, each logged in customer has its own token related to username/password.

#### setRouter

This is default path/routes set. If you want to create own pages create a new router by extending `ApplicationRouter`.

### Assets

Application contains complex assets to provide a frontend layout via CSS and functionality via JS. Also, those assets
have some dependencies from other JS libraries.

To get frontend working you must to:

- install those dependencies:

```
    "@fortawesome/fontawesome-free": "^5.15.3",
    "bootstrap": "^5.0.1",
    "bootstrap-cookie-alert": "^1.2.1",
    "datetimepicker": "^0.1.39",
    "filepond": "^4.27.2",
    "filepond-plugin-image-preview": "^4.6.6",
    "jquery": "^3.6.0",
    "lodash": "^4.17.21"
```

- copy all files from package's asset directory to your public directory.

It's strongly recommended to use `npm` tool for that. In `package.json` define all required dependencies and call
post-install script provided with this package (`npm-postinstall.js`).

Sample  `package.json` file can be found
in [reference implementation repository](https://github.com/SerwisantOnline/serwisant-cp-php/blob/main/package.json).
