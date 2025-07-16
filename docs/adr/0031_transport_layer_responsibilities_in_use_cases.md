# 31) CRUD and Search: Allow transport layer details in application logic for now {#adr_0031}

Date: 2025-07-15

## Status

accepted

## Context

In both the CRUD and Search domains, transport-layer responsibilities are currently leaking into use cases. For example, the use case validators in the Search domain are aware of "query parameter" names and include them in the use case errors they throw. These parameter names and their types are specific to the REST API and may be different in other interfaces, such as the equivalent Action API module.

This violates the separation of concerns between the core domain logic and the transport mechanism — a key principle in Hexagonal Architecture — and has a number of negative effects:
 - It breaks the Dependency Inversion Principle and violates the expected inward-pointing dependency direction.
 - It couples core logic to HTTP, making it difficult or impossible to reuse application logic in other contexts.

A proper solution would introduce a clear mapping between HTTP request parameters with their names and types to the existing transport layer-agnostic use case request objects. Similarly, use case errors should be mapped to appropriate HTTP responses with their transport specific error codes and messages.

## Decision

Since our application logic is currently only used in the HTTP-specific REST API, we see no immediate need to invest the considerable effort required to fully separate transport-layer concerns from use cases. We will accept the architectural violation for now. When a need arises to reuse the application logic from other interfaces (e.g., Action API or Special Pages), we will reconsider this decision and refactor accordingly.

## Consequences

For now, REST API details such as HTTP parameter names and types will remain visible within the application layer, particularly inside use case validators and errors.
