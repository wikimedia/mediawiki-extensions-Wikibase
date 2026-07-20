# Statements Domain

An entity-type-agnostic domain holding the shared **read models** used to
represent statements (and their parts) when retrieving data through the Wikibase
REST APIs. It has no services or use cases of its own — it is a small, stable set
of value objects that other domains build and consume.

Sharing these models across domain boundaries is explicitly sanctioned by
[ADR 0025](../../../docs/adr/0025-modularization-by-domain-specific-subsystems.md),
which names a `Statements` domain and permits "sharing the same models for
Statements" while keeping services and use cases per-subsystem.

## Consumers

- The **CRUD** domain (`Wikibase\Repo\Domains\Crud`) — Items and Properties.
- The **Lexeme** REST API in the WikibaseLexeme extension — Lexemes, Forms and
  Senses.

Each consumer keeps its own retrievers that produce these shared read models.

## Structure

- `src/Domain/ReadModel/`: statement read models (value objects) — depend only on
  `Wikibase\DataModel\*` and each other. The architecture test in
  `tests/architecture/` enforces this boundary.
