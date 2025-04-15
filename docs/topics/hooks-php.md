# Hooks PHP

This file describes hooks defined by the Wikibase extensions.

See https://www.mediawiki.org/wiki/Manual:Hooks for general information on hooks.

[TOC]

Repo
------------------------------------------------------------

#### WikibaseRepoDataTypes {#WikibaseRepoDataTypes}
See @ref Wikibase::Repo::Hooks::WikibaseRepoDataTypesHook.

#### WikibaseRepoEntityTypes {#WikibaseRepoEntityTypes}
See @ref Wikibase::Repo::Hooks::WikibaseRepoEntityTypesHook.

#### WikibaseTextForSearchIndex {#WikibaseTextForSearchIndex}
See @ref Wikibase::Repo::Hooks::WikibaseTextForSearchIndexHook.

#### WikibaseContentModelMapping {#WikibaseContentModelMapping}
See @ref Wikibase::Repo::Hooks::WikibaseContentModelMappingHook.

#### WikibaseRepoEntityNamespaces {#WikibaseRepoEntityNamespaces}
Called in the example settings to allow additional mappings between Entity types and namespace IDs to be defined.
Only used if no custom entity sources are defined.

Parameters:
* &$map
  * An associative array mapping Entity types to namespace ids.

#### WikibaseChangeNotification {#WikibaseChangeNotification}
See @ref Wikibase::Repo::Hooks::WikibaseChangeNotificationHook.

#### WikibaseContentLanguages {#WikibaseContentLanguages}
Called by[ WikibaseRepo::getContentLanguages()], which in turn is called by some other getters, to define the content languages per context.

Parameters:
* &$map
  * An associative array mapping contexts ('term', 'monolingualtext', extension-specific…) to ContentLanguage objects.

#### GetEntityContentModelForTitle {#GetEntityContentModelForTitle}
See @ref Wikibase::Repo::Hooks::GetEntityContentModelForTitleHook.

#### WikibaseRepoOnParserOutputUpdaterConstruction {#WikibaseRepoOnParserOutputUpdaterConstruction}
See @ref Wikibase::Repo::Hooks::WikibaseRepoOnParserOutputUpdaterConstructionHook.

#### GetEntityByLinkedTitleLookup {#GetEntityByLinkedTitleLookup}
See @ref Wikibase::Repo::Hooks::GetEntityByLinkedTitleLookupHook.

### WikibaseRepoEntitySearchHelperCallbacks {#WikibaseRepoEntitySearchHelperCallbacks}
See @ref Wikibase::Repo::Hooks::WikibaseRepoEntitySearchHelperCallbacks.

Client
------------------------------------------------------------

#### WikibaseClientDataTypes {#WikibaseClientDataTypes}
See @ref Wikibase::Client::Hooks::WikibaseClientDataTypesHook

#### WikibaseClientEntityTypes {#WikibaseClientEntityTypes}
Called when constructing the top-level [WikibaseClient] factory
May be used to define additional entity types.
See also @ref Wikibase::Repo::Hooks::WikibaseRepoEntityTypesHook.

Hook handlers may add additional definitions.
See [entitytypes documentation] for details.

Parameters:
* **&$entityTypeDefinitions**
  * The array of entity type definitions, as defined by WikibaseLib.entitytypes.php.

#### WikibaseHandleChanges {#WikibaseHandleChanges}
Called by [ChangeHandler::handleChange()] to allow pre-processing of changes.

Parameters:
* **$changes**
  * A list of Change objects
* **$rootJobParams**
  * Any relevant root job parameters to be inherited by child jobs.

#### WikibaseHandleChange {#WikibaseHandleChange}
Called by [ChangeHandler::handleChange()] to allow alternative processing of changes.

Parameters:
* $change
  * A Change object
* $rootJobParams
  * Any relevant root job parameters to be inherited by child jobs.

#### WikibaseClientSiteLinksForItem {#WikibaseClientSiteLinksForItem}
See @ref Wikibase::Client::Hooks::WikibaseClientSiteLinksForItemHook.

[WikibaseClient]: @ref Wikibase::Client::WikibaseClient
[WikibaseClient::getEntityNamespaceLookup()]: @ref Wikibase::Client::WikibaseClient::getEntityNamespaceLookup()
[WikibaseRepo::getContentLanguages()]: @ref Wikibase::Repo::WikibaseRepo::getContentLanguages()
[WikibaseRepo::getEntityNamespaceLookup()]: @ref Wikibase::Repo::WikibaseRepo::getEntityNamespaceLookup()
[WikibaseRepo::getContentModelMappings()]: @ref Wikibase::Repo::WikibaseRepo::getContentModelMappings()
[OtherProjectsSidebarGenerator]: @ref Wikibase::Client::Hooks::OtherProjectsSidebarGenerator
[ChangeHandler::handleChange()]: @ref Wikibase::Client::Changes::ChangeHandler::handleChange()
[entitytypes documentation]: @ref docs_topics_entitytypes
[datatypes documentation]: @ref docs_topics_datatypes
