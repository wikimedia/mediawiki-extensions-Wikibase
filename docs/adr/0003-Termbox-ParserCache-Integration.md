# 3) Termbox ParserCache Integration  {#adr_0003}

Date: 2019-01-29

## Status

accepted

## Context

The [new Termbox (v2)](https://gerrit.wikimedia.org/g/wikibase/termbox) introduces an external "SSR" service to Wikibase that generates the server-side markup for the section showing labels, descriptions, and aliases of Item and Property pages. This section contains user-specific content such as their preferred languages (likely determined by the Babel extension), and whether the "more languages" section is expanded or collapsed on page load. User-specific configuration can result from a logged-in state or information persisted in an anonymous user's session/cookies.

At the time of writing the following parameters influence the result of the Termbox (v2) service and would have consequently to be taken into account when caching results:
* entity id (✓ accounted for by `ParserCache`)
* entity revision (✓ accounted for by `ParserCache`)
* interface language (✓ accounted for by `ParserCache`)
* user's preferred languages
* toggle state of the "in more languages" section

Typically, only non-user-specific markup of entity pages is added to MediaWiki's `ParserOutput` and cached within `ParserCache`.
The [PlaceholderEmittingEntityTermsView] PHP version of the Termbox (v1) works around the `ParserCache` by injecting markers in place of the user-specific markup before it's added to `ParserOutput` and replacing the markers with the user-specific markup before the page is rendered in `OutputPageBeforeHTMLHookHandler::doOutputPageBeforeHTML`. This option is not viable for the new Termbox (v2) as it would split the logic of injecting and replacing markers across two services and would require the new service to be able to produce results for individual components which contradicts the architectural reality that it itself decides what to display, not only how (cue: "application vs. renderer").

The `ParserCache` drastically reduces the time it takes to render entity pages, it should consequently be leveraged whenever possible.

For wikidata item and property pages it is hit 3000 - 15000 times per minute in total (measured in January 2019).

Of these 16 - 66 times per minute are requests by logged in users (measured in January 2019), which influences the "preferred languages" dimension warranting a customized result. This number may be further reduced by only considering those who have their preferred languages set to something other than their preferred interface language.

Numbers for users with "in more languages" toggled to non-default state are not available at the time of writing. It is assumed that only a small portion of users request a customized toggle state.

## Decision

The non-user-specific version of the Termbox (v2) is cached as part of the `ParserOutput`.

For users with a user-specific configuration identical to the default values the cached result will be served.

For users with a user-specific configuration different from the default values, a request is performed against the Termbox (v2) service later in the request life cycle in `OutputPageBeforeHTMLHookHandler::doOutputPageBeforeHTML` and its result is served.

In case the SSR service does not produce a usable result (e.g. not reachable), [an empty element is served](https://gerrit.wikimedia.org/g/mediawiki/extensions/Wikibase/+/1a2a9397df3a6df8df5db346b7e8605c97ab9e2d/view/src/Termbox/TermboxView.php#22) that will be replaced by the client-side rendered version of the Termbox. More complex fallback scenarios are conceivable but not part of this ADR and up to product management to request depending on the value of focusing on this.

## Consequences

`ParserOutput` is kept free of user-specific markup, and further cache splitting is avoided. Users with custom configuration have potentially slower render times due to the uncached request to the SSR service.

In order for this option to be viable the Termbox SSR service has to be sufficiently performant.

Logging information will be closely observed in the future to ensure that the default configuration (resulting in the non-user-specific version of the page) in fact constitutes the largest share of page requests to maximize cache hit rate and minimize Termbox (v2) service requests.

[PlaceholderEmittingEntityTermsView]: @ref Wikibase::Repo::ParserOutput::PlaceholderEmittingEntityTermsView
