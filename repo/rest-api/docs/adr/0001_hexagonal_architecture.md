# 1) Follow the Hexagonal Architecture {#rest_adr_0001}

Date: 2022-03-02

## Status

accepted

## Context

Before creating this code base we promised to ourselves to have clear boundaries to Wikibase internals in order to keep the REST API code simple, while also not making the existing Wikibase code base more complicated. The [Hexagonal Architecture](https://alistair.cockburn.us/hexagonal-architecture/) suggests to decouple an application's business logic from the data access mechanism and user interfaces, which can be achieved by imposing strict dependency rules across these boundaries. Being a standard (as in not "homebrew") pattern means that we can rely on existing documentation and expertise.

Team FUN has successfully applied a similar architectural approach in their [fundraising application](https://github.com/wmde/fundraising-application#project-structure). They kindly agreed to give us an intro to the topic and had a chat with us to hear our concerns and questions, which convinced us further.

WMDE's fundraising application architecture is similar to the one described in [an article about Netflix's use of the hexagonal architecture](https://netflixtechblog.com/ready-for-changes-with-hexagonal-architecture-b315ec967749), which describes the following concepts and rules:
* The application logic consists of Entities, Repositories (data access interfaces), and Interactors (aka use cases).
* On the outside of the application boundary are Data Sources on the data side, and a Transport Layer on the user side of the hexagon.
* Dependencies must point from the outside in. Entities only depend on themselves, Interactors and Repositories depend on the Entities, and the Transport and Data Access Layers depend on Interactors and Repositories, respectively.
* Inputs and outputs of the system must flow from the user side through the business logic to the data side and back. The code on the outermost level must not skip the business logic.

In practice this means that we'll allow ourselves to bind to MediaWiki and Wikibase services and infrastructure only in the outermost user-facing and data source parts of the code, while keeping the inner business logic completely independent from it. Since this REST API is a component of Wikibase, we still consider the existing data model part of our entities, and also allow the use of (some?) parts of the data model libraries.

## Decision

We will attempt to follow the Hexagonal Architecture inspired by the structure of the FUN code bases. Initially, we will follow its rules to the best of our knowledge and keep an eye on them during code review. Once we feel that our code structure has stabilized we will [look into tooling to automatically enforce the rules](https://phabricator.wikimedia.org/T305132).

## Consequences

We hope for this to result in a well-structured and maintainable subsystem, that does not simply pile onto the rest of the Wikibase Repo code base. Some more general benefits and drawbacks can be found in the corresponding sections of [the FUN team's presentation notes](https://gist.github.com/gbirke/f02acccfe4837b3c62e2066959578fbd#benefits-of-the-clean-architecture).
