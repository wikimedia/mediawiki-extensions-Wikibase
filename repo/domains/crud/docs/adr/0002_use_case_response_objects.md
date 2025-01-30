# 2) Don't use serialized entities in response objects {#rest_adr_0002}

Date: 2022-05-09

## Status

superseded - The headline "Don't use serialized entities in response objects" still applies, but in [ADR 9] we changed to what is described as option 2 here.

## Context

Use case response objects are used to transfer data across the architectural boundary from the business logic to the presentation layer. Ideally they are simple DTOs with no dependencies. It is not recommended to use domain objects in use case requests or responses since the two may change for different reasons.

We initially decided to use serialized domain objects in the response objects, but ran into issues:
* Serialization fits better in the presentation layer than the use cases. We ended up addressing presentation problems in the serialization output, which required changes to the use cases. This is a violation of the onion architecture.
* We don't get any type hints beyond `array` which makes it awkward and error-prone for consumers.

We considered the follow options:
1. Keep everything as it is and accept the issues mentioned above.
2. Create dedicated DTOs or read models for the data we want to transfer. This may be the cleanest way, but it's impractical in our case since we're dealing with big nested objects. We don't want to rewrite the existing DataModel and DataModelSerialization code.
3. Use domain objects in the use case responses. This goes against the recommendation we found in literature and is not easily possible for our `GetItem` use case which may return a whole `Item` or only parts of it.
4. Some combination of the two above - use domain objects where possible, and create dedicated read models if necessary. We can use the existing serializers in the former case, and create new ones in the latter case.

## Decision

Option 4 seems like the most practical approach. While we acknowledge that domain objects and use case responses serve different purposes, we believe that due to the nature of our code base this will rarely be problematic.

## Consequences

We will refactor the existing use cases to return (wrapped) entities instead of serialized entities. Serialization will happen in the presentation layer.

[ADR 9]: @ref rest_adr_0009
