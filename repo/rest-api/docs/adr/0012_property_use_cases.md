# 12) Introducing Properties in the REST API {#rest_adr_0012}

Date: 2023-06-02

## Status

accepted

## Context

The Wikibase REST API supports various endpoints related to getting and updating Item data. It is now going to be extended to cover not only Items but also Properties. This raises a question regarding the approach to be taken: should we reuse the existing use cases and domain services by finding suitable abstractions that work for both Items and Properties, or should we create separate ones for Properties, accepting some code duplication between similar classes for Items and Properties?

As an example, consider the `ItemPartsRetriever` domain service:
```
interface ItemPartsRetriever {

	public function getItemParts( ItemId $itemId, array $fields ): ?ItemParts;

}
```
Re-using this service would require converting it into an `EntityPartsRetriever`, which takes an `EntityId` as input and returns generalized `Entity` data, covering both `Item` and `Property`. In contrast, a separate domain service for `Property` would leave the `ItemPartsRetriever` untouched and provide a new `getPropertyParts` method, that expects a `PropertyId` as an input parameter.

More such use cases and domain services exist for accessing and updating Item labels, descriptions and aliases.

## Decision

We have decided to create new use cases and domain services for Properties, similar to the Item specific ones. This way we avoid ambiguity and ensure clear inputs and outputs for both Items and Properties.

## Consequences

By creating Property equivalents of the Item specific services, we ensure that the code and functionality related to Items and Properties remain separate and unambiguous. This approach allows us to handle the unique characteristics of each entity type effectively. Although it results in some code duplication between similar use cases for Items and Properties, it helps maintain clarity and reduces the potential for errors or confusion.