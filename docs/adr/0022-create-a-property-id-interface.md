# 22) Create a Property ID interface {#adr_0022}

Date: 2021-07-20

## Status

accepted

## Context

Federated Properties v2 aims to make it possible to use both federated and local Properties on a single Wikibase instance. We introduced a `FederatedPropertyId` class to refer to Federated Property IDs in the code. `FederatedPropertyId` objects are different from `PropertyId` objects in a few aspects since the former are intended to be used for data access of Properties via an API while the latter are used to get Properties directly from a database.

There are a few service interfaces such as `PropertyDataTypeLookup` which are used throughout Wikibase and need to work for Federated Properties and local Properties alike. To keep these type hints meaningful the two Property ID classes need to share a common parent type.

## Considered Actions

We identified the following options:

1. Make `FederatedPropertyId` extend `PropertyId` after all.

While this would be the easiest solution to make all the code work, we think that this is conceptually wrong. `PropertyId` contains methods such as `getNumericId` and `newFromNumber` which do not make sense for `FederatedPropertyId`.

We considered reducing the `PropertyId` class to be more generic so that `FederatedPropertyId` would not inherit any unfitting methods. This might work since these methods could relatively easily be extracted into separate services, but we think it should be allowed for concrete `PropertyId` implementations to contain logic specific to their use case. Also, if `FederatedPropertyId` extends the local ID class we have no way of excluding federated IDs when type hinting for local IDs. If we keep a generic `PropertyId` class and separate implementations per use case, the generic ID class would effectively act as an interface.

2. Loosen the type hints of all Property-specific services to an existing common parent type, e.g. EntityId.

There is some precedence for services like this in Wikibase, e.g. terms related services accept `EntityId` objects but then only allow the subset of entity types which actually have terms. This is not a great pattern to follow. We know that only Properties have a data type, so allowing anything other than `PropertyId` in a data type lookup does not make sense.

3. Create a Property ID interface.

We will create an interface which will be implemented by local and federated Property ID implementations. The names are to be decided.

This seems to be the cleanest option to us, but also the one requiring the most changes. We can keep meaningful type hints in Property-related services and any implementations can contain methods for their specific use cases.

## Decision

Create a Property ID interface.

## Consequences

Introducing the new interface requires major changes to WikibaseDataModel, WikibaseDataModelServices, and all code bases using these packages. In the next step, we will decide on the right names for the interface and its two implementations, and make a plan for rolling out the changes.
