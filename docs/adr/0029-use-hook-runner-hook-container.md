# 29) Use MediaWiki's HookContainer / HookRunner pattern for hooks {#adr_0029}

Date: 2025-04-17

## Status

accepted

## Context

MediaWiki 1.35 introduces a new hook system that improves static validation and discovery of parameter documentation for
hooks and hook runners. The [MediaWiki Hooks Docs] describe in more detail the motivations for updating the hook system.
The new hook system comprises three parts, all of which we want to adopt in order to take advantage of the improvements
and to align with MediaWiki development best practices:

 - Each hook has an associated interface. When we define a hook of our own, we define a corresponding interface.
 - When registering a hook handler, use HookHandlers rather than Hooks in extension.json, and make the handler class implement the relevant interface.
 - When calling a hook, use a HookRunner class, which implements the relevant interfaces.

Where the handler class implements an interface that belongs to a different extension,
we need to take care that we do not introduce new and undesired hard dependencies
between the extensions, and in particular need to ensure that the test suites of the extensions
continue to pass in the absence of optional dependencies.

## Considered Actions

The implications for our codebase would be:
 - Creating new HookRunners for executing hooks, organised by call site. This would mean at least one hook runner for Wikibase Repo and one for Wikibase Client
 - Creating interfaces for all Wikibase hooks for other extensions to use
 - Ensuring that present and future hook names comply with the proposed naming conventions
 - Updating extensions that call Wikibase hooks (WikibaseCirrusSearch, WikibaseLexeme, WikibaseLexemeCirrusSearch, WikibaseMediaInfo, WikibaseQualityConstraints, WikimediaBadges) to use the HookRunner pattern

We might also create tests to validate the naming of hooks as we have already done in the [EntitySchemaExtensionJsonTest].

## Decision

We will migrate our existing hooks and their usage in Wikibase and related extensions to the HookRunner pattern as recommended in the MediaWiki docs.
All future hooks will also comply with these conventions.

## Consequences

Wikibase and related extensions will register hook handlers using the 'HookHandlers' `extension.json` configuration option.
These HookHandlers will be constructible classes that implement the interfaces corresponding to the registered hooks.
Any hook executions from Wikibase code will use an appropriate HookRunner object.
Calls to Wikibase hooks from related extensions will also use an appropriate HookRunner object.

[MediaWiki Hooks Docs]: https://www.mediawiki.org/wiki/Manual:Hooks#Handling_hooks_in_MediaWiki_1.35_and_later
[EntitySchemaExtensionJsonTest]: https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/EntitySchema/+/157449dbf318d4587b3c4df605b0f02a888e7139/tests/phpunit/integration/MediaWiki/EntitySchemaExtensionJsonTest.php#32