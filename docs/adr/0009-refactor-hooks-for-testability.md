# 9) Refactor hooks for testability {#adr_0009}

Date: 2020-03-04

## Status

accepted

## Context

Currently, the `RepoHooks` class remains largely untested due to a combination of two factors:

1. The methods in this class are static, and we do not own the contract under which they should be called, as they are
   defined as hooks in `extension.json` or as global variables in the entrypoints e.g. [extensions/Wikibase/repo/Wikibase.php:1020](https://github.com/wikimedia/mediawiki-extensions-Wikibase/blob/7b20d22b3c0bbc37ad23f63e38fadc9b1f2ca057/repo/Wikibase.php#L1020), which means we cannot easily refactor the methods to increase testability

2. Methods rely heavily on the `WikibaseRepo` singleton and it's store, which make it harder to test, as there is no
   way to to inject a mock of `WikibaseRepo` without dependency injection.

A [RFC for enabling dependency injection](https://phabricator.wikimedia.org/T240307) in hooks is currently under way.
However, an interim solution is needed in order to mitigate the amount of untested logic that exists in that file
and other places in the codebase.

While reviewing this issue, two initial solutions were considered:

- Refactor `RepoHooks` into a singleton itself, so that when instantiated, we can inject a Mock of `WikibaseRepo`
  instead of using the real deal.
- Adopt a pattern used in `WikibaseClient` Which enables us to mock several parts of it (namely the store), and replace
  the real store by creating an `overrideStore` method. See in following:
    - [`client/tests/phpunit/includes/MockClientStore.php`](https://github.com/wikimedia/mediawiki-extensions-Wikibase/blob/master/client/tests/phpunit/includes/MockClientStore.php)
    - [`client/tests/phpunit/includes/DataAccess/ParserFunctions/PropertyParserFunctionIntegrationTest.php:42`](https://github.com/wikimedia/mediawiki-extensions-Wikibase/blob/master/client/tests/phpunit/includes/DataAccess/ParserFunctions/PropertyParserFunctionIntegrationTest.php#L42)

However, after a discussion, it was decided to implement a middle ground, that would enable us to gradually refactor
hooks, rather than a one time big change.

## Decision

It was decided to adopt an existing pattern in Wikibase repo where each hook handler gets its own singleton class and
provides at least four methods:

- A constructor to make dependency injection easier.
- A public static method to bootstrap an instance from global settings. For consistency, this would typically be named
  `newFromGlobalSettings`.
- (optional) A public static method to get a cached instance of the handler object (rather than instantiate it each time):
  This is useful for hooks handlers which are called several times.
- A public static method to wire up functionality into the hooks system and should contain little to no logic (as it is
  hard to test without complete integration or e2e tests).
- A public method to perform the actual handler logic in a testable way.

This class should be placed under the `includes/Hooks` directory. An example of this pattern can be seen in:
https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Wikibase/+/574495

## Consequences

- Increase in the ability to test code for hooks, as well as general test coverage.

- Deciding on a single source of truth regarding hooks will encourage common understanding and help newcomers to the
  code refactor and write their own hooks.

- Decoupling the tests from the actual store might make tests run faster, as no connections to the Database / reads or
  writes will be made.

- Adopting a pattern regarding testing hooks, can help guide us in refactoring and writing new
  pieces of code which are considered "hard to test".

- This doesn't provide a pattern for end-to-end testing of hooks, i.e. running of the public static method for hook
  wiring isn't covered by this pattern.
