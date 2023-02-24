# 9) Introduce read models {#rest_adr_0009}

Date: 2023-02-24

## Status

accepted

## Context

In [ADR 2] we made the decision to put [Wikibase DataModel] entities into our use case response objects to transport data across the application layer boundary. These objects are used throughout Wikibase in all sorts of contexts for both reading and writing, which requires a few trade-offs. Having entities in responses was a step up from the untyped serialization arrays back then, but returning mutable objects with nullable IDs still isn't ideal. ADR 2 acknowledges introducing read models as a cleaner option, but dismisses it due to the required effort to rewrite the serializers. The serialization format changed since then, which required us to rewrite them anyway.

Another related issue came up in [ADR 4]. A user requesting statement data needs to know the statement property's data type in order to interpret the statement value, which means that it should be part of the use case response. For edit requests on the other hand, the data type is redundant since it can be derived from the statement property ID, which is always part of the edit payload. Encoding this difference between request data and response data at the use case level is not easily possible with a shared read/write model.

We are now reconsidering separating read models from write models to avoid such compromises in the future, and to gain the following benefits:
* ability for read and write models to hold different data
* read models that are guaranteed to be complete (e.g. no null IDs) and internally consistent
* mutable write models, immutable read models
* clearer responsibilities

## Decision

We decided to introduce REST API specific read models. Most of the counterarguments we had in the past are no longer valid, and while this change does come with considerable effort, we expect the investment to be worth it. We can continue using DataModel entities as our write models.

## Consequences

We will incrementally introduce read models to our code base. This mainly involves creating read model equivalents for DataModel classes we used directly in use case responses. `Updater` domain services will continue to consume DataModel objects, but will now return read models. Since the implementations of our domain services use non-REST API specific Wikibase services internally, they will need to convert the retrieved DataModel object to read models. In our `Serialization` namespace Deserializers will continue to return DataModel objects, but Serializers will be changed to consume read model objects instead.

[ADR 2] can be considered superseded now. [ADR 4] should be marked as superseded once the `Statement` read model is complete and contains the property data type.

[ADR 2]: @ref rest_adr_0002
[Wikibase DataModel]: https://github.com/wmde/WikibaseDataModel
[ADR 4]: @ref rest_adr_0004
