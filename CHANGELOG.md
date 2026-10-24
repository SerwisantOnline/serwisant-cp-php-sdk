# Changelog

All notable changes to `serwisant/serwisant-cp` are documented in this file.
Entries up to 1.6.3 were reconstructed from git history.

## 1.7.0 (unreleased)

### Breaking changes

- Minimum PHP version raised to **8.1** (was 7.4); `ext-intl` is now required.
- Abandoned `silex/silex` replaced with [serwisant/serwisant-silex](https://packagist.org/packages/serwisant/serwisant-silex)
  built on current Symfony components (`^6.4 || ^7.4`). The `Silex\` namespace used in `Router`, `ActionDecorator`,
  `Action`, `Application` and Twig extensions is now `Serwisant\Silex\` - update type hints in your routers/decorators.
- Twig upgraded to `^3.21`; `twig_escape_filter()` replaced with `EscaperRuntime`.
- `pragmarx/countries` dropped in favour of `symfony/intl` and `giggsey/libphonenumber-for-php-lite`.
  `CountryDatabase` and `CountryDatabaseHelper` classes removed, `LocaleDetector::$country` property removed.
  Country and phone prefix lists are now localised to the panel language and sorted with `Collator`;
  phone prefixes are returned with a leading `+`.
- `devimteam/silex-cors-provider` replaced with `Serwisant\Silex\Provider\CorsServiceProvider`.
- `composer/package-versions-deprecated`, `symfony/asset`, `symfony/twig-bridge` dependencies removed.
- Session cookie is now `httponly`.
- `serwisant/serwisant-api` bumped to `~3.6.0`.

### Added

- `Application::boot()` - idempotent registration of services, providers and routes.
- `Application::handle(Request): Response` - processes a request without sending the response, for tests and embedding.
- `api_http_client` application service - inject a custom Guzzle client into `Api` (e.g. `MockHandler` in tests).
- `Action::requestParam()` - reads a parameter from route attributes, query or body; replaces the deprecated
  `Request::get()` across all actions and traits.
- Flag images `pol.svg` and `gbr.svg` shipped in `npm-package/img` instead of being copied from `pragmarx/countries`.

### Fixed

- `Translator::codeToCurrencySymbol()` is locale-aware and falls back to the ISO code for unknown currencies.
- `LocaleDetector::timeZone()` falls back to `UTC` when no timezone is known for the country.
- `sessionStart()` no longer fails when a session is already active (multiple `handle()` calls in one process).
- Production bootstrap without the optional `assets_version` service no longer fails with `UnknownIdentifierException`
  (the minimal bootstrap from README was affected).
- Repair panel no longer breaks when `repair.device` is not present in the query result.

### Migration notes

Verified with the minimal bootstrap from README: a project that installs the panel **as-is** (`"serwisant/serwisant-cp": "1.*"`,
no custom classes, views or assets) needs no code changes - `composer update` followed by `npm install` is enough.
Note that fresh installs of 1.0-1.6.x are no longer possible anyway: they depend on Symfony 3.4, which Composer refuses
to install because of security advisories; existing lock files keep working until the next update.

1. **Platform**: PHP >= 8.1 and `ext-intl`. On older PHP or without `intl` Composer keeps 1.6.x (it reports
   `requires php >=8.1` / `requires ext-intl`) - upgrade the platform first.
2. **Composer**: run `composer update serwisant/serwisant-cp --with-all-dependencies` (Symfony 3.4/4.4, Twig 2, `silex/silex`,
   `pimple` 3 are replaced). If your `composer.json` pins `silex/silex`, `devimteam/silex-cors-provider`, `pragmarx/countries`,
   `symfony/*` < 6.4, `twig/twig` 2.x or `serwisant/serwisant-api` ~3.5, remove or relax those constraints.
3. **Assets**: run `npm install` again - `npm-package` now ships its own flag images and the build output changed.
4. **Custom routers, decorators, locale detectors, Twig extensions** (classes extending `Router`, `ActionDecorator`, `Routes`,
   `LocaleDetector`, `TwigExtensions`): replace `use Silex;` with `use Serwisant\Silex;` in type hints (`Silex\Application`,
   `Silex\ControllerCollection`). Remove any use of `CountryDatabase`, `CountryDatabaseHelper` and `LocaleDetector::$country`;
   country names come from `symfony/intl` (`Countries::getName()`), phone prefixes from `libphonenumber` and are returned with
   a leading `+`.
5. **Custom views** copied from 1.6.x are compiled by Twig 3. None of the package's own templates needed syntax changes,
   so yours most likely work as they are; the only constructs Twig 3 dropped are the ones already deprecated in Twig 2
   (`{% spaceless %}`, `{% filter %}`, `{% for ... if %}`, `sameas`/`divisibleby` spelling) - see the Twig 3 upgrade notes
   if you used them. Custom Twig extensions calling `twig_escape_filter()` must switch to `EscaperRuntime`. Keep in mind
   that in the `development` environment Twig runs with `strict_variables`, so a template reading an attribute that is
   absent from the GraphQL result throws there (production renders `null`, as before).
6. **Session**: the session cookie is `httponly` - custom JavaScript that read `PHPSESSID` must stop doing so (the package's
   own scripts never did).
7. **Actions** extending `Action` that called `$this->request->get()`: use `$this->requestParam()` (`Request::get()` is
   deprecated in Symfony 6.4 and removed in 8.0).
8. Optional: `Application::handle(Request)` and the `api_http_client` service allow end-to-end tests of the panel without
   network access - see README, section *Testing*.

## 1.6.3 - 2026-10-21

- PHP 8.3 compatibility (includes 8.3.36 fixing CVE-2026-109618 and CVE-2026-109619).

## 1.6.2 - 2025-12-13

- Ratings can be switched off via configuration.
- Performance optimisations (country database lookups).
- Bugfixes.

## 1.6.0 - 2025-09-09

- Name and surname of the submitter in anonymous repair and ticket forms.
- Email confirmation for anonymous applicants.
- Branding and visual form updates.
- Missing translations added.

## 1.5.0 - 2025-06-17

- Base version of "Rate Our Service" (ratings for repairs and tickets).
- Fixes.

## 1.4.0 - 2025-04-21

- Anonymous repair submission.
- Bugfixes.

## 1.3.0 - 2025-03-04

- `serwisant/serwisant-api` 3.4.0.
- Ticket submit and preview without logging into the panel.
- DSA: contact details in footers and on repair/ticket pages.
- Proper address handling on repair creation.
- Mobile device tuning.

## 1.2.0 - 2024-11-23

- Changed way of handling address in ticket submission form.

## 1.1.3 - 2024-07-31

- Device registration, repair for a device.
- Fix presentation of uploaded files.
- Bugfix: device list not visible on mobile screens.
- Bugfix: updating viewer failed with GQL syntax error.
- Bugfix: missing building number.
- `serwisant/serwisant-api` bump.

## 1.1.2 - 2024-01-17

- Additional prompt after repair creation.
- Language switcher and terms documents moved to a common route path.
- EN translations, updated searchable select and datetime picker controls.
- Fixes for PHP warnings/notices (`array_key_exists()` on null, undefined `HTTP_REFERER`,
  uninitialised `LocaleDetector::$default_locale`).
- Twig runtime error on null address fixed.

## 1.1.1 - 2023-10-04

- Critical fix: package builds and repair-by-token page.

## 1.1.0 - 2023-10-04

- Devices section in the customer panel.
- Assets compiled with the NPM builder.
- Ticket priorities hidden when only one priority is available.
- GraphQL schema definition updates.

## 1.0.21 - 2023-02-27

- Refactoring of view templates for easier extending, visual fixes.
- Handling of devices and schedules.
- Fix syntax error in ticket template.

## 1.0.20 - 2022-10-11

- Bugfix: phone number prefix detection, adding a new address on ticket and repair creation.

## 1.0.19 - 2022-09-19

- Custom repair and ticket status names.

## 1.0.18 - 2022-09-14

- Repair page: additional items and descriptions for files; ticket page: descriptions for files.
- Refactor.

## 1.0.17 - 2022-08-05

- Keep translations backward compatible.

## 1.0.16 - 2022-08-05

- Translation fixes, missing translations added.

## 1.0.15 - 2022-07-20

- Repair shipments information in the panel after login.

## 1.0.14 - 2022-07-06

- Bugfix: uploaded files were lost when the repair creation form had validation errors.

## 1.0.13 - 2022-06-20

- Single-query optimisation, token validation update.

## 1.0.12 - 2022-06-11

- `serwisant/serwisant-api` bumped to 3.1.16.

## 1.0.10 / 1.0.11 - 2022-04-04

- Separate page to update the customer's password.
- Form submit button disabled on submit to prevent double click.
- Misc bugfixes.

## 1.0.9 - 2022-03-02

- Bugfixes: ticket payment information, custom form fields on the repair form. Visual tweaks.

## 1.0.8 - 2022-02-16

- Datalist control replaced with JS select list.
- Panel access activation on password reminder and signup pages; activation links processed even when signups
  are turned off by the service.

## 1.0.7 - 2022-02-07

- New searchable select control for repair types.
- Fix JS error on new ticket form when file upload is turned off.

## 1.0.6 - 2022-02-06

- Customer self-activation when the account exists but panel login is not enabled.

## 1.0.5 - 2022-01-13

- Option to disable file attachments or allow images only.

## 1.0.4 - 2021-12-20

- Fixes for anonymous tokens, shortcut to own token-check page.

## 1.0.3 - 2021-11-27

- Option to require a GSM phone number on signup.
- SDK version bump.

## 1.0.2 - 2021-11-24

- Locale bugfixes.

## 1.0.1 - 2021-11-23

- Localisation support and fixes; `serwisant/serwisant-api` bumped for new localisation features.

## 1.0.0 - 2021-10-08

- First stable release: repairs, tickets, messages, online payments, signup/activation/password reset,
  repair cost acceptance, delivery method selection, printouts, PDO session handler, CORS preflight, 429 handling.
