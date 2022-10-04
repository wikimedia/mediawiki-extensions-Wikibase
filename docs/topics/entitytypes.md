# Entitytypes

## Abstract

Entities as defined by Wikibase have a unique identifier and a type. As Wikibase is an extension of MediaWiki, every entity object is stored on its own page in a given namespace that can hold entities of one type.

The EntityDocument interface describes this construct and adds some methods around it that allow getting the type, getting and setting the id, creating a copy and checking if an entity has content and whether two entities are equal. It is important that the identifier does not count as content and neither affect's emptiness nor equality.

All entities must implement this interface. The two entity types 'item' and 'property' are defined in Wikibase by default. They can be enabled by defining their namespace.

The actual content of an entity can be anything. However, Wikibase defines some basic structures including labels, descriptions, aliases and statements. If an entity holds one of these structures, it has to implement the corresponding provider interface (eg. LabelsProvider).

## Entity type definitions

To support an entity type, additionally to defining the entity itself, the following services need to be implemented and registered:

* Serializers and Deserializers must be defined to convert the entity into native data structures that can be (de-)serialized as json. These classes have to implement the DispatchableSerializer and DispatchableDeserializer interfaces from the serialization component.
* Each entity needs a view to provide an HTML representation to the user. Each view has to implement the EntityDocumentView interface from the WikibaseView component.
* MediaWiki bindings are needed by defining a content class extending EntityContent and a handler class extending EntityHandler. Furthermore, the handler has to be registered in the entity types repository. It is used to create instances of the content class.

Entity types are defined in [WikibaseLib.entitytypes.php] and [WikibaseRepo.entitytypes.php]. They can be amended by extensions using [WikibaseRepoEntityTypes] hook.

The entity types repository is an associative array mapping entity type identifiers to a set of callbacks that act as factory methods. The structure of this set is defined as follows, where each string key is associated with a function having the dedicated signature:

* serializer-factory-callback (repo and client)
  * A callable that returns a DispatchableSerializer instance, with the first and only argument being a SerializerFactory. The returned serializer is used to produce the output visible to the user.
* storage-serializer-factory-callback (repo only)
  * A callable that returns a DispatchableSerializer instance, with the first and only argument being a SerializerFactory. The returned serializer is used when storing entity data internally.
* deserializer-factory-callback (repo and client)
  * A callable that returns a DispatchableDeserializer instance, with the first and only argument being a DeserializerFactory
* entity-id-pattern (repo and client)
  * A regular expression that matches serialized entity IDs
* entity-id-builder (repo and client)
  * A callable that returns an EntityId instance for a given entity ID serialization
* entity-id-composer-callback (repo and client)
  * A callable that returns an EntityId instance for the given unique part of an entity ID serialization. Only entity types with IDs that are constructed from a static and a unique part can and should specify this.
* view-factory-callback (repo only)
  * A callable that returns an [EntityDocumentView] instance, with the arguments being a language code, a LabelDescriptionLookup, a TermLanguageFallbackChain and an EditSectionGenerator
* content-model-id (repo only)
  * A string representing the id of the content model
* content-handler-factory-callback (repo only)
  * A callable that returns an [EntityHandler] instance supporting this entity type
* entity-factory-callback (repo only)
  * A callback for creating an empty entity of this type
* entity-store-factory-callback (repo only)
  * A callable for creating an [EntityStore] for entities of this type. Takes the default store and an [EntityRevisionLookup] as arguments.
* entity-revision-lookup-factory-callback (repo only)
  * A callback for creating an [EntityRevisionLookup] for and entity of this type, with first and only argument being the default lookup, which will be an instance of EntityRevisionLookup.
* entity-title-store-lookup-factory-callback (repo only)
  * A callback for creating an [EntityTitleStoreLookup] for entities of this type, with the first and only argument being the default lookup.
* entity-metadata-accessor-callback
  * A callback for creating a [WikiPageEntityMetaDataAccessor] for an entity of this type, with the arguments being the wiki database name (string|false), and the repository name (string)
