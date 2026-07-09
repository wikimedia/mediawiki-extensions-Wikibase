# Controllers

Controllers are high-level, use case-oriented entry points for Wikibase features, introduced as part of the modularization strategy described in [ADR 25](@ref adr_0025). Unlike [entity type definitions](@ref docs_topics_entitytypes), which define lower-level services orchestrated by generic Wikibase application logic, controllers own the full execution of a specific feature for a given entity type.

## Controller definitions

Controllers are registered per entity type in [WikibaseRepo.controllers.php](@ref WikibaseRepo.controllers.php). Extensions can add or modify controller definitions using the [WikibaseRepoControllers hook](@ref Wikibase::Repo::Hooks::WikibaseRepoControllersHook).

For each registered controller, there are typically four types of components:
* a controller interface, which defines the contract for a feature
* implementations of the controller interface for each entity type that supports the feature
* corresponding controller factory callbacks in the `*.controllers.php` file(s)
* a dispatcher which is used in the feature's entry point (e.g. a REST route handler) to delegate the request to an entity type-specific controller

Examples can be found by exploring usages of the [ControllerRegistry::get()](@ref Wikibase::Repo::ControllerRegistry::get()) method and the controller definition files such as [WikibaseRepo.controllers.php](@ref WikibaseRepo.controllers.php).

## Example: `wbsearchentities-controller`

The `wbsearchentities-controller` (entry point for the `wbsearchentities` Action API module) illustrates what each of the four component types looks like in practice.

### 1. Controller interface

The interface defines the feature contract without committing to a specific entity type. See [WbSearchEntitiesController](@ref Wikibase::Repo::Domains::Search::Infrastructure::Controllers::WbSearchEntitiesController). It returns a [WbSearchEntitiesResponse](@ref Wikibase::Repo::Domains::Search::Infrastructure::Controllers::WbSearchEntitiesResponse) value object — the requested page of results plus a `hasMore` flag — so pagination lives in the domain rather than in the API module:

```php
interface WbSearchEntitiesController {
    /** @throws EntitySearchException */
    public function search( WbSearchEntitiesRequest $request ): WbSearchEntitiesResponse;
}
```

### 2. Entity-type-specific implementation

Each entity type that supports the feature gets its own implementation, which owns the full execution of the use case for that type. For items, this is [ItemWbSearchEntitiesController](@ref Wikibase::Repo::Domains::Search::Infrastructure::Controllers::ItemWbSearchEntitiesController):

```php
class ItemWbSearchEntitiesController implements WbSearchEntitiesController {
    public function __construct(
        private readonly ItemPrefixSearch $itemPrefixSearch,
        private readonly EntitySourceLookup $entitySourceLookup
    ) {}

    public function search( WbSearchEntitiesRequest $request ): WbSearchEntitiesResponse {
        $response = $this->itemPrefixSearch->execute( /* ... */ );
        return new WbSearchEntitiesResponse(
            array_map( fn ( $r ) => $this->convertResult( $r ), iterator_to_array( $response->results ) ),
            $response->results->hasMore()
        );
    }
    // ...
}
```

An entity type may also share a generic implementation — for example [FallbackEntitySearchHelperController](@ref Wikibase::Repo::Domains::Search::Infrastructure::Controllers::FallbackEntitySearchHelperController), which is parameterised by entity type and is currently used for properties.

### 3. Factory callbacks

Controllers are instantiated lazily through callbacks keyed by entity type and controller constant. For built-in entity types these live in [WikibaseRepo.controllers.php](@ref WikibaseRepo.controllers.php):

```php
return [
    Item::ENTITY_TYPE => [
        ControllerRegistry::WB_SEARCH_ENTITIES_CONTROLLER => static function () {
            return new ItemWbSearchEntitiesController(
                WbSearch::getItemPrefixSearch(),
                WikibaseRepo::getEntitySourceLookup()
            );
        },
    ],
    Property::ENTITY_TYPE => [
        ControllerRegistry::WB_SEARCH_ENTITIES_CONTROLLER => static function () {
            return new FallbackEntitySearchHelperController( /* ... */ );
        },
    ],
];
```

Extensions that add new entity types register their callbacks via the [WikibaseRepoControllers hook](@ref Wikibase::Repo::Hooks::WikibaseRepoControllersHook) instead of editing this file.

### 4. Dispatcher

The entry point — here the [SearchEntities](@ref Wikibase::Repo::Api::SearchEntities) Action API module — does not depend on any single implementation. It uses a dispatcher that resolves the right controller per entity type via [ControllerRegistry::get()](@ref Wikibase::Repo::ControllerRegistry::get()). See [WbSearchEntitiesControllerDispatcher](@ref Wikibase::Repo::Domains::Search::Infrastructure::Controllers::WbSearchEntitiesControllerDispatcher):

```php
class WbSearchEntitiesControllerDispatcher {
    public function __construct( private readonly array $callbacks ) {}

    public function getControllerForEntityType( string $entityType ): WbSearchEntitiesController {
        if ( !isset( $this->callbacks[$entityType] ) ) {
            throw new InvalidArgumentException( "No controller registered for entity type '$entityType'" );
        }
        return ( $this->callbacks[$entityType] )();
    }
}
```

## Available controllers

* `wbsearchentities-controller`: entry point for the `wbsearchentities` Action API module for a given entity type.
