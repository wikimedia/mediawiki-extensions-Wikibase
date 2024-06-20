# 24) Data type specific value deserialization {#adr_0024}

Date: 2024-06-13

## Status

accepted

## Context

The Wikidata team has been working on linking to Entity Schemas in statements. It was decided to create a new `entity-schema` data type and to use the existing `wikibase-entityid` [value type], but without registering Entity Schema as an entity (see rejected [Entity Schema ADR 5](https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/EntitySchema/+/refs/heads/master/docs/adr/0005-make-entity-schema-a-wikibase-entity-type.md)).

By default, Wikibase deserializes values of type `wikibase-entityid` into `EntityIdValue` objects containing an `EntityId`. These `EntityId` objects are expected to be compatible with any entity services defined via [entity registration], which doesn't work for Entity Schemas since they're not using the entity registration mechanism. We needed to create a new solution to present Entity Schema values as `wikibase-entityid` values to the users without treating them the same as other `EntityIdValue`s.

## Considered Actions

### Distinguish between PseudoEntityId and "real" EntityId

In this approach `EntityIdValue` would be changed to hold an `IndeterminateEntityId` which is either a `PseudoEntityId` or a "real" `EntityId`. This allows for special treatment of `PseudoEntityId` objects in the code, while regular `EntityId`s could be processed as usual.

Pros:
* could be applied to similar features handling "real" and "pseudo" entities in the future

Cons:
* code dealing with `IndeterminateEntityId` becomes littered with instanceof checks
* some services such as `EntityIdParser` need to become aware of pseudo-entities which increases ambiguity and indirection in code
* seemed like a too far-reaching abstraction for the problem at hand

### Enable data type specific value deserialization

With this approach Wikibase will take into account the corresponding Property's data type and not only the value type when deserializing statement/qualifier/reference values. A data type specific value deserializer can be registered with the data type definition (here: `entity-schema`). This will allow serialized Entity Schema values to contain `"type": "wikibase-entityid"` without being deserialized as an `EntityIdValue`.

Pros:
* requires only a relatively small, localized change to the value deserialization code and data type definitions

Cons:
* values with custom deserializers can only be deserialized if the corresponding property or its data type is known
* deserializing values requires data type lookups which may impact performance

### Use a different value type

Not using `wikibase-entityid` as the value type was another tempting idea, given the debate around Entity Schemas being entities or not. This was briefly discussed in comments on the corresponding phabricator ticket ([T339920]), but ultimately got rejected by product management.

## Decision

We chose the "data type specific value deserialization" approach.

## Consequences

A `deserializer-builder` field was added to Wikibase's [data type definitions] mechanism and used by the `entity-schema` data type. We did some testing to alleviate the performance concerns ([T359420]).

[value type]: https://www.wikidata.org/wiki/Wikidata:Glossary#Value_type
[entity registration]: @ref docs_topics-entitytypes
[data type definitions]: @ref docs_topics-datatypes
[T359420]: https://phabricator.wikimedia.org/T359420
[T339920]: https://phabricator.wikimedia.org/T339920
