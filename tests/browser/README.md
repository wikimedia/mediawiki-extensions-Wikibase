Wikidata Browser Tests
======================

This directory contains the browser tests for
[Wikidata](https://gerrit.wikimedia.org/r/#/admin/projects/mediawiki/extensions/Wikidata).

## Executing tests

Update/install gems:
```shell
bundle install
```

Switch to the `tests/browser/` directory to run all tests:
```shell
bundle exec cucumber
```

Run a specific feature:
```shell
bundle exec cucumber features/label.feature
```

Run a specific scenario:
```shell
bundle exec cucumber features/label.feature:17
```

Run only tests with a specific tag:
```shell
bundle exec cucumber --tag @ui_only
```

Run only tests that are supposed to be executed locally:
```shell
bundle exec cucumber --tag @local_config
```

## Configuration and setup

For setup and configuration please see
[Browser Testing for Wikidata](https://www.mediawiki.org/wiki/Wikibase/Programmer%27s_guide_to_Wikibase#Browser_Testing_for_Wikidata).
