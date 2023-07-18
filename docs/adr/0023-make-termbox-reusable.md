# 23) Make Termbox v2 reusable for EntitySchema {#adr_0023}

Date: 2023-07-18

## Status

accepted

## Context

Labels, descriptions and aliases of EntitySchemas are currently only editable via the `SetEntitySchemaLabelDescriptionAliases` special page. In order to make them editable directly from the EntitySchema wiki pages and to increase consistency across Wikibase UIs we want to reuse Termbox v2, which so far has only been active on mobile Item and Property pages.

Termbox v2 has not been developed with reusability for other entity types in mind originally, however, it does allow for data access services to be configured as part of its build process. Making these services configurable enables different data sources to be used for server-side rendering (SSR) and client-side rendering (CSR).

## Considered Actions

We considered three different approaches. The first two are similar in that they both make use of the existing service wiring mechanism to configure the parts of Termbox that may be different between Item/Property and EntitySchema pages.

### Approach 1a: Add an additional EntitySchema wiring file to Termbox.git

This is similar to how Termbox works already, but instead of exporting one wikibase.termbox.<b>main</b>.js, we export two: one wikibase.termbox.<b>item-and-property</b>.js and one wikibase.termbox.<b>entity-schema</b>.js, each containing a whole Vue application including their respective data access details

Straightforward implementation:

1. create a new entity-schema-client-entry.ts file within termbox
2. adjust the build config accordingly
3. make a new resource loader module within EntitySchema.git and use the new build artifact
4. done (? probably)

#### Interface to consuming software system

* entity-schema-client-entry.ts as the build step entry point for the EntitySchema Termbox
* exported assets to be loaded within the MediaWiki extensions:
	* 1 fully built termbox JS file for item/property pages (incl data access details)
	* 1 fully built termbox for EntitySchema (incl data access details)
	* 1 CSS file to be shared by both

#### Discussion

* Pros:
	* very little effort
* Cons
	* moves EntitySchema details into the termbox code base
	* requires the team maintaining the separate entry file to understand all the involved details
	* shipping Termbox as a closed system makes it not reusable by other Vue applications
	* less-than-ideal interface for consumers (= WD Team) as it requires understanding of all involved data structures and their corresponding data access services and the service container interface

### Approach 1b: Move data access services into the consuming applications

This is similar to 1a but instead of exporting fully built applications from Termbox, we export a function that builds the application and requires the consumers to define the data access services. The two main services that need an EntitySchema equivalent are [writingEntityRepository](https://github.com/wikimedia/wikibase-termbox/blob/81ca125c0828e1a83c0283ea05db84dcc3491e49/src/client-entry.ts#L90) (service that updates terms) and [entityRepository](https://github.com/wikimedia/wikibase-termbox/blob/81ca125c0828e1a83c0283ea05db84dcc3491e49/src/client-entry.ts#L65) (service that fetches terms).

#### Interface to consuming software system

This approach results in a single consumable “module” that can be used by both Wikibase for item/property pages, and EntitySchema, distributed a resource loader module defined in a central place or an npm package.

Module exports:

* function initTermbox( mw, services )
	* mw: the global window.mw object for messages, config, etc
	* services: services specific to the label provider and its context, i.e. “entityRepository”, “writingEntityRepository” and possibly “entityEditabilityResolver”
* a CSS file
* interfaces/types:
	* EntityRepository
	* WritingEntityRepository
	* EntityEditabilityResolver
	* Fingerprintable, TermList, AliasesList, … data structures needed for services are defined in [https://github.com/wmde/WikibaseDataModelTypes/](https://github.com/wmde/WikibaseDataModelTypes/)
	* types of errors that can be thrown by services: TechnicalProblem

#### Discussion

* Pros
	* clearer separation of ownership: Termbox remains unaware of EntitySchema-specific details; the wiring file(s) live in a code base fully owned by the consuming team (= WD Team)
	* more consumer-friendly compared to 1a
	* clean interface without exposing Termbox internals
	* gives the consumer more control over the Termbox initialization
* Cons
	* potential dependency/integration overhead across code bases/teams
	* shipping Termbox as a closed system makes it not reusable by other Vue applications
	* less-than-ideal interface for consumers (= WD Team) as it requires an EntitySchema-specific implementation of data access services

### Approach 2: Make Termbox a reusable Vue component

This involves properly librarifying termbox, making data fetching/updating the responsibility of each consuming code base. As such, the Termbox component would make no API calls and have no direct access to external application state.

#### Interface to consuming software system

The interface with the consuming software system is a Vue component.

* props
	* labels, descriptions, aliases
	* preferred languages
	* isEditable
	* licenseAgreementAcknowledged (determines whether or not the “license attribution” overlay is shown)
	* warnForAnonymousEdits (determines whether or not the anonymous edit warning overlay is shown)
	* config (static props that don’t change)
		* textFieldCharacterLimit
		* licenseAgreementInnerHtml
		* copyrightVersion
	* isSaving (to show the loading overlay while the consumer is making API requests)
* events
	* save (payload: labels, descriptions, aliases)
	* licenseAgreementAcknowledged
	* doNotWarnForAnonymousEditsAgain
* further data required by the component without a clear input mechanism
	* i18n (vue plugin, probably)
	* language data: language codes, language translations, language script directionality; (worst case we can pass the data in via prop)
	* how does the consumer inform termbox about errors that occurred while saving
* peer dependencies: vue, vuex

#### Implications to existing Item/Property Termbox solution

This approach results in a major refactoring of the existing Termbox code base, but not necessarily changing the way it is consumed by Wikibase.

Possible implementation path:

* treat termbox.git as a monorepo containing both the item/property termbox application, and the Termbox.vue library component
* leave build step and SSR as it is for item/property
* gradually extract things out of the item/property application and into the library component while using it
* add a second build config to the library directory of the monorepo to build/export the component for reuse by EntitySchema

#### Discussion

* Pros
	* industry standard way of sharing a Vue component across code bases: exporting the component and its styles
	* flexibility: shipping a component for the consuming application to interact with as opposed to a whole application as a closed system
	* in a hypothetical future where the whole item/property page is one big Vue application, this Termbox.vue component could just be imported into it
	* potential to abandon vuex, since a major refactoring of the application is needed anyway
* Cons
	* much more effort, both in Termbox-internal refactoring and (re)implementation on the consumer side
	* possibly premature: same future-proofing effort can be invested, once the consuming systems are Vue applications themselves that benefit from being able to interact with an embedded Termbox component once a Termbox Vue component is required

## Decision

We choose option 1b: We will make use of the existing service wiring mechanism and will move the wiring for the data access services into the consuming applications. Compared to 1a, 1b provides a much cleaner interface for consumers at a low additional cost. Option 2 on the other hand is significantly more effort, which we think might not pay off in the end.

## Consequences

The Wikibase Product Platform Team will start implementing the chosen approach and apply it to Item and Property pages. This can then serve as a showcase implementation for the EntitySchema pages.
