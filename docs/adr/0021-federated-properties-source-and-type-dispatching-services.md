# 21) Make all Federated Properties-relevant services source and type dispatching {#adr_0021}

Date: 2021-07-07

## Status

accepted

## Context

Federated Properties v2 aims to make it possible for users to enable Federated Properties even if their Wikibase already contains data, so that they can choose to use both remote & local Properties to make statements.

Dispatching by entity type is a widely used mechanism in Wikibase that allows the dispatching service to handle entity ids of different types by delegating to the service implementation defined in the [entity type definitions], thus enabling entity type specific behavior within a single service. With Federated Properties v2 the entity type ("property") no longer uniquely identifies the desired service implementation, since local Properties' services need to be handled by database-backed implementations, whereas Federated Properties use API-backed services. In order to work with local and remote Properties, dispatching services need to be aware of the entity's source as well as the type.

## Considered Actions

We considered two options:

1. Keep dispatching by entity type, and build source dispatching only into the Property services.
2. Make all relevant services source and type dispatching.

Both options involve deriving the source information from a given entity id. The former would likely result in a slightly less invasive change, as it would only require adding a second dispatching layer to the Property services, however, splitting the dispatching into multiple steps that are not co-located can likely be confusing.

Making all relevant services source and type dispatching means that affected type dispatching services would gain an extra "dimension" to dispatch by. Instead of mapping entity types to service objects, they are mapped by the combination of entity source names and entity types. This means dispatching happens in a single class, and is also future-proofing us for the anticipated Federated Items, which can use the same mechanism.

## Decision

Make all relevant services source and type dispatching.

## Consequences

Type dispatching services that should behave differently for local and Federated Properties, mainly the ones involving persistence, become source and type dispatching. We created an EntitySourceLookup to look up the EntitySource object corresponding to a given entity id, as well as a EntitySourceAndTypeDefinitions class to build the source + entity type to service callback mapping, similar to how EntityTypeDefinitions works, but with the added source dimension.

We added a "type" field to EntitySource in order to tell whether a source is local (db-backed) or remote (API-backed). EntitySourceAndTypeDefinitions uses the entity source type to get the service creation callback from either WikibaseRepo.entitytypes.php for local entities or from WikibaseRepo.FederatedProperties.entitytypes.php (likely to be renamed in the future) for remote entities.

[entity type definitions]: @ref docs_topics-entitytypes
