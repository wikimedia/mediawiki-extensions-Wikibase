# API integration tests

These tests check that Wikibase works as expected through the API.
See [mw:MediaWiki API integration tests][] for more information on how to run or write these tests.

Some of the tests in here require the Wikibase CI settings in order to pass.
Add something like the following to your `LocalSettings.php`:

```php
require_once "$IP/extensions/Wikibase/repo/config/Wikibase.ci.php";
```

(If some tests still fail, check that none of the CI settings are overwritten by later parts of your `LocalSettings.php`.)

[mw:MediaWiki API integration tests]: https://www.mediawiki.org/wiki/MediaWiki_API_integration_tests
