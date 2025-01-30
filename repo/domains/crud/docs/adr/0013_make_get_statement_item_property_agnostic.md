# 13) Make single statement use cases subject agnostic {#rest_adr_0013}

Date: 2023-06-28

## Status

accepted

## Context

- The Wikibase REST API allows handling of a statement on an item via the `/entities/items/{item_id}/statements/{statement_id}` and `/statements/{statement_id}` endpoints. Currently, these endpoints are limited to handling items only and don't support other entity types such as properties.
- As the system evolves and the need for handling all entity types arises, it is crucial to extend the functionality of those endpoints, that operate on a single identifiable statement, to handle all entity types.
- Because the short `/statement/{statement_id}` endpoints don't explicitly specify the entity type in the URL, we should make them work with all entity types and not just items and properties.
- While item and property are currently the only two entities in the `Wikibase` extension, other extensions can create additional Wikibase entity types (e.g. the `WikibaseMediaInfo` extension adds the `MediaInfo` entity).

## Decision

- Since a statement is uniquely identifiable, we will treat it as a first class domain object and create dedicated domain service interfaces (retriever, metadata retriever and updater). This will allow us to make the use cases agnostic to the different statement subject types, and we can keep that logic hidden in the corresponding service implementations.
- Each single statement use case will support multiple endpoints (and therefore be called by multiple route handlers). For example, for "retrieving a single statement", the route handlers which process the `/entities/items/{item_id}/statements/{statement_id}`, `/entities/properties/{property_id}/statements/{statement_id}` and `/statements/{statement_id}` endpoints, will each call the same subject agnostic `GetStatement` use case.
- The `POST /entities/items/{item_id}/statements` endpoint for adding a new statement to an item **won't** use a subject agnostic use case. Instead, when adding the `POST /entities/properties/{property_id}/statements` endpoint, will create a new use cases for adding a new statement to a property. This is because the new statement doesn't yet have a unique identifier, and it would be nonsensical to have a short `POST /statements` endpoint. This is also true of all endpoints that don't support a single uniquely identifiable statement, such as the `GET /entities/items/{item_id}/statements` endpoint.

## Consequences

- By refactoring the single statement use cases to be subject agnostic, we are decreasing the need to duplicate similar code across different statement subject types.
- We will need to rename the subject agnostic use cases, and their related classes, to not be **Item** specific. For example, rename **GetItemStatement** to **GetStatement**, and **GetItemStatementValidator** to **GetStatementValidator**.
- We will need to create some subject agnostic error codes for the use case validators to use when throwing a `UseCaseError`. The route handlers will need to handle these new error codes in order to return the correct endpoint specific error response.
- The short endpoints will have the capability to support all subject types, rather than being limited to only items and properties.
- By creating subject agnostic use cases we will also be aligning with the broader vision of the Wikibase REST API to provide comprehensive and consistent functionality across different entity types.
- This decision will add more complexity to the subject agnostic use cases. Clean coding and proper testing will be crucial to ensure the feature is robust, well-understood, and easily maintainable.
- Creating the domain services for single statements may allow us in the future to create implementations that don't require loading the full subject in order to access or update a single statement. Since all domain service interfaces can remain the same, we wouldn't have to make any changes to the use cases.
- Extensions that add additional entity types won't need to reimplement the short `/statement/{statement_id}` endpoints as the existing ones will already support that. They will have to implement the long entity specific endpoints though.
