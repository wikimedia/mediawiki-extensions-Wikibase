# 14) Make Wikibase.git a monorepo {#adr_0014}

Date: 2020-08-05

## Status

accepted

## Context

Wikibase-related code is found in various places and under different degrees of isolation.
Parts of the PHP code, especially early / fundamental parts like the data model and its serialization,
are independent libraries in separate Git repositories, distributed via Composer.
Some JavaScript code, such as the new Termbox,
is also developed independently, and typically included as a Git submodule.
On the other hand, much of Wikibase is included directly in the Wikibase Git repository,
often as part of the monolithic “Lib” component.

Part of the goal of the Decoupling Hike (June to August 2020) has been to develop a strategy for refactoring this “Lib” component.
We found that the separate libraries have obvious benefits thanks to being stand-alone, separate components,
but also have downsides for development:
it is inconvenient to have to develop the libraries in separate repository,
and cumbersome to get the changes back into the main Wikibase Git repository.
At best, a submodule pointer needs to be updated;
at worst, the library needs to publish a new release, which then needs to make its way into [mediawiki/vendor][].
We investigated a “monorepo” approach in [T254920][],
and propose that it offers the best of both worlds.
Monorepos are also used by others, including the Symfony PHP framework
([blog post][Symfony blog post], [talk video][Symfony talk video]).

## Decision

Wikibase.git will become a monorepo,
containing not just the overall MediaWiki extension code
but also the code and history of libraries that can stand on their own.
Changes to those libraries become immediately effective within Wikibase,
but are also published separately.

Where possible, sections of Wikibase Lib will be extracted into separate libraries.
Dependencies on MediaWiki are removed
(or replaced with suitable MediaWiki libraries, e.g. [wikimedia/timestamp][] instead of `wfTimestamp()`).
Their source code is moved from subdirectories of `lib/includes/` and `lib/tests/phpunit/` into a subdirectory of `lib/packages/`
(adjusting the paths in extension `AutoloadNamespaces` and elsewhere),
named after the prospective Composer package name,
and a `composer.json` file is added there.
The [git filter-repo][] tool can then be used to extract a subset of the Wikibase Git history with only the changes relevant to the new library;
this new read-only repository can be used as the VCS source of the Composer package,
and is automatically updated through a GitHub action (see e.g. `.github/workflows/filterChanges.yml`).
The Decoupling Hike team demonstrated these steps for the [wikibase/changes][] library;
see [T256058][] and related tasks for details.

Formerly stand-alone libraries will be merged into the Wikibase Git repository.
Their history will be preserved, and they will also be extracted into separate Git repositories again,
using the [git filter-repo][]-based process outlined above.
We expect that it will be possible to produce identical Git hashes,
making this migration transparent to other users of the libraries’ Git repositories –
the repositories will simply no longer be the main source of truth.
The Decoupling Hike team has not done this for any existing library.

## Consequences

Code that was formerly part of Lib will become more stand-alone,
less coupled to MediaWiki core and Wikibase,
while still being developed in mostly the same fashion.

Libraries that were standalone will remain independent,
but development for them will take place in the Wikibase Git repository.
Changes to them will become effective immediately,
without having to wait for the next release and [mediawiki/vendor][] update.
“Tree-wide” changes, such as code style updates, will be easier to apply.

For new components, the source code strategy will be clear:
they are expected to be developed in Wikibase,
but with a setup that also extracts them as a separate Git repository
(using [git filter-repo][]) right from the beginning.
This removes a source of uncertainty for new projects.

Publishing these packages as separate repositories to GitHub is done for the following benfits:
* being able to see their isolated history and contributers, e.g. [wikibase changes contributors][]
* being able to run their tests in CI in isolation without surrounding Wikibase code being present, e.g. [wikibase changes Travis CI][]

[mediawiki/vendor]: https://gerrit.wikimedia.org/g/mediawiki/vendor/
[T254920]: https://phabricator.wikimedia.org/T254920
[Symfony blog post]: https://symfony.com/blog/symfony2-components-as-standalone-packages
[Symfony talk video]: https://www.youtube.com/watch?v=4w3-f6Xhvu8
[wikimedia/timestamp]: https://packagist.org/packages/wikimedia/timestamp
[git filter-repo]: https://github.com/newren/git-filter-repo/
[wikibase/changes]: https://packagist.org/packages/wikibase/changes
[T256058]: https://phabricator.wikimedia.org/T256058
[wikibase changes contributors]: https://github.com/wikimedia/wikibase-changes/graphs/contributors
[wikibase changes Travis CI]: https://travis-ci.org/github/wikimedia/wikibase-changes
