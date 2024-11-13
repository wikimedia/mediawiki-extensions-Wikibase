# 26) Wikibase Frontend Technical Strategy {#adr_0026}

Date: 2024-11-13

## Status

accepted

## Context

The original Wikibase UI created at the start of Wikidata is not feasible to be maintained long term. Combination of using the outdated technology, custom home-brew solutions and frameworks, as well as overly complex architecture and lack of expert knowledge in the organisation leads to extremely high cost (slow) of making changes and adjustments to the UI and a very high onboarding cost for engineering staff. This in turn leads to failing to innovate in the frontend product(s), a very long time it takes WMDE to fix issues in the frontend, which is likely negatively affecting the satisfaction of editors and other users of the Wikibase UI.
Based on those observations Engineering part of the organisation recognizes an urgent and essential need to modernize frontend technologies, with that this change will also enable modernization of the product(s).

## Decisions

This Decision Record document attempts to cover a number of decisions considered and agreed on in the frontend development area between 2018 and 2024. Those covered below are rather higher level decisions, followed by some more specific decisions and descriptions of initiatives that follow those. Further frontend development work will lead to adjustments to the strategy which are meant to be recorded on more detailed level.

### Modern frontend development and tools

* Modern approach to frontend development and widely-adopted tools, including a modern widely-used frontend framework, are to be adopted instead to benefit from the established ecosystem of solutions and tools, and the familiarity among the prospective engineering staff

Follow-up decisions:
* Vue.js has been chosen as a modern frontend framework. It has been meanwhile [selected by the WMF as a “Wikimedia frontend framework”](https://phabricator.wikimedia.org/T241180)
* WMDE would like the standardized frontend build tooling to be provided by the Mediawiki framework as long as Wikibase is intended to be a Mediawiki-based application. [No standardized build tooling has been agreed on and provided](https://phabricator.wikimedia.org/T279108) by Mediawiki maintainers as of writing this ADR.
* WMDE prefers creating frontend logic using Type Script rather than “plain” JavaScript to benefit from type safety. Type Script code is to be transpiled into client-side JavaScript and server-side nodejs for server-side rendering needs
* SASS/SCSS are preferred over LESS as CSS extension due to wider industry adoptions. That said, Wikimedia design system has [decided to use PostCSS](https://phabricator.wikimedia.org/T286951) and not use CSS preprocessor.

### Single Page Application
* Wikibase “item page” UI is envisioned to be a [Single-Page Application](https://en.wikipedia.org/wiki/Single-page_application) that could be “embedded” in the Mediawiki skin, which serves as a “chrome” providing generic Mediawiki UI functionality

### Responsive UI
* WMDE Engineering strongly recommends that general-purpose UI products like “item page” UI are provided as [responsive UIs](https://en.wikipedia.org/wiki/Responsive_web_design), with a single UI implementation covering all intended end devices/viewports. Duplication of frontend logic is to be avoided due to maintenance costs and error-proneness related to running multiple versions of the same UI. Having different versions of the UI (E.g. mobile vs desktop) should only be reserved for situations where those UIs are addressing significantly different use cases, effectively meaning those UIs are different products, rather than different versions of a single UI.

Follow-up decision:
* [Wikibase Termbox v2](https://gerrit.wikimedia.org/r/plugins/gitiles/wikibase/termbox/) have been developed as a responsive single-page-like application ([microfrontend](https://en.wikipedia.org/wiki/Microfrontend)) which is embedded in the legacy "item page" UI.

### Design System
* To ensure consistency and reduce duplicated effort the frontend logic is to be encapsulated in a [design system](https://en.wikipedia.org/wiki/Design_system).

Follow-up decision:
* WMDE has proven the design system usage concept with Wikibase-specific [Wikit](https://github.com/wmde/wikit), which has then been superseded with the [Wikimedia design system Codex](https://doc.wikimedia.org/codex/latest/).

## Further Remarks

* [Isomorphic rendering](https://en.wikipedia.org/wiki/Isomorphic_JavaScript) seems to be a convenient technique, as it could minimise the costs and challenges related to maintaining multiple implementations of rendering logic. However, Mediawiki has historically only rendered frontend server-side using server-side-only language PHP.
* There's no consensus on what approach to use for rending UI server-side
 * Wikibase Termbox v2 demonstrates the usage of isomorphic rendering paradigm with the server-side rendering of the UI done in nodejs, sharing the rendering logic with client-side JavaScript. Mediawiki itself does not provide required mechanism leading suboptimal [architecture](https://wikitech.wikimedia.org/wiki/WMDE/Wikidata/SSR_Service#Architecture). When used in a browser with disabled JavaScript, Termbox v2 passes editors to do edits in dedicated UIs which are rendered using PHP.
 * In other initiatives, the UI has been rendered on the server in PHP using various methods to render templates as lack of a standardised non-PHP rendering mechanism in Mediawiki makes the javascript-based rendering solutions overly complex and harder to maintain.
* As of writing this ADR no decision has been made on [standardising server-side rendering methods in Mediawiki](https://phabricator.wikimedia.org/T322163).
