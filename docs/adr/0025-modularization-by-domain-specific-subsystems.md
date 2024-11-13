# 25) Modularization by Domain-Specific Subsystems {#adr_0025}

Date: 2024-09-19

## Status

accepted

## Context

Wikibase has evolved into a large, complex, and tangled codebase with numerous unintended interdependencies. This has led to growing maintenance challenges, including longer onboarding time, steep learning curve, slower feature development, and frustrated developers. The codebase's increasing complexity is causing development bottlenecks, making it progressively harder to introduce new features or maintain existing ones efficiently.

The root of this situation lies in the lack of clear architectural boundaries. The codebase suffers from a mixing of presentation, persistence, application, and domain logic, alongside pervasive coupling to the MediaWiki framework. Additional factors include a blending of subdomains and frequent violations of the Single Responsibility Principle, primarily due to an over-reliance on generic entity handling code.

To address these issues, the concerned Linked Open Data (LOD) teams have agreed to focus on restructuring Wikibase to be more adaptable and maintainable. This includes dismantling the monolithic structure and establishing clear boundaries between the various subdomains and components of the system. However, so far there is no overarching pattern to follow and the teams involved have not been able to create a clear vision of the Wikibase software architecture.

## Considered Actions

We have considered the following two options to achieve the envisioned goals:

### Modularize Wikibase by Domain-Specific Subsystems

This approach involves splitting Wikibase into distinct subsystems based on domains. A domain defines the problem space that an application needs to operate within. It can be seen as a "category of related use cases" or may directly relate to certain entity types, such as Items, Properties or Lexemes. The modularization approach follows the concept of "domain-specific use cases" rather than relying on generic handling. By reducing the amount of generic entity code, the system will enable more focused and maintainable logic for each domain, promoting clearer boundaries and reducing unintended interdependencies.

An industry standard software design pattern to clearly define such "domain-specific use cases" is the [Hexagonal Architecture](https://en.wikipedia.org/wiki/Hexagonal_architecture_(software)). It aims to establish strict boundaries between the core business logic (domain) and external systems (e.g., databases, user interfaces, frameworks). Since the Product Platform team have had success applying Hexagonal Architecture principles while implementing the REST API, we expect it to be equally suitable for domain-specific subsystems of Wikibase.

### Modularise Wikibase by Cross-Cutting Subsystems

This refined version of the current Wikibase architecture would split its existing functionality into subsystems across entity types, with which Wikibase-related extensions could interface selectively. Entity Registration would be improved to avoid the current monolithic, all-or-nothing approach, enabling more granular control. In contrast to domain-specific modularization, this would keep the architectural status quo of handling entities generically through dispatching services and branching into entity-specific sub-services where necessary.

## Decision

The Wikidata team and the Product Platform team jointly decide that we will **modularize Wikibase by domain-specific subsystems** and follow the Hexagonal Architecture pattern for all new features or those that will require significant modifications for improved functionality.

Wikibase will take more of a framework role. It remains the central place building upon the various MediaWiki extension points, but there will be significantly fewer low-level dispatching services, because entity type-specific use cases can use entity type-specific services (see e.g. the REST API use cases `GetItem` and `GetProperty`).

For features requiring generic entity handling across multiple entity type-specific use cases (e.g. `wbgetentities`), we will branch out into them only once and make the control flow a lot more linear by:
- Defining controller interfaces for entry points in Wikibase
- Changing existing entry points to use the controller interfaces
- Creating controller implementations calling the corresponding entity type-specific use case(s)

Still, domain-specific subsystems are not limited to entity types. They will also allow the design of entity type agnostic domains, e.g. `Statements` which can generally apply to subjects, rather than Entities of a specific type.

Each subsystem will maintain all of its domain models, domain services, and use cases. Sharing abstractions across subsystem boundaries should be kept to a minimum to avoid coupling, but may make sense in some cases e.g. sharing the same models for Statements. The universal entity domain model would likely not suit well in this approach.

Domain service implementations should reuse code, e.g. since most entities are stored on wiki pages an "ItemLookup", a "PropertyLookup" and a "LexemeLookup" would likely reuse a shared mechanism under the hood.

We will follow this decision until the time we have collected significant evidence proving that the approach is not yielding the expected benefits.

## Consequences

With this new approach and by following an industry standard architecture pattern, we are expecting the following effects on the Wikibase code and the LOD teams:
 - Code will be easier to understand and debug
 - More clearly defined and perceived code ownership
 - Engineers will only have to consider a single domain at a time, rather than solutions that work for all Entities
 - More flexibility for each entity subsystem
 - The "use case" abstraction is closer to the actual functionality
 - Domain services and models can be tailored to the specific subsystem
 - More streamlined control-flow and less jumping between Wikibase and its extensions (Lexeme, EntitySchema, ...)
 - Subsystems have more internal cohesion and are less of a loose collection of magic low-level services
 - Less risk of creating abstractions for things that are not quite the same
 - More clarity on each LOD team’s scope, making it easier to define, learn and work
 - More meaningful and impactful interaction between teams
 - Reduced onboarding time for new developers
