# 36) Federated Items - Remote Items as Values {#adr_0036}

Date: 2026-11-07


## Status

accepted

## Context

This ADR is one of several referring to Federated Items. Federated Items is an opt-in feature that allows users to select Wikidata items and display them as statement values in their Wikibase instance. The Federated Items MVP supports the use of remote Wikidata items in statements on local items.

### Current implementation: entity sources and conditions in service wiring

The Federated items feature is disabled by default, and configured using a feature flag (currently `federatedValuesEnabled`). When the feature is enabled, Wikidata is automatically injected as a remote entity source by `DefaultWikidataEntitySourceAdder`. Once configured, the `EntitySource` mechanism established for Wikimedia Commons and used by Federated Properties (see [ADR #10](@ref adr_0010), [ADR #19](@ref adr_0019), and [ADR #21](@ref adr_0021) is set up to allow access to Wikidata.

The validation, parsing and formatting of the Wikidata items is done in the service definitions. Specifically, by conditionally changing the definitions of the `WikibaseRepo.EntityIdParser` and `WikibaseRepo.ValueFormatterFactory` services in `repo/WikibaseRepo.ServiceWiring.php`. This implementation differs from the standard approach to achieving data type specific behavior in Wikibase, namely by using data type registration callbacks (`parser-factory-callback` and `formatter-factory-callback` in `repo/WikibaseRepo.datatypes.php`).

## Considered Actions

### Use data type registration and entity sources

This approach keeps the automatic injection of Wikidata as a remote source via Wikibase's entity source mechanism, while handling parsing and formatting by changing the wikibase-item data type definitions. This reduces the amount of changes needed to service definitions compared to the current implementation.

The data type changes could either be implemented directly in `repo/WikibaseRepo.datatypes.php` or by using the `WikibaseRepoEntityTypesHook` to dynamically change the definitions as needed.

### Use data type registration and create new extension interface

In this approach, Federated Items will be implemented using data type registration, while refraining from registering Wikidata as an entity source. For RDF production, changes to or around `ItemStubRdfBuilder` will be needed to either allow or at least bypass item stub creation ([T439695#12422388]). For further, yet unidentified, use cases currently served by entity sources, specific mechanisms will be created on a case-by-case basis.

Phabricator: [T406224]

## Decision

Where possible, data type registration will be used to attain remote entity specific behaviour. For RDF production and possible further, yet unidentified, use cases where this is not possible, specific mechanisms will be created on a case-by-case basis.

[T406224]: https://phabricator.wikimedia.org/T406224
[T439695#12422388]: https://phabricator.wikimedia.org/T439695#12422388