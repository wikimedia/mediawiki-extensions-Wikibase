# 32) wbui2025 Server-Rendered HTML {#adr_0032}

Date: 2025-07-31 (TODO: bump)

## Status

proposed (TODO: accepted)

## Context

Snaks need to be displayed in wbui2025 (the new mobile UI, see [ADR #30](@ref adr_0030)).
There are a variety of different datatypes, all of which already have formatters that handle their complexity and edge-cases.
For example, the formatters handle language selection and fallbacks.

The HTML outputted by these formatters is generally simple, and can be styled with CSS.
In the case that CSS alone is insufficient, we could introduce a new output format
(similar to existing "[verbose][FORMAT_HTML_VERBOSE]" or "[diff][FORMAT_HTML_DIFF]" HTML formats; see [SnakFormat][]).

In the server-side rendering, we can use the HTML from these formatters directly,
via the `v-html` directive (already supported by php-vuejs-templating).
In the client-side rendering, we can use the same HTML, by extracting it from the server-rendered HTML before we mount our app.
This was first prototyped in an [investigation task][T398415] and [PoC patch][PoC].

Another option we considered was to format the data in JSON and rebuild the logic of the formatters in Vue.
We decided against this because it would require a lot of duplicate effort without clear benefit,
and the JSON formatters and Vue templates would be tightly coupled together.

## Decision

We will use the existing formatters to provide html for use in the Vue templates, injected via the `v-html` directive.
The html will be styled with wbui2025-specific CSS to make it fit the design.
If there is any case where it is not feasible to style as needed, we may introduce a new output format.

In the JS code, we will extract the snak HTML from the server-rendered HTML before initializing the app,
and then reuse this HTML for the client-rendered app (again via `v-html`).

## Consequences

The HTML for data value will be mostly the same between mobile and desktop.
There is a risk that changes made to the data value HTML for desktop will break things on mobile, or vice versa;
however, this is mitigated by the fact that we maintain both frontends.

We may introduce a new output format if necessary.

### Kartographer issues

We have seen [some issues][T394906] with this approach due to the fact that Kartographer,
when it initializes a map, creates a DOM that cannot be serialized via `innerHTML`
(it contains a `<div>` inside a `<p>`, and nested `<a>` elements).
When we reassign this HTML in our app (via `v-html`), it is broken by the browser into a different, non-functional DOM.
As of writing this ADR, it is not yet clear how we will resolve this, and whether other data value formatters may be affected by it;
we may change Kartographer so that it produces valid HTML, or find another solution.

[FORMAT_HTML_VERBOSE]: https://gerrit.wikimedia.org/g/mediawiki/extensions/Wikibase/+/993f9695faa37689f347684207dc3bd662472fd7/lib/includes/Formatters/SnakFormatter.php#34
[FORMAT_HTML_DIFF]: https://gerrit.wikimedia.org/g/mediawiki/extensions/Wikibase/+/993f9695faa37689f347684207dc3bd662472fd7/lib/includes/Formatters/SnakFormatter.php#33
[SnakFormat]: https://gerrit.wikimedia.org/g/mediawiki/extensions/Wikibase/+/993f9695faa37689f347684207dc3bd662472fd7/lib/includes/Formatters/SnakFormat.php
[T398415]: https://phabricator.wikimedia.org/T398415
[PoC]: https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Wikibase/+/1169087
[T394906]: https://phabricator.wikimedia.org/T394906
