# Statements Domain

An entity-type-agnostic domain holding the shared building blocks for statements
in the Wikibase REST APIs: the **read models** used to represent statements (and
their parts) when retrieving data, the **serializers** that turn those read
models into the JSON response shapes, and the **deserializers** and
**validators** that turn incoming JSON back into `Wikibase\DataModel` statements.
It has no use cases of its own — it is a small, stable set of value objects plus
the code that converts them to and from JSON, which other domains build and
consume.

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
- `src/Domain/Services/`: `StatementReadModelConverter`, which builds the read
  models from `Wikibase\DataModel` statements, and the `ValueTypeLookup`
  interface.
- `src/Application/Serialization/`: serializers that convert the read models into
  the JSON response structures for the REST API, and deserializers that convert
  incoming JSON into the corresponding write models.
- `src/Application/Validation/`: validators which wrap the deserializers and
  report failures as validation errors.
- `src/Infrastructure/`: implementations of validator and service interfaces.
