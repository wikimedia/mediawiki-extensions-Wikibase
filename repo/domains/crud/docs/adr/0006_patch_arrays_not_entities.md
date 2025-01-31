# 6) Patch arrays, not entities in use cases {#rest_adr_0006}

Date: 2022-12-15

## Status

accepted

## Context

The process of using JSON Patch to modify a resource is by nature tied to serialization/deserialization to and from JSON. When we first started working on the patching functionality of our API we decided to hide most of this behind a domain service interface with a `StatementPatcher::patch( Statement $statement, array $patch ): Statement` method which takes a Statement object and a patch, and returns the patched statement.

According to our dependency rules, domain services may not depend on serialization concepts, which meant that errors occurring in the deserialization step of the patching process may not surface as *deserialization errors*, but must be mapped to some equivalent *patch error*. This in turn makes it hard to handle the two cases in our use cases without a lot of redundancy.

## Considered Actions

### 1) Redundant handling of deserialization issues

In this approach we'd keep everything as it is and accept the redundant error handling. Specifically, this means 1) deserialization errors have to be mapped to patcher errors within the patcher service, and 2) patcher errors have to be mapped to use case errors in the patch use case, all while already having the more direct user input deserialization error handling via validators in other (non-patch) use cases.

The above sounds pretty terrible in theory, but the PoC patch didn't look too bad. It is worth noting though that amount of redundant code keeps growing as we introduce more finer-grained error handling.

Proof of concept patch: https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Wikibase/+/867528

Control flow: use case -> patcher (via deserializer -> deserializer exception) -> patcher exception -> use case error response

### 2) Move the patcher into the use case namespace

By moving the patcher service out of the domain services namespace and into the use case namespace, we allow it to access the serialization and validation namespaces. This means that there wouldn't be any duplicated error/exception mapping because the patcher itself could produce the same kinds of errors as the validators inspecting user-provided statement input. The handling for those could then be identical across replace/add/patch use cases.

Proof of concept patch: https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Wikibase/+/867567

Control flow: use case -> patcher (via validator) -> patcher exception with validation error -> validation error -> use case error response

### 3) Let the use case do the patching

Another way to work around the issues mentioned in previous section is simply acknowledging that patching involves converting an entity to JSON and back. In this approach we'd simply get rid of the Statement-specific patcher and instead do the whole process of serializing, patching (`array` to `array`!), deserializing and validating in the use case itself.

We considered two options here:
* 3a) Using the swaggest/json-diff library and its exceptions directly in the use case
* 3b) Wrapping the library calls in a more generic `JsonPatcher::patch( array $original, array $patch ): array` service in order to keep the use cases decoupled from library details.

Proof of concept patch (for 3a): https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Wikibase/+/867603

Control flow: use case -> validator (validating the *patched* serialization) -> validation error -> use case error response

## Decision

We choose option 3b. After thinking it through, it seems like the right thing for the use case to know the details of the patch process, and not claim that JSON Patch documents can magically be applied to our entities. Option 1 results in too much redundancy for our liking, and option 2 feels like cheating our self-imposed rules. We chose 3b over 3a because we like to stay independent from this particular library, and it allows us to hide some "ugly" implementation details (e.g. `$patch->setFlags( JsonPatch::TOLERATE_ASSOCIATIVE_ARRAYS )`).

## Consequences

We'll rework our `PatchItemStatement` use case according to approach 3b and create the generic `JsonPatcher` which will even be reusable for future use cases operating on objects other than Statements.