* js-deserializer-factory-function (repo only)
  * A string representing a resource loader module that, when `require`d, returns a function returning a `wikibase.serialization.Deserializer` instance supporting this entity type
* changeop-deserializer-callback (repo only)
  * A callable that returns a [ChangeOpDeserializer] instance for change requests to the entity of this type
* rdf-builder-factory-callback (repo only)
  * A callable that returns a [EntityRdfBuilder] instance. See [EntityRdfBuilderFactory::getEntityRdfBuilders] for arguments in the callback
* rdf-builder-stub-factory-callback (repo only)
  * A callable that returns a [EntityStubRdfBuilder] instance. See [EntityStubRdfBuilderFactory::getEntityStubRdfBuilders] for arguments in the callback
* rdf-builder-label-predicates (repo only)
  * List on pairs [ns,local] specifying predicates for RDF export of labels for this entity.
* entity-search-callback (repo only)
  * A callable that returns [EntitySearchHelper] instance. Takes WebRequest as an argument. This defines how the completion search (wbsearchentities) for the entity type works.
* link-formatter-callback
  * A callable that returns [EntityLinkFormatter] instance. Takes a Language object as argument.
* entity-id-html-link-formatter-callback
  * A callable that returns [EntityIdFormatter] instance. Takes a Language object as argument.
* entity-reference-extractor-callback
  * A callable that builds and returns [EntityReferenceExtractors] instance which extract ids of referenced entities from an entity. Can be used to determine which other entities an entity links to (e.g. in "What links here").
* entity-differ-strategy-builder (repo and client)
  * A callback for creating an [EntityDifferStrategy], called without arguments. (The differ strategy is itself responsible for determining whether it can diff a certain entity type or not.)
* entity-patcher-strategy-builder (repo only)
  * A callback for creating an [EntityPatcherStrategy], called without arguments. (The patcher strategy is itself responsible for determining whether it can patch a certain entity type or not.)
* entity-diff-visualizer-callback (repo only)
  * A callback for creating an [EntityDiffVisualizer] for entities of this type. Called with five arguments: a MessageLocalizer, a [ClaimDiffer], a [ClaimDifferenceVisualizer], a SiteLookup, and an [EntityIdFormatter].
* sub-entity-types (optional) (repo and client)
  * An array of strings listing the sub entity types that this entity type contains.
* fulltext-search-context (repo only)
  * Configuration context to allow instantiating a fulltext search query builder
* search-field-definitions (repo only)
  * Field definitions for search indexing
* lua-entity-module (optional) (client only)
  * The name of a Lua module that should be used to represent entities of this type. The module must expose a create() function; mw.wikibase.getEntity() will call this function with a clone of the entity data and return its result. If this is not specified, the standard mw.wikibase.entity module is used.
* entity-id-lookup-callback (client only)
  * A callback for creating an [EntityIdLookup] to resolve Title instances to EntityIds for entities of this types
* prefetching-term-lookup-callback
  * A callable that returns a [PrefetchingTermLookup] instance. When no callback is provided for an entity type, a `NullPrefetchingTermLookup` is used as a fallback. The `PrefetchingTermLookup` is used to prefetch terms for all entities that appear on the page in all languages in the `TermLanguageFallbackChain` to minimize the number of term lookups when an entity page is being rendered. This happens in `FallbackLabelDescriptionLookupFactory::newLabelDescriptionLookup`.
* meta-tags-creator-callback (repo only)
  * A callable for creating an [EntityMetaTagsCreator] for entities of this type. Takes a Language object as argument.
* article-id-lookup-callback (repo only)
  * A callable for creating an [EntityArticleIdLookup] for entities of this type, called without arguments.
* title-text-lookup-callback (repo only)
  * A callable for creating an [EntityTitleTextLookup] for entities of this type, called without arguments.
* url-lookup-callback (repo only)
  * A callable for creating an [EntityUrlLookup] for entities of this type, called without arguments.
