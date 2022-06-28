# 4) Use PropertyDataTypeLookup in SnakSerializer {#rest_adr_0004}

Date: 2022-06-28

## Status

accepted

## Context

The responses of our REST API include the Property data type for objects containing "Snaks" such as statements, references, and qualifiers. The data type of these Snaks is not stored with the Snaks themselves, but has to be looked up using the respective Property ID via a `PropertyDataTypeLookup`.

We considered two options to add the data type to the responses.

1) We fetch the data type when *serializing* any Snak object. This approach is straightforward, but means that we're calling a data access service within a serializer, which is usually only called in the presentation layer. Ideally the use case response should already contain all the necessary data to make the response.
2) We fetch the data type within the data access services. This is arguably the neater solution since no additional data would need to be fetched in the presentation layer. Unfortunately, the Wikibase data model code makes it hard to add this information to existing data model objects without major changes.

## Decision

We choose option 1 and fetch the data type in the serializer. It's a relatively small and contained change, which is not hard to undo at a later point. We consider it an acceptable trade-off.
