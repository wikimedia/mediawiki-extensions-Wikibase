# Hooks PHP

This file describes hooks defined by the Wikibase extensions.

See https://www.mediawiki.org/wiki/Manual:Hooks for general information on hooks.

[TOC]

Repo
------------------------------------------------------------

#### WikibaseRepoDataTypes {#WikibaseRepoDataTypes}
Called when constructing the top-level [WikibaseRepo] factory
May be used to define additional data types.
See also the [WikibaseClientDataTypes](#WikibaseClientDataTypes) hook.

Hook handlers may add additional definitions.
See [datatypes documentation] for details.

Parameters:
* &$dataTypeDefinitions
  * the array of data type definitions, as defined by WikibaseRepo.datatypes.php.

#### WikibaseRepoEntityTypes {#WikibaseRepoEntityTypes}
Called when constructing the top-level [WikibaseRepo] factory
May be used to define additional entity types.
See also the [WikibaseClientEntityTypes](#WikibaseClientEntityTypes) hook.

Hook handlers may add additional definitions.
See [entitytypes documentation] for details.

Parameters:
* &$entityTypeDefinitions
  * the array of entity type definitions, as defined by WikibaseLib.entitytypes.php.

#### WikibaseTextForSearchIndex {#WikibaseTextForSearchIndex}
Called by [EntityContent::getTextForSearchIndex()] to allow extra text to be passed to the search engine for indexing.
If the hook function returns false, no text at all will be passed to the search index.

Parameters:
* $entity
  * EntityContent to be indexed
* &$text
  * The text to pass to the indexed (to be modified).

#### WikibaseContentModelMapping {#WikibaseContentModelMapping}
Called by [WikibaseRepo::getContentModelMappings()] to allow additional mappings between Entity types and content model identifiers to be defined.

Parameters:
* &$map
  * An associative array mapping Entity types to content model ids.

#### WikibaseRepoEntityNamespaces {#WikibaseRepoEntityNamespaces}
Called in the example settings to allow additional mappings between Entity types and namespace IDs to be defined.
Only used if no custom entity sources are defined.

Parameters:
* &$map
  * An associative array mapping Entity types to namespace ids.

#### WikibaseRebuildData (DEPRECATED)
Parameters:
* $report
  * A closure that can be called with a string to report that messages.

#### WikibaseDeleteData (DEPRECATED)
Parameters:
* $report
  * A closure that can be called with a string to report that messages.

#### WikibaseChangeNotification {#WikibaseChangeNotification}
Triggered from ChangeNotifier via a [HookChangeTransmitter] to notify any listeners of changes to entities.

For performance reasons, does not include statement, description and alias diffs (see [T113468], [T163465]).

Parameters:
* $change
  * The Change object representing the change.

#### WikibaseContentLanguages {#WikibaseContentLanguages}
Called by[ WikibaseRepo::getContentLanguages()], which in turn is called by some other getters, to define the content languages per context.

Parameters:
* &$map
  * An associative array mapping contexts ('term', 'monolingualtext', extension-specific…) to ContentLanguage objects.

#### GetEntityContentModelForTitle {#GetEntityContentModelForTitle}
Called by [EntityContentFactory] to see what is the entity content type of the Title.
Extensions can override it so entity content type does not equal page content type.

Parameters:
* $title
  * Title object for the page
* &$contentType
  * Content type for the page. Extensions can override this.

#### WikibaseRepoOnParserOutputUpdaterConstruction {#WikibaseRepoOnParserOutputUpdaterConstruction}
Allows extensions to register extra EntityParserOutputUpdater implementations.

Parameters:
* $statementUpdater
* &$entityUpdaters

#### GetEntityByLinkedTitleLookup {#GetEntityByLinkedTitleLookup}
Allows extensions to add custom EntityByLinkedTitleLookup services.

Parameters:
* &$lookup

Client
------------------------------------------------------------

#### WikibaseClientDataTypes {#WikibaseClientDataTypes}
Called when constructing the top-level [WikibaseClient] factory
May be used to define additional data types
See also the [WikibaseRepoDataTypes](#WikibaseRepoDataTypes) hook.

Hook handlers may add additional definitions.
See the [datatypes documentation] for details.

Parameters:
* &$dataTypeDefinitions
  * The array of data type definitions, as defined by WikibaseClient.datatypes.php.

#### WikibaseClientEntityTypes {#WikibaseClientEntityTypes}
Called when constructing the top-level [WikibaseClient] factory
May be used to define additional entity types.
See also the [WikibaseRepoEntityTypes](#WikibaseRepoEntityTypes) hook.

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
Called by [OtherProjectsSidebarGenerator] to allow altering the sitelinks used
in language links and the other project's sidebar.
Only called in case the page we are on is linked with an item.

Parameters:
* $item
  * Item the page is linked with.
* &$siteLinks
  * Array containing the site links to display indexed by site global ID.
* $usageAccumulator
  * A [UsageAccumulator] to track the usages of Wikibase entities done by the hook handlers.


[T113468]: https://phabricator.wikimedia.org/T113468
[T163465]: https://phabricator.wikimedia.org/T163465
[WikibaseClient]: @ref Wikibase::Client::WikibaseClient
[WikibaseClient::getEntityNamespaceLookup()]: @ref Wikibase::Client::WikibaseClient::getEntityNamespaceLookup()
[WikibaseRepo]: @ref Wikibase::Repo::WikibaseRepo
[EntityContent::getTextForSearchIndex()]: @ref Wikibase::Repo::Content::EntityContent::getTextForSearchIndex()
[WikibaseRepo::getContentLanguages()]: @ref Wikibase::Repo::WikibaseRepo::getContentLanguages()
[WikibaseRepo::getEntityNamespaceLookup()]: @ref Wikibase::Repo::WikibaseRepo::getEntityNamespaceLookup()
[WikibaseRepo::getContentModelMappings()]: @ref Wikibase::Repo::WikibaseRepo::getContentModelMappings()
[EntityContentFactory]: @ref Wikibase::Repo::Content::EntityContentFactory
[HookChangeTransmitter]: @ref Wikibase::Repo::Notifications::HookChangeTransmitter
[OtherProjectsSidebarGenerator]: @ref Wikibase::Client::Hooks::OtherProjectsSidebarGenerator
[ChangeHandler::handleChange()]: @ref Wikibase::Client::Changes::ChangeHandler::handleChange()
[UsageAccumulator]: @ref Wikibase::Client::Usage::UsageAccumulator
[entitytypes documentation]: @ref docs_topics_entitytypes
[datatypes documentation]: @ref docs_topics_datatypes
