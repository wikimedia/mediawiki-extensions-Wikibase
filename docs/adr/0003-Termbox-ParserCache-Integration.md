# 3. Termbox ParserCache Integration

Date: 2019-01-29

## Status

proposed

## Context

The new Termbox introduces an external service to Wikibase that generates the server-side markup for the section showing labels, descriptions, and aliases of Item and Property pages. This section contains user-specific content such as their preferred languages (likely determined by the Babel extension), and whether the "more languages" section is expanded or collapsed on page load.

Typically, non-user-specific markup of entity pages is added to MediaWiki's `ParserOutput` and cached within `ParserCache`. The `PlaceholderEmittingEntityTermsView` PHP version of the Termbox works around the `ParserCache` by injecting markers in place of the user-specific markup before it's added to `ParserOutput` and replacing the markers with the user-specific markup before the page is rendered in `OutputPageBeforeHTMLHookHandler::doOutputPageBeforeHTML`. This option is not viable for the new Termbox as it would split the logic of injecting and replacing markers across two services.

The `ParserCache` drastically reduces render time of entity pages. It's hit 3000 - 15000 times per minute in total, but only 16 - 66 times per minute from logged in users. As long as the only additional dimension is the user's list of preferred languages, the number of problematic requests may be further reduced by only considering those who have Babel preferences set to something other than their preferred interface language.

## Decision

We always request the non-user-specific version of the Termbox and cache the result as part of the `ParserOutput`. For logged in users the request is made later in the request life cycle in `OutputPageBeforeHTMLHookHandler::doOutputPageBeforeHTML` and not part of the `ParserCache`. In case the service is down, the cached output can be used as a fallback.

## Consequences

`ParserOutput` is kept free of user-specific markup, and further cache splitting is avoided. Logged in users have potentially slower render times due to the uncached request to the SSR service.

In order for this option to be viable the Termbox SSR service has to be sufficiently performant.
