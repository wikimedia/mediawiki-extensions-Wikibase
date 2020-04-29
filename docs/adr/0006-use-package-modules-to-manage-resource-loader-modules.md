# 6) Use Package Modules for dependency management within ResourceLoader modules  {#adr_0006}

Date: 2019-10-24

## Status

accepted

## Context

Until recently, frontend dependencies in Wikibase were managed through globally registered [ResourceLoader] modules with
no distinction between internal dependencies and modules that needed to be publicly available. This resulted in more
than 250 [ResourceLoader] modules with a very complex dependency relationship. The names of all modules are loaded with
every page view, affecting page load time and network overhead. See [T228513]

We considered two options to reduce the number of globally registered ResourceLoader modules:
webpack and [ResourceLoader] [Package Modules].

Solving the problem with webpack would have involved the introduction of a build step to create bundles for each of the
top-level (=entry point) modules. Advantages of this approach are superior minification at build time - better than the
ResourceLoader minification, and all the other benefits of introducing a build step such as transpilation from more modern ES versions,
and easier reuse of 3rd party npm libraries. The risks we identified for this approach include;

- Having to generate and check in the bundled js files in addition to their source. That would lead to most patches having a merge conflict.
- Having to rewrite tests for internal files that can no longer run within the QUnit ResourceLoader test suite.
- Webpack is not yet widely adopted within the MediaWiki ecosystem.
- Putting in too much work into (legacy) parts of the code that are not going to change any time soon.

For [Package Modules], we agreed that we would be missing out many of the positive side effects of introducing webpack and
the build step. On the other hand, this solution would require fewer code changes, and would be the recommended MediaWiki way to solve the problem at hand.

## Decision

We decided to go with [Package Modules] instead of webpack and reduced the number of modules that Wikibase registers from 260 to 85.
The reason we went with Package Modules was:

- It's easier to migrate such a huge codebase to [Package Modules].
- The tests are easier to adapt given the current paradigm of running QUnit tests in RL and browser context.
- If we change our mind later and switch to webpack, it would be still doable and easier than now given that both understand require.
- The rebase hell that would come with checking the dist files in VCS.

We still went with webpack in the submodules that work independently of Wikibase (like WikibaseSerializationJavaScript and
WikibaseDataModelJavaScript) to run the tests in the context of karma + webpack + node but at the same time ResourceLoader understands
those notations when the code is being pulled as a RL module in the Wikibase extension (as a submodule).

## Consequences

By internalizing many of the previously globally registered ResourceLoader modules, we reduced the size of the startup manifest in [Startup Module].
Since the startup manifest is loaded on every page view, we reduce overall traffic with each removed module.
In addition, the use of `require()` to load dependencies makes development and debugging easier.
For statistics representing the impact, see [T232492]

## For future

Use [Package Modules] instead of modelling internal dependencies with [ResourceLoader] modules.

[ResourceLoader]: https://www.mediawiki.org/wiki/ResourceLoader
[Package Modules]: https://www.mediawiki.org/wiki/ResourceLoader/Package_modules
[Startup Module]: https://www.mediawiki.org/wiki/ResourceLoader/Architecture#Startup_Module
[T228513]: https://phabricator.wikimedia.org/T228513
[T232492]: https://phabricator.wikimedia.org/T232492
