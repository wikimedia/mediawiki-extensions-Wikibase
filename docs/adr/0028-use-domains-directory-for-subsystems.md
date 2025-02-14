# 28) Use `repo/domains/` for Newly Defined Domain-Specific Subsystems {#adr_0028}

Date: 2025-01-21

## Status

accepted

## Context

As documented in [ADR #25](@ref adr_0025), the Linked Open Data teams agreed to modularize Wikibase code into domain-specific subsystems. We need to organize the Wikibase code repository to meaningfully reflect separate domains. The decision is now imminent with the upcoming task of REST endpoints for simple search and prefix search.

## Considered Actions
Since the search use cases will involve searching for Items as well as Properties (and possibly other entity types), a choice has to be made, by which domain to split out the new subsystem. Two options are apparent:
- **Split by entity type:** Define domains for Items, Properties etc. and implement search use cases inside each of the type-specific domains
- **Define a search domain:** Centralize all entity-type-specific search use cases within a single domain.

## Decision
We will define *search* as the first subsystem implemented under the new modularization approach and group different entity-type-specific use cases within this domain. This is the preferred option, because we expect search use cases to be quite similar to each other and find it reasonable to keep them together, rather than splitting them up into their entity-type domains.

## Consequences
All PHP code, PHPUnit tests, end-to-end tests, the OpenAPI and REST route definitions for the new search endpoints will reside in a newly created `repo/domains/search` directory. Test helper functions and the OpenAPI build step will be reused from the overarching `repo/rest-api/` directory.

With the creation of the `repo/domains/` directory, we propose moving any code that is being refactored to follow [ADR #25](@ref adr_0025) into this location. We will therefore move the existing REST API code into a new `repo/domains/crud` subdirectory. While 'CRUD' does not constitute a domain, the software architecture of the REST API already follows some of the suggestions from [ADR #25](@ref adr_0025), such as Hexagonal Architecture, infrastructure / core separation and entity specificity. It can be further split into separate domains later, for example Items, Properties and Statements. This reorganization will also enable consistent reuse of the `repo/rest-api/` components.