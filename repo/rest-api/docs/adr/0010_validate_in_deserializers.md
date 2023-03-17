# 10) Validate fully in the REST API deserializers {#rest_adr_0010}

Date: 2023-03-08

## Status

accepted

## Context

As documented in [ADR 0001], when we started development we took the decision to "consider the existing [WikibaseDataModel] part of our entities, and also allow the use of (some?) parts of the data model libraries". The deserializers in the [WikibaseDataModelSerialization] library don't fully validate during deserialization. In other words, they can return objects that are syntactically valid but logically invalid. For example, the `SnakDeserializer` can return a `Snak` with a `PropertyId` that doesn't exist. The existing Wikibase codebase relies on validators, for example the `SnakValidator`, to check a deserialized object are logically valid.

During book club, while reading "Advanced Web Application Architecture" by Matthias Noback, we learnt that "an object should ensure that its data is never incomplete, invalid, or inconsistent" and that an _entity_ is a stateful object with an identity. If an invalid object is never created it can't be used in the wrong place, or saved to the database, and cause an issue later down the line.

While working on the [T321459: Adjust statement data structure in Wikibase REST API responses and requests][T321459] story, we decided to create dedicated REST API deserializers ([T322650]) as the existing ones in [WikibaseDataModelSerialization] would no longer work. This gave us an opportunity to create deserializers that validate fully and only return complete and valid objects.

## Decision

Create REST API specific deserializers that validate fully and return complete and valid [WikibaseDataModel] objects.

## Consequences

- More robust code as the [WikibaseDataModel] objects created by our deserializers are never incomplete or invalid
- For now, it is still beneficial for our deserializers to return [WikibaseDataModel]s as it makes it easier to use existing Wikibase Services. It also limits the scope of changes to a manageable size. In the future, we may want to return our own write model entities from our deserializers and change our updater services accordingly.


[ADR 001]: @ref rest_adr_0001
[T321459]: https://phabricator.wikimedia.org/T321459
[T322650]: https://phabricator.wikimedia.org/T322650
[WikibaseDataModelSerialization]: https://github.com/wmde/WikibaseDataModelSerialization
[WikibaseDataModel]: https://github.com/wmde/WikibaseDataModel
[`UnDeserializableValue`]: https://github.com/DataValues/DataValues/blob/master/src/UnDeserializableValue.php