* existence-checker-callback (repo only)
  * A callable for creating an [EntityExistenceChecker] for entities of this type, called without arguments.
* redirect-checker-callback (repo only)
  * A callable for creating an [EntityRedirectChecker] for entities of this type, called without arguments.
* property-data-type-lookup-callback (repo only)
  * A callable for creating an [PropertyDataTypeLookup] for entities of the property type, called without arguments.

Extensions that wish to register an entity type should use the [WikibaseRepoEntityTypes] and/or
[WikibaseClientEntityTypes] hooks to provide additional entity type definitions. (See @ref docs_topics_hooks-php)

## Programmatic Access

Information about entity types can be accessed programmatically using the appropriate service objects.
The entity type definitions themselves are wrapped by the [EntityTypeDefinitions] class.

[EntityIdLookup]: @ref Wikibase::Store::EntityIdLookup
[EntityTypeDefinitions]: @ref Wikibase::Lib::EntityTypeDefinitions
[EntityRevisionLookup]: @ref Wikibase::Lib::Store::EntityRevisionLookup
[WikiPageEntityMetaDataAccessor]: @ref Wikibase::Lib::Store::Sql::WikiPageEntityMetaDataAccessor
[ChangeOpDeserializer]: @ref Wikibase::Repo::ChangeOp::ChangeOpDeserializer
[EntityHandler]: @ref Wikibase::Repo::Content::EntityHandler
[EntitySearchHelper]: @ref Wikibase::Repo::Api::EntitySearchHelper
[EntityRdfBuilder]: @ref Wikibase::Rdf::EntityRdfBuilder
[EntityRdfBuilderFactory::getEntityRdfBuilders]: @ref Wikibase::Rdf::EntityRdfBuilderFactory::getEntityRdfBuilders
[EntityDocumentView]: @ref Wikibase::View::EntityDocumentView
[WikibaseLib.entitytypes.php]: @ref WikibaseLib.entitytypes.php
[WikibaseRepo.entitytypes.php]: @ref WikibaseRepo.entitytypes.php
[WikibaseRepoEntityTypes]: @ref WikibaseRepoEntityTypes
[WikibaseClientEntityTypes]: @ref WikibaseClientEntityTypes
[EntityLinkFormatter]: @ref Wikibase::Repo::Hooks::Formatters::EntityLinkFormatter
[EntityIdFormatter]: @ref Wikibase::DataModel::Services::EntityId::EntityIdFormatter
[EntityReferenceExtractors]: @ref Wikibase::Repo::EntityReferenceExtractors::EntityReferenceExtractor
[PrefetchingTermLookup]: @ref Wikibase::DataAccess::PrefetchingTermLookup
[EntityStore]: @ref Wikibase::Lib::Store::EntityStore
[EntityTitleStoreLookup]: @ref Wikibase::Repo::Store::EntityTitleStoreLookup
[EntityMetaTagsCreator]: @ref Wikibase::View::EntityMetaTagsCreator
[EntityDifferStrategy]: @ref Wikibase::DataModel::Services::Diff::EntityDifferStrategy
[EntityPatcherStrategy]: @ref Wikibase::DataModel::Services::Diff::EntityPatcherStrategy
[EntityDiffVisualizer]: @ref Wikibase::Repo::Diff::EntityDiffVisualizer
[ClaimDiffer]: @ref Wikibase::Repo::Diff::ClaimDiffer
[ClaimDifferenceVisualizer]: @ref Wikibase::Repo::Diff::ClaimDifferenceVisualizer
[EntityArticleIdLookup]: @ref Wikibase::Lib::Store::EntityArticleIdLookup
[EntityTitleTextLookup]: @ref Wikibase::Lib::Store::EntityTitleTextLookup
[EntityUrlLookup]: @ref Wikibase::Lib::Store::EntityUrlLookup
[EntityExistenceChecker]: @ref Wikibase::Lib::Store::EntityExistenceChecker
[EntityRedirectChecker]: @ref Wikibase::Lib::Store::EntityRedirectChecker
