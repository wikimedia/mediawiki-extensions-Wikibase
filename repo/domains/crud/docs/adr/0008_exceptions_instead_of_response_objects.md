# 8) Throw exceptions instead of returning response objects in use cases {#rest_adr_0008}

Date: 2023-01-20

## Status

Accepted

## Context

Currently, our UseCases can return three different types of response objects:

1. A "success" response (e.g. `GetItemLabelsSuccessResponse`)
2. An `ItemRedirectResponse`
3. An `ErrorResponse` (e.g. `GetItemLabelsErrorResponse`)

While discussing Chapter 9: Validation of `Advanced Web Application Architecture`, we decided it would be useful to try out **only returning a success response object from a UseCase and throwing an exception in the other situations**.

We perceive that the benefits of throwing exceptions would be:

1. The UseCase `execute()` method would only need a single return type instead of union types.
2. In the RouteHandlers, catching different exceptions is nicer than doing several `instanceof` checks in `if`/`elseif`/`else` (or `switch`) statements.
3. Checks like <em>"is there a validation error"</em>, <em>"does the item exist"</em>, <em>"is the item a redirect"</em>, or <em>"does the user have permission"</em> can be condensed into a few method calls, reducing the number of lines in the UseCase `execute()` method.

## Considered Actions

### 1) Leave the current response objects as is

Keep current `ErrorResponse` and `ItemRedirectResponse`, without throwing exceptions.

### 2) Switch to the new approach of using exceptions

By trying to use exceptions instead of response objects in either an already implemented UseCase with a single `RouteHandler` like `GetItemLabels`, or when creating a new UseCase e.g. `GetItemDescriptions` or `GetItemAliases`.

## Decision

We decided to go with action 2: switch to throwing use case exceptions instead of returning different kinds of response objects.

## Consequences

- Modify old **use cases** and **validators** to use exceptions instead of response objects.
- Since there is now only one response for each use case (the "SuccessResponse"), we will rename it to just "Response". (e.g. `GetItemLabelsSuccessResponse` -> `GetItemLabelsResponse`).
