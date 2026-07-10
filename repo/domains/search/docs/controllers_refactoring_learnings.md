## Learnings from refactoring wbsearchentities {#controllers_refactoring_learnings}

After introducing REST API prefix search endpoints for Items and Properties following the [Hexagonal Architecture](https://alistair.cockburn.us/hexagonal-architecture/) approach, the team decided to reuse the newly created application use cases for the existing `wbsearchentities` Action API endpoint. Both APIs provide essentially the same functionality but differ in some request parameters, response formats, and implementation details. Integrating the prefix search use cases therefore required both refactoring the `SearchEntities` Action API module as well as extending the use cases with additional functionality.

This refactoring of `wbsearchentities` marks the first practical application of the domain-specific modularization concept described in [ADR 25](@ref adr_0025). It is now the first Wikibase entry point that handles generic entity types by delegating requests to entity type-specific controllers, which in turn call the corresponding entity type-specific use case(s).

### wbsearchentities before refactoring
* Wikibase defined [EntitySearchHelper](@ref EntitySearchHelper.php) as a generic service interface for searching entities
* Different entity types implemented EntitySearchHelper in different ways e.g. Items and Properties were searched by label, Lexemes were searched by lemma etc. These entity type specific implementations were registered via entity type definitions in `*.entitytypes.php`
* The Action API module's entry points [SearchEntities](@ref SearchEntities.php) and [QuerySearchEntities](@ref QuerySearchEntities.php) called `TypeDispatchingEntitySearchHelper` which delegated to the entity type specific implementations based on the “type” query param

\image{inline} html ./search-before.svg "Prefix Search before refactoring" width=75%

### Controller Registry

According to [ADR 25](@ref adr_0025), each Wikibase entry point that requires generic entity handling needs a corresponding controller. The controller acts as an adapter between the framework-specific entry point and the entity type-specific implementation of the feature. We defined the controller interface [WbSearchEntitiesController](@ref WbSearchEntitiesController.php), and the implementations for Item, Property, EntitySchema and other entity types serve as prefix search entry points for all entity types.

For registering these controller implementations, we introduced a new [ControllerRegistry](@ref ControllerRegistry.php), together with a [WikibaseRepoControllersHook](@ref WikibaseRepoControllersHook.php) and a dedicated `WikibaseRepo.controllers.php` wiring file, following the existing entity type registration pattern. This allows extensions to register controllers for additional entity types without modifying the generic `wbsearchentities` implementation.

### wbsearchentities after refactoring
* Wikibase defines a [WbSearchEntitiesController](@ref WbSearchEntitiesController.php) controller interface.
* Each relevant entity type implements the controller.
* The Action API module's entry points [SearchEntities](@ref SearchEntities.php) and [QuerySearchEntities](@ref QuerySearchEntities.php) call [WbSearchEntitiesControllerDispatcher](@ref WbSearchEntitiesControllerDispatcher.php), which delegates to the entity type specific controller based on the “type” query param.
* The controller calls the use case (for item and property prefix search) and transforms the use case response into its own return type. For other entity types, a fallback controller calls the respective entity type’s [EntitySearchHelper](@ref EntitySearchHelper.php) implementation.

\image{inline} html ./search-after.svg "Prefix Search after refactoring" width=75%

### Controller request object
The Action API entry points delegate the request with all its parameters to the new entity type specific controllers. We decided not to pass the request parameters to the controller as separate arguments, but to create a request DTO which contains all of them. This made it easier to change the controller interface while keeping it compatible with its implementations which live in different repositories.

For the transport-agnostic application use cases, we generally prefer passing input parameters as scalar types (string, int, bool), and apply the same principle to controller request objects. Care is therefore required when translating framework objects such as Language or User into values expected by the application layer. This became particularly apparent for language codes, where MediaWiki’s internal language code format and BCP-47 codes are used in different parts of the system. During the refactoring we accidentally passed a BCP-47 code where the existing search implementation expected a MediaWiki-internal language code, which broke language fallback for search result labels. For the User object, however, we ultimately decided to pass the domain object itself through the controller into the use case request, since it represents execution context rather than user input.

A related challenge is that existing implementations do not always receive all required execution context explicitly. For example, EntitySearchHelper implementations determine the result language from MediaWiki’s request context instead of accepting it as a parameter. The new use cases make these dependencies explicit by including them in their request objects, while the fallback controller preserves the legacy behavior for existing implementations.

### Controller implementations for Items and Properties

For `wbsearchentities` requests on Items and Properties, the newly created controllers are now calling the existing use cases. Several issues had to be considered to make this work:
* Use cases had to be extended to cover Action API features, that are not supported by the REST API suggest endpoints, such as the `resultLanguage` and `strictLanguage` parameters or setting a search profile.
* The search results' concept URIs are needed in the Action API response, but aren't provided by the use case. We decided to add the additional key in the controller's result conversion step.
* The property data type is also required in the `wbsearchentities` response. In contrast to the concept URI, we extended the use case response model so that it is propagated from the search engine through the application layer, with the controller simply forwarding it into the Action API response.
* Use case errors needed to be wrapped into `EntitySearchException`, which is being handled by the `SearchEntities` module.

### Input parameter validation

Validation isn't handled consistently, yet. For most parameters, the new approach leads to redundant validation in the SearchEntities module entrypoint and then again in the use case validator. For some parameters exclusive to wbsearchentities (search profile, result language), we chose to not validate them in the use case for now.

When looking into the parameter validation, we noticed that the existing use cases do not take the `apihighlimits` privilege into account. Instead, they have a higher limit for basic users than `wbsearchentities`. To fix this, the use case requests now include a `User` object for authenticated requests, and we adjusted the use case validators so that they are now considering a different maximum limit for privileged users.

### Pagination

At the point of the refactoring, pagination was done by [SearchEntities](@ref SearchEntities.php) internally, but it also happens within the [ItemPrefixSearch](@ref ItemPrefixSearch.php) and [PropertyPrefixSearch](@ref PropertyPrefixSearch.php) use cases. This caused an unexpected issue with the `limit` and `offset` parameter validation in the use case, which had to be disabled as a quick fix for Action API requests.

We decided to address this in a seperate task ([T428038](https://phabricator.wikimedia.org/T428038)) following the refactoring. While it is relatively straightforward to move the pagination logic out of [SearchEntities](@ref SearchEntities.php) into the existing use cases for item and property search, for all other entity types, it needs to move into the corresponding controllers.

### Migration and cleanup

After we had adjusted [SearchEntities](@ref SearchEntities.php) to use the new controller approach, the main use of `entity-search-callback` in the entity type definitions had disappeared, so we wanted to remove that and the `TypeDispatchingEntitySearchHelper`. Before, we had to adjust all places that still made use of them. Search features only involving a single entity type were modified to use the type-specific service instead of `TypeDispatchingEntitySearchHelper` (e.g. the item disambiguation page or `PropertySuggester`). The `searchEntities.php` maintenance script was considered expendable, so we deleted it. The only remaining search feature that actually required type dispatching, was [QuerySearchEntities](@ref QuerySearchEntities.php), which we adjusted to reuse the wbsearchentities controller (see diagram above). This is unusual for two different entrypoints (`?action=wbsearchentities` and `?action=query&list=wbsearch`), but was acceptable in this case, since [QuerySearchEntities](@ref QuerySearchEntities.php) covers the same functionality as `wbsearchentities`, only packaged as a query module.

This allowed us to remove:
  * the `entity-search-callback` entries from all `*.entitytypes.php` files
  * the `TypeDispatchingEntitySearchHelper` class and corresponding service definition
  * the callbacks "service" from entity type definition wiring file

### Other entity types and outlook

A key goal of the `wbsearchentities` refactoring was to implement the hexagonal architecture approach from [ADR 25](@ref adr_0025), where all application logic should reside in use cases and entry points act purely as adapters. Controllers provide the layer between transport and application code, translating requests into use case inputs and mapping results back to API responses without introducing application logic.

The refactoring shows how this controller pattern can be used for generic entity handling. At the time of writing, not all entity types have fully migrated to dedicated use cases, yet. For example, `EntitySchema` uses a dedicated controller that forwards to its existing search implementation. `WikibaseLexeme` still relies on an interim fallback controller that delegates to `EntitySearchHelper` implementations for Lexemes, Forms, and Senses. These fallback paths can be replaced with dedicated controller + use case implementations over time, eventually allowing the removal of the `EntitySearchHelper` abstraction and its type dispatching services.


