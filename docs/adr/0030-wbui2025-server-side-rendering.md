# 30) wbui2025 Server-Side Rendering {#adr_0030}

Date: 2025-07-25

## Status

accepted

## Context

In order to improve the mobile editing experience (aka MEX), we are working on a new Wikibase UI implementation, internally named wbui2025.
This UI is to be built using Vue.js (compare [ADR #26](@ref adr_0026)), but in order to provide a no-JS experience,
we also need a server-rendered version of the UI (specifically, the “read-only” state).
The question, then, is how we implement the server-rendered page.

While the new implementation is currently only targeting mobile,
we expect it to eventually become the basis of a new desktop (or unified) UI as well;
thus, we would prefer the SSR solution to be suitable for all page views in the long run.

### Prior art

#### “Legacy” Wikibase UI

The current Wikibase UI (mostly implemented in the `view/` directory) uses a hybrid approach.
The file `view/resources/templates.php` contains various HTML snippets (with `$1`, `$2` etc. placeholders),
which are used to build the UI.
The same templates are used both in PHP and in JavaScript;
they are rendered and combined by bespoke code,
which can be found by searching for calls to `$this->templateFactory->render()` (PHP)
or references to `$.ui.EditableTemplatedWidget` (JS).

#### Mobile Termbox SSR

The mobile termbox is already implemented using Vue.js.
For server-side rendering, it uses an [SSR service][Termbox SSR] running in Node.js;
Wikibase requests rendered HTML from the service over HTTP,
and the service requests entity data from Wikibase also over HTTP.
Updates are deployed by building a new container image and updating the deployment charts to use the new image.

#### WikibaseLexeme SSR

WikibaseLexeme is also implemented using Vue.js.
For server-side rendering here, we implemented an experimental library, [php-vuejs-templating][],
which uses the [DOM][] PHP extension to implement a subset of Vue syntax:
evaluate `v-if=` attributes, remove `v-on=` (meaningless server-side), clone nodes for `v-for=`, etc.
The library only supports a part of Vue’s features, and only a tiny amount of JavaScript expression syntax,
but within those constraints we found it sufficient for WikibaseLexeme at the time.
We never declared the library stable and have not encouraged its use elsewhere,
but it is still in use in WikibaseLexeme now.

#### MediaWiki Vue SSR proof-of-concept

In January 2025, the WMF Design System Team uploaded a [proof of concept][Vue-SSR-POC]
for Vue.js server-side rendering in MediaWiki core.
This would use a separate Node.js service
(similar to the mobile termbox, albeit using Rollup instead of Webpack),
but with the integration code living in the MediaWiki core Git repository.
We might be able to use something like this in future,
but at the moment nobody is actively working on this approach as far as we’re aware.

### Options considered

#### JavaScript server-side rendering

We could follow the same approach as Mobile Termbox SSR:
run a standard Vue.js server-side rendering solution in Node.js,
as a separate service reachable over the (internal) network.
This would be the “industry standard” approach, but in our environment it brings several downsides, such as:

- The network communication adds latency,
  although we have not measured how much it is –
  probably not a whole lot within Wikimedia’s datacenters.
  (The “separate service” approach is also used by MediaWiki to render some page contents,
  most notably by the Score extension to call Lilypond –
  however, in that case the runtime is probably dominated by Lilypond anyways.)
- Updates must be deployed manually instead of “riding the train”,
  though the MediaWiki Vue SSR work might mitigate this if it proceeds beyond the proof-of-concept stage.
  In principle, this can be either a positive or a negative characteristic,
  in that we could move faster than the train if we wanted
  (though only for SSR changes that require no updates to the client-side code,
  which is still rolling out with the train after all);
  however, in our experience with Termbox, we’ve mainly seen the negative side of this,
  where the SSR service deployment risks falling behind the latest code merged on the `master` branch.
- Local development becomes somewhat more cumbersome,
  though this partially depends on the way the development environment is set up.

Overall, we’re not very excited about this option,
though it remains a viable fallback option.

#### PHP server-side rendering, same sources, php-vuejs-templating

We could follow the same approach as WikibaseLexeme:
use the same Vue.js templates both client-side and server-side,
using the [php-vuejs-templating][] library for server-side rendering.
Our templates would be limited to the “whatever php-vuejs-templating supports” subset of Vue.js
(at least in the part that needs to be server-side rendered – the interactive parts have fewer restrictions);
if we want additional templating features, we have to spend time to implement them ourselves,
in addition to maintaining the library in general.

This option was considered, but decided against, for the Mobile Termbox work at the time
(without a full ADR, but there are some [WMDE-internal notes][] and [approach comparisons][]).
It’s worth mentioning that the situation for php-vuejs-templating has somewhat improved in the meantime:
thanks to the adoption of Peast for the JavaScript minifier in MediaWiki core ([T75714][]),
we can now use this library, already security-reviewed for Wikimedia production,
to parse the JavaScript expression in the templates and evaluate a subset of them.
This was not available during the Termbox work, so at the time,
expanding php-vuejs-templating’s JavaScript support would have needed more ugly string manipulation.
That said, it remains true that php-vuejs-templating is not an industry standard solution
and requires more ongoing maintenance from us.

#### PHP server-side rendering, same sources, v8js

We could use the [v8js][] PHP extension to run Vue.js server-side rendering directly in PHP,
rather than in a separate Node.js service.
However, our [investigation][T397291#10980545] found that the extension is difficult to install,
carries security risks,
and requires a lot of boilerplate code to set up compared to using Node.js.

#### PHP server-side rendering, separate sources

We could implement a server-side rendering in PHP separately –
the HTML markup would be the same, and styles would be reused,
but it would be built using custom PHP code,
independently of the Vue.js sources.
This approach is always viable, but requires more work –
effectively implementing the (non-interactive) UI twice –
and risks having the PHP and JS versions accidentally diverge from one another.
(We might be able to have tests in CI which render both versions and compare the results.)

#### Hydrating PHP Templates with Vue.js

We [investigated][T397291#10980651] creating the HTML via PHP and hydrating it with Vue.js,
but concluded that it would require a lot of boilerplate code,
make it impossible to use Codex Vue components,
and bring no benefit compared to php-vuejs-templating.

## Decision

We will use and extend the php-vuejs-templating library for the time being,
until we either come to the conclusion that it is not feasible due to the limitations of this approach,
or finish the new mobile UI.

We expect this approach to be “safe” in that,
because the templates we use on the server side are the same standard Vue.js templates as on the client side,
we can always switch to a different Vue SSR approach later.

## Consequences

Wikibase will depend on the [php-vuejs-templating][] library,
which in turn will gain new features developed by us
(mostly published in “beta” releases until we’re more confident about the approach).
Apart from installing the library via composer (or `mediawiki/vendor.git`),
no necessary changes to the deployment of Wikidata or other Wikibase instances are expected.

[Termbox SSR]: https://wikitech.wikimedia.org/wiki/WMDE/Wikidata/SSR_Service
[php-vuejs-templating]: https://www.php.net/manual/en/book.v8js.php
[DOM]: https://www.php.net/manual/en/book.dom.php
[Vue-SSR-POC]: https://gerrit.wikimedia.org/r/c/mediawiki/core/+/1114077
[WMDE-internal notes]: https://docs.google.com/document/d/1LidOJHohmjkVUzNI2chjbL-99ORLeWxTq7mHcs0fp-E/edit?tab=t.0#heading=h.72po82oi3ffs
[approach comparisons]: https://docs.google.com/spreadsheets/d/1q9rJKxnzriRAyt8dW6vg4h1Cz-G_soAQOHehijz4DD4/edit
[T75714]: https://phabricator.wikimedia.org/T75714
[v8js]: https://www.php.net/manual/en/book.v8js.php
[T397291#10980545]: https://phabricator.wikimedia.org/T397291#10980545
[T397291#10980651]: https://phabricator.wikimedia.org/T397291#10980651
